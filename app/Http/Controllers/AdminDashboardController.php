<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Statistik umum - dioptimalkan via aggregate queries.
        // Waktu dikirim sebagai binding (bukan date('now') SQLite) agar portabel SQLite/MySQL dan mengikuti timezone aplikasi.
        $now = now();
        $today = $now->toDateString();

        $repStat = Report::selectRaw("
            COUNT(*) as total_reports,
            COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_reports,
            COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_reports,
            COALESCE(SUM(CASE WHEN status IN ('in_progress', 'assigned', 'verified') THEN 1 ELSE 0 END), 0) as in_progress_reports,
            COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END), 0) as today_reports,
            COALESCE(SUM(CASE WHEN assigned_to IS NULL AND status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_assignments,
            COALESCE(SUM(CASE WHEN DATE(resolved_at) = ? THEN 1 ELSE 0 END), 0) as completed_today,
            COALESCE(SUM(CASE WHEN sla_due_at < ? AND status NOT IN ('resolved', 'closed', 'rejected') THEN 1 ELSE 0 END), 0) as sla_breached,
            COALESCE(SUM(CASE WHEN sla_due_at >= ? AND sla_due_at <= ? AND status NOT IN ('resolved', 'closed', 'rejected') THEN 1 ELSE 0 END), 0) as due_soon
        ", [$today, $today, $now, $now, $now->copy()->addDay()])->first();

        $compStat = Complaint::selectRaw("
            COUNT(*) as total_complaints,
            COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_complaints,
            COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_complaints
        ")->first();

        $stats = [
            'total_users' => User::count(),
            'total_reports' => (int) ($repStat->total_reports ?? 0),
            'total_complaints' => (int) ($compStat->total_complaints ?? 0),
            'total_departments' => Department::count(),
            'pending_reports' => (int) ($repStat->pending_reports ?? 0),
            'pending_complaints' => (int) ($compStat->pending_complaints ?? 0),
            'resolved_reports' => (int) ($repStat->resolved_reports ?? 0),
            'resolved_complaints' => (int) ($compStat->resolved_complaints ?? 0),
            'in_progress_reports' => (int) ($repStat->in_progress_reports ?? 0),
            'today_reports' => (int) ($repStat->today_reports ?? 0),
            'pending_assignments' => (int) ($repStat->pending_assignments ?? 0),
            'completed_today' => (int) ($repStat->completed_today ?? 0),
            'sla_breached' => (int) ($repStat->sla_breached ?? 0),
            'due_soon' => (int) ($repStat->due_soon ?? 0),
        ];

        // Statistik berdasarkan departemen (dioptimalkan tanpa hydrasi relasi berat yang tidak ditampilkan)
        $departmentStats = Department::withCount(['reports', 'complaints', 'users'])->get();

        // Laporan terbaru
        $recentReports = Report::with(['user', 'department', 'assignedUser'])
            ->latest()
            ->limit(10)
            ->get();

        // Keluhan terbaru
        $recentComplaints = Complaint::with(['user', 'department', 'assignedUser'])
            ->latest()
            ->limit(10)
            ->get();

        // Semua departemen
        $departments = Department::all();

        // Statistik bulanan
        $monthlyStats = [
            'reports' => Report::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'complaints' => Complaint::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return view('admin.modern-dashboard', compact(
            'stats',
            'departmentStats',
            'recentReports',
            'recentComplaints',
            'monthlyStats',
            'departments'
        ));
    }

    public function reports()
    {
        $search = $this->adminSearch();
        $perPage = Auth::user()->getSettings('items_per_page', 15);
        $reports = Report::with(['user', 'department', 'assignedUser'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$search.'%')->orWhere('ticket_no', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate($perPage)->appends(request()->query());

        // Get all staff for assignment dropdown
        $staffList = User::where('role', 'staff')->get();

        return view('admin.reports', compact('reports', 'staffList'));
    }

    public function complaints()
    {
        $search = $this->adminSearch();
        $perPage = Auth::user()->getSettings('items_per_page', 15);
        $complaints = Complaint::with(['user', 'department', 'assignedUser'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$search.'%')->orWhere('ticket_no', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate($perPage)->appends(request()->query());

        return view('admin.complaints', compact('complaints'));
    }

    public function users()
    {
        $search = $this->adminSearch();
        try {
            $perPage = Auth::user()->getSettings('items_per_page', 15);
            // Get users with department relationship, paginate per page
            $users = User::with('department')
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)->appends(request()->query());

            // Get all departments for filters
            $departments = Department::all();

            return view('admin.users', compact('users', 'departments'));
        } catch (\Exception $e) {
            \Log::error('Error loading users: '.$e->getMessage());

            return back()->with('error', 'Gagal memuat data pengguna. Silakan coba lagi.');
        }
    }

    /**
     * Activate or deactivate a user account. Deactivated users cannot log in
     * and are signed out on their next request (see EnsureAccountIsActive).
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ((int) $user->id === (int) Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $oldStatus = (bool) $user->is_active;
        $user->update(['is_active' => ! $oldStatus]);

        \App\Models\AuditLog::record($user, $user->is_active ? 'user_activated' : 'user_deactivated', ['is_active' => $oldStatus], ['is_active' => $user->is_active]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', 'Akun "'.$user->name.'" berhasil '.$statusText.'.');
    }

    public function departments()
    {
        $search = $this->adminSearch();
        $departments = Department::withCount(['users', 'reports', 'complaints'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))
            ->orderBy('name')->get();

        return view('admin.departments', compact('departments'));
    }

    private function adminSearch(): string
    {
        $validated = request()->validate(['q' => 'nullable|string|max:100']);

        return trim($validated['q'] ?? '');
    }

    /**
     * System monitoring dashboard
     */
    public function monitoring()
    {
        // Real-time statistics
        $stats = [
            'total_reports' => Report::count(),
            'total_complaints' => Complaint::count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'in_progress_reports' => Report::where('status', 'in_progress')->count(),
            'resolved_reports' => Report::where('status', 'resolved')->count(),
            'escalated_reports' => Report::where('is_escalated', true)->count(),
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
        ];

        // Department performance
        $departmentPerformance = Department::withCount(['reports', 'complaints', 'users'])
            ->with(['reports' => function ($query) {
                $query->whereIn('status', ['resolved', 'closed']);
            }])
            ->get()
            ->map(function ($dept) {
                $totalReports = $dept->reports_count;
                $resolvedReports = $dept->reports->count();

                return [
                    'name' => $dept->name,
                    'total_reports' => $totalReports,
                    'resolved_reports' => $resolvedReports,
                    'resolution_rate' => $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100, 2) : 0,
                    'staff_count' => $dept->users_count,
                ];
            });

        // Recent activity
        $recentActivity = collect();

        // Recent reports
        $recentReports = Report::with(['user', 'department'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($report) {
                return [
                    'type' => 'report',
                    'title' => $report->title,
                    'user' => $report->user?->name ?? 'Anonim',
                    'department' => $report->department?->name ?? 'Umum',
                    'status' => $report->status,
                    'created_at' => $report->created_at,
                ];
            });

        // Recent complaints
        $recentComplaints = Complaint::with(['user', 'department'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($complaint) {
                return [
                    'type' => 'complaint',
                    'title' => $complaint->title,
                    'user' => $complaint->user?->name ?? 'Anonim',
                    'department' => $complaint->department?->name ?? 'Umum',
                    'status' => $complaint->status,
                    'created_at' => $complaint->created_at,
                ];
            });

        $recentActivity = $recentReports->merge($recentComplaints)
            ->sortByDesc('created_at')
            ->take(10);

        // SLA monitoring
        $slaStats = [
            'total_with_sla' => Report::whereNotNull('sla_due_at')->count(),
            'sla_breached' => Report::where('is_escalated', true)->count(),
            'sla_due_soon' => Report::where('sla_due_at', '<=', now()->addHours(24))
                ->where('is_escalated', false)
                ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
                ->count(),
        ];

        // Monthly trends
        $monthlyTrends = [
            'reports' => Report::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'complaints' => Complaint::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return view('admin.monitoring', compact(
            'stats',
            'departmentPerformance',
            'recentActivity',
            'slaStats',
            'monthlyTrends'
        ));
    }

    public function editReport($id)
    {
        $report = Report::findOrFail($id);
        $departments = Department::all();
        $staff = User::where('role', 'staff')->get();

        return view('admin.reports_edit', compact('report', 'departments', 'staff'));
    }

    public function updateReport(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'status' => ['required', \Illuminate\Validation\Rule::in($report->allowedStatuses())],
            'priority' => 'required|in:low,medium,high,urgent',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
        ]);

        $fields = ['title', 'description', 'category', 'status', 'priority', 'department_id', 'assigned_to', 'location'];
        $oldValues = $report->only($fields);

        $report->update($request->only($fields));

        \App\Models\AuditLog::record($report, 'admin_report_updated', $oldValues, $report->only($fields));

        if ($oldValues['status'] !== $report->status) {
            event(new \App\Events\ReportStatusChanged($report, $oldValues['status'], $report->status, Auth::user()));
        }

        return redirect()->route('admin.reports')->with('success', 'Laporan berhasil diperbarui.');
    }

    public function deleteReport($id)
    {
        $report = Report::findOrFail($id);

        \App\Models\AuditLog::record(
            $report,
            'admin_report_deleted',
            [
                'ticket_no' => $report->ticket_no,
                'title' => $report->title,
                'status' => $report->status,
                'department_id' => $report->department_id,
            ]
        );

        $report->delete();

        return redirect()->route('admin.reports')->with('success', 'Laporan berhasil dihapus.');
    }

    public function downloadReport($id)
    {
        $report = Report::with(['user', 'department', 'assignedUser'])->findOrFail($id);

        // Check if ZipArchive is available
        if (! class_exists('ZipArchive')) {
            return $this->downloadReportAsJson($report);
        }

        try {
            $zip = new \ZipArchive;
            $zipPath = storage_path('app/temp/report_'.$report->id.'_'.time().'.zip');
            if (! is_dir(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return back()->with('error', 'Gagal membuat arsip unduhan.');
            }

            $metadata = [
                'id' => $report->id,
                'title' => $report->title,
                'description' => $report->description,
                'category' => $report->category,
                'status' => $report->status,
                'priority' => $report->priority,
                'department' => optional($report->department)->name,
                'location' => $report->location,
                'created_at' => (string) $report->created_at,
                'updated_at' => (string) $report->updated_at,
                'user' => $report->user ? ['id' => $report->user->id, 'name' => $report->user->name, 'email' => $report->user->email] : null,
                'assigned_to' => $report->assignedUser ? ['id' => $report->assignedUser->id, 'name' => $report->assignedUser->name] : null,
            ];
            $zip->addFromString('report.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if (is_array($report->attachments)) {
                foreach ($report->attachments as $relPath) {
                    $abs = Attachments::path($relPath);
                    if ($abs) {
                        $zip->addFile($abs, 'attachments/'.basename($relPath));
                    }
                }
            }

            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Zip creation failed: '.$e->getMessage());

            return $this->downloadReportAsJson($report);
        }
    }

    /**
     * Download report as JSON (fallback when ZipArchive is not available)
     */
    private function downloadReportAsJson($report)
    {
        $metadata = [
            'id' => $report->id,
            'title' => $report->title,
            'description' => $report->description,
            'category' => $report->category,
            'status' => $report->status,
            'priority' => $report->priority,
            'department' => optional($report->department)->name,
            'location' => $report->location,
            'created_at' => (string) $report->created_at,
            'updated_at' => (string) $report->updated_at,
            'user' => $report->user ? ['id' => $report->user->id, 'name' => $report->user->name, 'email' => $report->user->email] : null,
            'assigned_to' => $report->assignedUser ? ['id' => $report->assignedUser->id, 'name' => $report->assignedUser->name] : null,
            'attachments' => $report->attachments ?? [],
        ];

        $filename = 'report_'.$report->ticket_no.'_'.date('Y-m-d_H-i-s').'.json';

        return response()->json($metadata)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Content-Type', 'application/json');
    }

    public function sendReportToHead($id)
    {
        $report = Report::findOrFail($id);
        if (! $report->department_id) {
            return back()->with('error', 'Laporan belum memiliki departemen.');
        }

        $head = User::where('role', 'department_head')->where('department_id', $report->department_id)->first();
        if (! $head) {
            return back()->with('error', 'Tidak ditemukan kepala departemen untuk laporan ini.');
        }

        // Use WorkflowService for proper assignment
        $workflowService = app(\App\Services\WorkflowService::class);
        $workflowService->assignReport($report, $head, Auth::user(), 'Dikirim ke Kepala Departemen oleh Admin');

        return back()->with('success', 'Laporan berhasil dikirim ke Kepala Departemen: '.$head->name.'.');
    }

    public function confirmComplaint($id)
    {
        $complaint = Complaint::findOrFail($id);
        $oldStatus = $complaint->status;
        $complaint->update([
            'status' => 'pending',
            'last_activity_at' => now(),
        ]);

        \App\Models\AuditLog::record($complaint, 'complaint_confirmed', ['status' => $oldStatus], ['status' => 'pending']);

        return redirect()->back()->with('success', 'Keluhan berhasil dikonfirmasi.');
    }

    public function assignComplaint(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $complaint = Complaint::findOrFail($id);
        $assignedTo = User::where('id', $request->assigned_to)->where('is_active', true)->firstOrFail();

        \App\Models\Assignment::where('assignable_type', Complaint::class)
            ->where('assignable_id', $complaint->id)
            ->where('status', 'active')
            ->update([
                'status' => 'reassigned',
                'completed_at' => now(),
            ]);

        \App\Models\Assignment::create([
            'assignable_id' => $complaint->id,
            'assignable_type' => Complaint::class,
            'assigned_to' => $assignedTo->id,
            'assigned_by' => Auth::id(),
            'notes' => $request->notes,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $oldAssigned = $complaint->assigned_to;
        $complaint->update([
            'assigned_to' => $assignedTo->id,
            'status' => 'investigating',
            'last_activity_at' => now(),
        ]);

        \App\Models\AuditLog::record($complaint, 'complaint_assigned', ['assigned_to' => $oldAssigned], ['assigned_to' => $assignedTo->id, 'status' => 'investigating']);

        return redirect()->back()->with('success', 'Keluhan berhasil ditugaskan ke '.$assignedTo->name.'.');
    }

    public function editComplaint($id)
    {
        $complaint = Complaint::findOrFail($id);
        $departments = Department::all();
        $staff = User::where('role', 'staff')->get();

        return view('admin.complaints_edit', compact('complaint', 'departments', 'staff'));
    }

    public function updateComplaint(Request $request, $id)
    {
        $complaint = Complaint::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category' => 'sometimes|required|string|max:100',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'location' => 'sometimes|nullable|string|max:255',
            'status' => ['nullable', \Illuminate\Validation\Rule::in($complaint->allowedStatuses())],
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        $oldValues = $complaint->only(array_keys($validated));

        if (array_key_exists('status', $validated) && $validated['status'] === null) {
            unset($validated['status']);
        }
        $complaint->update($validated + ['last_activity_at' => now()]);

        if ($request->status === 'resolved' && ! $complaint->resolved_at) {
            $complaint->update(['resolved_at' => now()]);
        }

        \App\Models\AuditLog::record($complaint, 'admin_complaint_updated', $oldValues, $complaint->only(array_keys($validated)));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'complaint' => $complaint]);
        }

        return back()->with('success', 'Status keluhan berhasil diperbarui.');
    }

    public function deleteComplaint($id)
    {
        $complaint = Complaint::findOrFail($id);

        \App\Models\AuditLog::record(
            $complaint,
            'admin_complaint_deleted',
            [
                'ticket_no' => $complaint->ticket_no,
                'title' => $complaint->title,
                'status' => $complaint->status,
                'department_id' => $complaint->department_id,
            ]
        );

        $complaint->delete();

        return redirect()->route('admin.complaints')->with('success', 'Keluhan berhasil dihapus.');
    }
}
