<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CitizenDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Statistik warga - dioptimalkan via aggregate query
        $repStat = Report::where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as my_reports,
                COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending', 'verified') THEN 1 ELSE 0 END), 0) as pending_reports,
                COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_reports,
                COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_reports
            ")->first();

        $compStat = Complaint::where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as my_complaints,
                COALESCE(SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END), 0) as pending_complaints,
                COALESCE(SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END), 0) as investigating_complaints,
                COALESCE(SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END), 0) as resolved_complaints
            ")->first();

        $stats = [
            'my_reports' => (int) ($repStat->my_reports ?? 0),
            'pending_reports' => (int) ($repStat->pending_reports ?? 0),
            'in_progress_reports' => (int) ($repStat->in_progress_reports ?? 0),
            'resolved_reports' => (int) ($repStat->resolved_reports ?? 0),
            'my_complaints' => (int) ($compStat->my_complaints ?? 0),
            'pending_complaints' => (int) ($compStat->pending_complaints ?? 0),
            'investigating_complaints' => (int) ($compStat->investigating_complaints ?? 0),
            'resolved_complaints' => (int) ($compStat->resolved_complaints ?? 0),
        ];

        // Laporan saya
        $myReports = Report::with(['department', 'assignedUser'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        // Keluhan saya
        $myComplaints = Complaint::with(['department', 'assignedUser'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        // Departemen tersedia
        $departments = Department::where('is_active', true)->get();

        // Statistik tambahan untuk dashboard modern
        $pendingCount = $stats['pending_reports'];
        $resolvedCount = $stats['resolved_reports'];

        return view('citizen.modern-dashboard', compact(
            'stats',
            'myReports',
            'myComplaints',
            'departments',
            'pendingCount',
            'resolvedCount'
        ));
    }

    public function createReport()
    {
        $departments = Department::where('is_active', true)->get();

        return view('citizen.reports.create', compact('departments'));
    }

    public function storeReport(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'location' => 'nullable|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip|max:5120',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = Attachments::store($file, 'reports');
            }
        }

        $workflowService = app(\App\Services\WorkflowService::class);
        $report = $workflowService->submitReport([
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'department_id' => $request->department_id,
            'location' => $request->location,
            'priority' => $request->priority,
            'attachments' => $attachments ?: null,
        ], Auth::user());

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Laporan berhasil dikirim. Nomor tiket: '.$report->ticket_no);
    }

    public function createComplaint()
    {
        $departments = Department::where('is_active', true)->get();

        return view('citizen.complaints.create', compact('departments'));
    }

    public function storeComplaint(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'location' => 'nullable|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip|max:5120',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = Attachments::store($file, 'complaints');
            }
        }

        // Create complaint
        $complaint = Complaint::create([
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'department_id' => $request->department_id,
            'location' => $request->location,
            'priority' => $request->priority,
            'user_id' => Auth::id(),
            'status' => 'submitted',
            'attachments' => $attachments ?: null,
        ]);

        // Auto-assign complaint to first admin so it directly enters admin queue
        $firstAdmin = User::where('role', 'admin')->orderBy('id')->first();
        if ($firstAdmin) {
            // Save assignment record for audit trail consistency
            Assignment::create([
                'assignable_id' => $complaint->id,
                'assignable_type' => Complaint::class,
                'assigned_to' => $firstAdmin->id,
                'assigned_by' => Auth::id(),
                'notes' => 'Auto-assigned to first admin on submission',
                'assigned_at' => now(),
            ]);
            $complaint->update([
                'assigned_to' => $firstAdmin->id,
            ]);
        }

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Keluhan berhasil dikirim'.($complaint->ticket_no ? ' • Nomor tiket: '.$complaint->ticket_no : ''));
    }

    public function myReports()
    {
        $perPage = Auth::user()->getSettings('items_per_page', 15);
        $reports = Report::with(['department', 'assignedUser'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate($perPage);

        return view('citizen.reports.index', compact('reports'));
    }

    public function myComplaints()
    {
        $perPage = Auth::user()->getSettings('items_per_page', 15);
        $complaints = Complaint::with(['department', 'assignedUser'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate($perPage);

        return view('citizen.complaints.index', compact('complaints'));
    }

    public function showReport($id)
    {
        $report = Report::with(['department', 'assignedUser'])->findOrFail($id);

        // Use policy to check authorization
        $this->authorize('view', $report);

        return view('citizen.reports.show', compact('report'));
    }

    public function showComplaint($id)
    {
        $complaint = Complaint::with(['department', 'assignedUser'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('citizen.complaints.show', compact('complaint'));
    }
}
