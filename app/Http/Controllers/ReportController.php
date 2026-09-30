<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = auth()->check() ? auth()->user()->getSettings('items_per_page', 20) : 20;
        $reports = Report::with(['user', 'department', 'assignedUser'])
            ->latest()
            ->paginate($perPage);

        return view('admin.reports', compact('reports'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->get();

        return view('citizen.reports.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
        ], auth()->user());

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Laporan berhasil dikirim. Nomor tiket: '.$report->ticket_no);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $report = Report::with(['user', 'department', 'assignedUser'])->findOrFail($id);

        // Check if user can view this report
        $user = auth()->user();
        if ($user->role === 'citizen' && (int) $report->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized access to report.');
        }
        if (in_array($user->role, ['staff', 'department_head']) && (int) $report->department_id !== (int) $user->department_id && (int) $report->assigned_to !== (int) $user->id) {
            abort(403, 'Unauthorized access to report from another department.');
        }

        return view('citizen.reports.show', compact('report'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $report = Report::findOrFail($id);
        $departments = Department::all();
        $staff = User::where('role', 'staff')->get();

        return view('admin.reports_edit', compact('report', 'departments', 'staff'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'status' => 'required|in:submitted,pending,verified,assigned,in_progress,awaiting_info,needs_revision,resolved,closed,rejected',
            'priority' => 'required|in:low,medium,high,urgent',
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'assigned_to' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
        ]);

        $report = Report::findOrFail($id);
        $oldData = $report->toArray();
        $oldStatus = $report->status;

        $updateData = $request->only([
            'title', 'description', 'category', 'status', 'priority', 'department_id', 'assigned_to', 'location',
        ]);

        // Recalculate SLA due date if priority changed
        if (isset($updateData['priority']) && $updateData['priority'] !== $report->priority) {
            $report->priority = $updateData['priority'];
            $updateData['sla_due_at'] = $report->calculateSLADueDate();
        }

        $report->update($updateData);

        // Audit log for direct admin update
        AuditLog::create([
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'user_id' => auth()->id(),
            'event' => 'updated_by_admin',
            'old_values' => $oldData,
            'new_values' => $report->fresh()->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Fire status changed event if status was changed
        if ($oldStatus !== $report->status) {
            event(new \App\Events\ReportStatusChanged($report, $oldStatus, $report->status, auth()->user()));
        }

        return redirect()->route('admin.reports')->with('success', 'Laporan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $report = Report::findOrFail($id);

        // Clean up file attachments
        if (! empty($report->attachments) && is_array($report->attachments)) {
            foreach ($report->attachments as $attachment) {
                Attachments::delete($attachment);
            }
        }

        // Audit log before delete
        AuditLog::create([
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'user_id' => auth()->id(),
            'event' => 'deleted_by_admin',
            'old_values' => $report->toArray(),
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $report->delete();

        return redirect()->route('admin.reports')->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * API endpoint for report statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 'submitted')->count(),
            'verified' => Report::where('status', 'verified')->count(),
            'in_progress' => Report::whereIn('status', ['assigned', 'in_progress', 'awaiting_info'])->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'closed' => Report::where('status', 'closed')->count(),
            'escalated' => Report::where('is_escalated', true)->count(),
            'by_priority' => Report::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority'),
            'by_category' => Report::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
            'by_department' => Report::with('department')
                ->selectRaw('department_id, COUNT(*) as count')
                ->groupBy('department_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->department->name ?? 'Unassigned' => $item->count];
                }),
        ];

        return response()->json($stats);
    }

    /**
     * Get KPI dashboard data
     */
    public function kpiDashboard()
    {
        $kpis = [
            'total_reports' => Report::count(),
            'total_complaints' => Complaint::count(),
            'open_reports' => Report::whereIn('status', ['submitted', 'verified', 'assigned', 'in_progress', 'awaiting_info'])->count(),
            'resolved_reports' => Report::whereIn('status', ['resolved', 'closed'])->count(),
            'escalated_reports' => Report::where('is_escalated', true)->count(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'sla_breach_rate' => $this->getSLABreachRate(),
            'top_categories' => $this->getTopCategories(),
            'department_performance' => $this->getDepartmentPerformance(),
            'monthly_trends' => $this->getMonthlyTrends(),
        ];

        return response()->json($kpis);
    }

    /**
     * Export reports to Excel
     */
    public function export(Request $request)
    {
        $query = Report::with(['user', 'department', 'assignedUser']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Limit to 5000 records to prevent memory exhaustion
        $reports = $query->limit(5000)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'A1' => 'Ticket No',
            'B1' => 'Title',
            'C1' => 'Description',
            'D1' => 'Category',
            'E1' => 'Priority',
            'F1' => 'Status',
            'G1' => 'Department',
            'H1' => 'Assigned To',
            'I1' => 'Created By',
            'J1' => 'Created At',
            'K1' => 'Resolved At',
            'L1' => 'SLA Due',
            'M1' => 'Is Escalated',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Data
        $row = 2;
        foreach ($reports as $report) {
            $sheet->setCellValue('A'.$row, $report->ticket_no);
            $sheet->setCellValue('B'.$row, $report->title);
            $sheet->setCellValue('C'.$row, $report->description);
            $sheet->setCellValue('D'.$row, $report->category);
            $sheet->setCellValue('E'.$row, ucfirst($report->priority));
            $sheet->setCellValue('F'.$row, ucfirst(str_replace('_', ' ', $report->status)));
            $sheet->setCellValue('G'.$row, $report->department?->name ?? 'N/A');
            $sheet->setCellValue('H'.$row, $report->assignedUser?->name ?? 'N/A');
            $sheet->setCellValue('I'.$row, $report->user?->name ?? 'N/A');
            $sheet->setCellValue('J'.$row, $report->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('K'.$row, $report->resolved_at ? $report->resolved_at->format('Y-m-d H:i:s') : 'N/A');
            $sheet->setCellValue('L'.$row, $report->sla_due_at ? $report->sla_due_at->format('Y-m-d H:i:s') : 'N/A');
            $sheet->setCellValue('M'.$row, $report->is_escalated ? 'Yes' : 'No');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'reports_export_'.date('Y-m-d_H-i-s').'.xlsx';
        $filepath = storage_path('app/temp/'.$filename);

        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $writer->save($filepath);

        return response()->download($filepath)->deleteFileAfterSend(true);
    }

    private function getAverageResolutionTime()
    {
        $resolvedReports = Report::whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->get();

        if ($resolvedReports->isEmpty()) {
            return 0;
        }

        $totalHours = $resolvedReports->sum(function ($report) {
            return $report->created_at->diffInHours($report->resolved_at);
        });

        return round($totalHours / $resolvedReports->count(), 2);
    }

    private function getSLABreachRate()
    {
        $totalReports = Report::count();
        if ($totalReports === 0) {
            return 0;
        }

        $breachedReports = Report::where('is_escalated', true)->count();

        return round(($breachedReports / $totalReports) * 100, 2);
    }

    private function getTopCategories()
    {
        return Report::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->pluck('count', 'category');
    }

    private function getDepartmentPerformance()
    {
        return Department::withCount(['reports'])
            ->with(['reports' => function ($query) {
                $query->whereIn('status', ['resolved', 'closed']);
            }])
            ->get()
            ->map(function ($department) {
                $totalReports = $department->reports_count;
                $resolvedReports = $department->reports->count();

                return [
                    'name' => $department->name,
                    'total_reports' => $totalReports,
                    'resolved_reports' => $resolvedReports,
                    'resolution_rate' => $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100, 2) : 0,
                ];
            });
    }

    private function getMonthlyTrends()
    {
        $driver = \DB::connection()->getDriverName();
        $dateExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        return Report::selectRaw("{$dateExpression} as month, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month');
    }
}
