<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\Report;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
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
