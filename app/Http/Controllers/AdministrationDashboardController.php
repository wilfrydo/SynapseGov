<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdministrationDashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->isDepartmentHead()) {
            return app(DepartmentHeadWorkspaceController::class)->index();
        }
        $user = Auth::user();
        $department = $user->department;

        if (! $department) {
            return redirect()->route('home')->with('error', 'Akun Anda belum terhubung dengan departemen manapun.');
        }

        $today = now()->toDateString(); // dikirim sebagai binding agar portabel SQLite/MySQL

        // Statistik departemen
        if ($user->role === 'department_head') {
            $repStat = Report::where('department_id', $department->id)
                ->selectRaw("
                    COUNT(*) as total_reports,
                    COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_reports,
                    COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_reports,
                    COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_reports,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END), 0) as today_reports,
                    COALESCE(SUM(CASE WHEN DATE(resolved_at) = ? THEN 1 ELSE 0 END), 0) as completed_today,
                    COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending', 'verified') THEN 1 ELSE 0 END), 0) as pending_action
                ", [$today, $today])->first();

            $compStat = Complaint::where('department_id', $department->id)
                ->selectRaw("
                    COUNT(*) as total_complaints,
                    COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_complaints,
                    COALESCE(SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END), 0) as investigating_complaints,
                    COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_complaints
                ")->first();
        } else {
            // Statistik untuk staff - hanya laporan yang ditugaskan kepada mereka
            $repStat = Report::where('assigned_to', $user->id)
                ->selectRaw("
                    COUNT(*) as total_reports,
                    COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_reports,
                    COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_reports,
                    COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_reports,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END), 0) as today_reports,
                    COALESCE(SUM(CASE WHEN DATE(resolved_at) = ? THEN 1 ELSE 0 END), 0) as completed_today,
                    COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending', 'verified') THEN 1 ELSE 0 END), 0) as pending_action
                ", [$today, $today])->first();

            $compStat = Complaint::where('assigned_to', $user->id)
                ->selectRaw("
                    COUNT(*) as total_complaints,
                    COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_complaints,
                    COALESCE(SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END), 0) as investigating_complaints,
                    COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_complaints
                ")->first();
        }

        $stats = [
            'total_reports' => (int) ($repStat->total_reports ?? 0),
            'pending_reports' => (int) ($repStat->pending_reports ?? 0),
            'in_progress_reports' => (int) ($repStat->in_progress_reports ?? 0),
            'resolved_reports' => (int) ($repStat->resolved_reports ?? 0),
            'total_complaints' => (int) ($compStat->total_complaints ?? 0),
            'pending_complaints' => (int) ($compStat->pending_complaints ?? 0),
            'investigating_complaints' => (int) ($compStat->investigating_complaints ?? 0),
            'resolved_complaints' => (int) ($compStat->resolved_complaints ?? 0),
            'today_reports' => (int) ($repStat->today_reports ?? 0),
            'completed_today' => (int) ($repStat->completed_today ?? 0),
            'pending_action' => (int) ($repStat->pending_action ?? 0),
        ];

        // Laporan departemen
        if ($user->role === 'department_head') {
            $departmentReports = Report::with(['user', 'assignedUser'])
                ->where(function ($q) use ($department, $user) {
                    $q->where('department_id', $department->id)
                        ->orWhere('assigned_to', $user->id);
                })
                ->latest()
                ->limit(10)
                ->get();
        } else {
            // Staff melihat laporan yang ditugaskan kepada mereka + laporan departemen
            $departmentReports = Report::with(['user', 'assignedUser'])
                ->where(function ($query) use ($user, $department) {
                    $query->where('department_id', $department->id)
                        ->orWhere('assigned_to', $user->id);
                })
                ->latest()
                ->limit(10)
                ->get();
        }

        // Keluhan departemen
        $departmentComplaints = Complaint::with(['user', 'assignedUser'])
            ->where('department_id', $department->id)
            ->latest()
            ->limit(10)
            ->get();

        // Staff departemen
        $departmentStaff = User::where('department_id', $department->id)
            ->where('role', '!=', 'citizen')
            ->get();

        return view('administration.modern-dashboard', compact(
            'stats',
            'department',
            'departmentReports',
            'departmentComplaints',
            'departmentStaff'
        ));
    }

    public function reports(Request $request)
    {
        if (Auth::user()->isDepartmentHead()) {
            return app(DepartmentHeadWorkspaceController::class)->tickets($request, 'reports');
        }
        $user = Auth::user();
        $perPage = $user->getSettings('items_per_page', 15);

        // Laporan yang dapat diakses oleh Staff
        $query = Report::with(['user', 'assignedUser', 'department'])
            ->where(function ($q) use ($user) {
                if ($user->department_id) {
                    $q->where('department_id', $user->department_id)
                        ->orWhere('assigned_to', $user->id);
                } else {
                    $q->where('assigned_to', $user->id);
                }
            });

        // Filter pencarian
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('ticket_no', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->where('name', 'like', $term);
                    });
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $reports = $query->latest()->paginate($perPage)->appends(request()->query());

        // Ambil daftar staff untuk assignment dropdown
        $staffList = User::where('department_id', $user->department_id)
            ->where('role', 'staff')
            ->where('id', '!=', $user->id)
            ->get();

        return view('administration.reports', compact('reports', 'staffList'));
    }

    public function complaints(Request $request)
    {
        if (Auth::user()->isDepartmentHead()) {
            return app(DepartmentHeadWorkspaceController::class)->tickets($request, 'complaints');
        }
        $user = Auth::user();
        $perPage = $user->getSettings('items_per_page', 15);
        $query = Complaint::with(['user', 'assignedUser', 'department'])
            ->where('department_id', $user->department_id);

        // Filter pencarian
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('ticket_no', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->where('name', 'like', $term);
                    });
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $complaints = $query->latest()->paginate($perPage)->appends(request()->query());

        $staffList = User::where('department_id', $user->department_id)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->get();

        return view('administration.complaints', compact('complaints', 'staffList'));
    }

    public function staff()
    {
        if (Auth::user()->isDepartmentHead()) {
            return app(DepartmentHeadWorkspaceController::class)->staff(request());
        }
        $user = Auth::user();
        $perPage = $user->getSettings('items_per_page', 15);
        $staff = User::where('department_id', $user->department_id)
            ->where('role', '!=', 'citizen')
            ->paginate($perPage);

        return view('administration.staff', compact('staff'));
    }

    public function assignReport(Request $request, $id)
    {
        $user = Auth::user();
        if (! in_array($user->role, ['department_head', 'staff'])) {
            abort(403, 'Anda tidak berhak menugaskan laporan ini.');
        }

        $request->validate([
            'assigned_to' => [
                'required',
                'exists:users,id',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($query) use ($user) {
                    $query->where('department_id', $user->department_id)->where('is_active', true);
                }),
            ],
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($id, $request, $user) {
            $report = Report::where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            })->lockForUpdate()->findOrFail($id);

            $assignedTo = User::findOrFail($request->assigned_to);

            $workflowService = app(\App\Services\WorkflowService::class);
            $workflowService->assignReport($report, $assignedTo, $user, $request->notes);

            return redirect()->back()->with('success', 'Laporan berhasil ditugaskan ke '.$assignedTo->name);
        });
    }

    public function assignComplaint(Request $request, $id)
    {
        $user = Auth::user();
        if (! in_array($user->role, ['department_head', 'staff'])) {
            abort(403, 'Anda tidak berhak menugaskan keluhan ini.');
        }

        $request->validate([
            'assigned_to' => [
                'required',
                'exists:users,id',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($query) use ($user) {
                    $query->where('department_id', $user->department_id)->where('is_active', true);
                }),
            ],
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($id, $request, $user) {
            $complaint = Complaint::where('department_id', $user->department_id)->lockForUpdate()->findOrFail($id);

            $assignedTo = User::findOrFail($request->assigned_to);

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
                'assigned_by' => $user->id,
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

            AuditLog::record($complaint, 'complaint_assigned', ['assigned_to' => $oldAssigned], ['assigned_to' => $assignedTo->id, 'status' => 'investigating'], $user);

            return redirect()->back()->with('success', 'Keluhan berhasil ditugaskan ke '.$assignedTo->name);
        });
    }

    public function downloadReport($id)
    {
        $user = Auth::user();
        $report = Report::with(['user', 'department', 'assignedUser'])
            ->where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            })
            ->findOrFail($id);

        if (! class_exists('ZipArchive')) {
            return back()->with('error', 'Ekstensi ZipArchive PHP tidak terpasang di server.');
        }

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
    }

    /**
     * Staff or Department Head confirms back to admin after completing actions or reviewing.
     */
    public function confirmToAdmin($id)
    {
        $user = Auth::user();
        if (! in_array($user->role, ['staff', 'department_head'])) {
            return back()->with('error', 'Hanya staff atau Kepala Departemen yang berhak mengonfirmasi laporan ini ke admin.');
        }

        return DB::transaction(function () use ($id, $user) {
            $report = Report::where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            })->lockForUpdate()->findOrFail($id);

            if ((int) $report->assigned_to !== (int) $user->id && (int) $report->department_id !== (int) $user->department_id) {
                return back()->with('error', 'Anda tidak berhak mengonfirmasi laporan ini ke admin.');
            }

            // Status guard: allow valid active and approval statuses
            $allowedStatuses = ['reviewed', 'in_progress', 'assigned', 'needs_revision', 'verified', 'awaiting_admin_approval'];
            if (! in_array($report->status, $allowedStatuses)) {
                return back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat dikonfirmasi ke admin.');
            }

            app(\App\Services\WorkflowService::class)
                ->submitForApproval($report, $user, request('completion_notes', $report->completion_notes));

            return back()->with('success', 'Laporan telah dikonfirmasi ke admin untuk persetujuan akhir.');
        });
    }

    /**
     * Resolve a complaint (for department head or staff)
     */
    public function resolveComplaint(Request $request, $id)
    {
        $user = Auth::user();
        if (! in_array($user->role, ['department_head', 'staff'])) {
            abort(403, 'Anda tidak berhak menyelesaikan keluhan ini.');
        }

        $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        return DB::transaction(function () use ($id, $request, $user) {
            $complaint = Complaint::where('department_id', $user->department_id)->lockForUpdate()->findOrFail($id);

            // Only a complaint that is being handled can be closed out (see Complaint::STATUS_TRANSITIONS)
            if (! $complaint->canBeResolved()) {
                return redirect()->back()->with('error', 'Keluhan harus ditugaskan dan diinvestigasi terlebih dahulu sebelum diselesaikan.');
            }

            $oldStatus = $complaint->status;
            $complaint->update([
                'status' => 'resolved',
                'resolution_notes' => $request->resolution_notes,
                'resolved_at' => now(),
                'last_activity_at' => now(),
            ]);

            \App\Models\Assignment::where('assignable_type', Complaint::class)
                ->where('assignable_id', $complaint->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            AuditLog::record($complaint, 'complaint_resolved', ['status' => $oldStatus], ['status' => 'resolved', 'resolution_notes' => $request->resolution_notes], $user);

            return redirect()->back()->with('success', 'Keluhan berhasil diselesaikan.');
        });
    }
}
