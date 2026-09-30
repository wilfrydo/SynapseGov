<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public (no login) transparency pages: service performance per OPD and ticket tracking.
 * Only aggregate numbers and ticket status are exposed; report content and reporter identity never are.
 */
class PublicTransparencyController extends Controller
{
    private const COMPLETED = ['resolved', 'closed'];

    public function landing()
    {
        // Short cache: the landing page is public, but judges/citizens should still see fresh numbers
        $transparency = Cache::remember('public.transparency', now()->addMinute(), fn () => $this->performanceStats());

        return view('landing', compact('transparency'));
    }

    public function track(Request $request)
    {
        $ticketNo = strtoupper(trim((string) $request->query('tiket', '')));
        $ticket = null;
        $error = null;

        if ($ticketNo !== '') {
            if (! preg_match('/^(RPT|CMP)-\d{8}-[A-Z0-9]{6}$/', $ticketNo)) {
                $error = 'Format nomor tiket tidak valid. Contoh: RPT-20261001-AB12CD.';
            } else {
                $model = str_starts_with($ticketNo, 'RPT-') ? Report::class : Complaint::class;
                $ticket = $model::with('department:id,name')->where('ticket_no', $ticketNo)->first();
                $error = $ticket ? null : 'Nomor tiket tidak ditemukan. Periksa kembali penulisannya.';
            }
        }

        return view('public.track', [
            'ticketNo' => $ticketNo,
            'ticket' => $ticket ? $this->publicTicket($ticket) : null,
            'error' => $error,
        ]);
    }

    /**
     * Per-OPD report counts plus city-wide totals. Plain CASE/SUM SQL so it runs on SQLite and MySQL.
     */
    private function performanceStats(): array
    {
        $rows = Report::query()
            ->selectRaw("
                department_id,
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status IN ('resolved', 'closed') AND resolved_at IS NOT NULL
                         AND sla_due_at IS NOT NULL AND resolved_at <= sla_due_at THEN 1 ELSE 0 END) as on_time
            ")
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->get()
            ->keyBy('department_id');

        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            ->map(function ($department) use ($rows) {
                $row = $rows->get($department->id);

                return $this->summarize($department->name, [
                    'total' => (int) ($row->total ?? 0),
                    'completed' => (int) ($row->completed ?? 0),
                    'rejected' => (int) ($row->rejected ?? 0),
                    'on_time' => (int) ($row->on_time ?? 0),
                ]);
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $totals = collect(['total', 'completed', 'rejected', 'on_time'])
            ->mapWithKeys(fn ($key) => [$key => (int) $rows->sum($key)])
            ->all();

        return [
            'overall' => $this->summarize('Semua OPD', $totals),
            'departments' => $departments,
            'generated_at' => now()->translatedFormat('d M Y, H:i'),
        ];
    }

    private function summarize(string $name, array $counts): array
    {
        // Rejected reports never enter the service pipeline, so they are excluded from the completion rate
        $eligible = $counts['total'] - $counts['rejected'];

        return $counts + [
            'name' => $name,
            'in_progress' => max($eligible - $counts['completed'], 0),
            'completion_rate' => $eligible > 0 ? (int) round($counts['completed'] / $eligible * 100) : null,
            'on_time_rate' => $counts['completed'] > 0 ? (int) round($counts['on_time'] / $counts['completed'] * 100) : null,
        ];
    }

    /**
     * The only fields a person holding the ticket number may see without logging in.
     */
    private function publicTicket(Report|Complaint $ticket): array
    {
        $completed = in_array($ticket->status, self::COMPLETED, true);
        $slaState = match (true) {
            ! $ticket->sla_due_at || $ticket->status === 'rejected' => null,
            $completed => ($ticket->resolved_at && $ticket->resolved_at->lte($ticket->sla_due_at)) ? 'on_time' : 'late',
            default => $ticket->sla_due_at->isPast() ? 'overdue' : 'within',
        };

        return [
            'ticket_no' => $ticket->ticket_no,
            'type' => $ticket instanceof Report ? 'Laporan' : 'Keluhan',
            'department' => $ticket->department?->name ?? '-',
            'category' => $ticket->category,
            'status' => $ticket->status,
            'status_label' => Report::statusLabel($ticket->status),
            'step' => $this->progressStep($ticket->status),
            'created_at' => $ticket->created_at?->translatedFormat('d M Y, H:i'),
            'updated_at' => ($ticket->last_activity_at ?? $ticket->updated_at)?->translatedFormat('d M Y, H:i'),
            'sla_due_at' => $ticket->sla_due_at?->translatedFormat('d M Y, H:i'),
            'sla_state' => $slaState,
        ];
    }

    /**
     * Collapse the internal workflow into four public milestones: 1 diterima, 2 diverifikasi, 3 ditangani, 4 selesai.
     */
    private function progressStep(string $status): int
    {
        return match ($status) {
            'submitted', 'pending', 'awaiting_info' => 1,
            'verified' => 2,
            'assigned', 'in_progress', 'reviewed', 'needs_revision', 'awaiting_admin_approval', 'investigating' => 3,
            'resolved', 'closed' => 4,
            default => 0, // rejected
        };
    }
}
