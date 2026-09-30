<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Report;
use App\Models\User;
use App\Services\WorkflowService;
use App\Support\Attachments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkflowManagementController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Get the authenticated user.
     */
    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    /**
     * Admin assigns report to staff
     */
    public function adminAssignToStaff(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        if (! $user->isAdmin()) {
            return back()->with('error', 'Hanya admin yang dapat menugaskan laporan ke staff.');
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($id, $request, $user) {
            $report = Report::lockForUpdate()->findOrFail($id);
            $assignedTo = User::findOrFail($request->assigned_to);

            // Verify the assigned user is staff or department head
            if (! $assignedTo->isStaff() && ! $assignedTo->isDepartmentHead()) {
                return back()->with('error', 'User yang dipilih bukan staff atau kepala departemen.');
            }

            // Use WorkflowService for proper assignment
            $this->workflowService->assignReport($report, $assignedTo, $user, $request->notes);

            return back()->with('success', 'Laporan berhasil ditugaskan ke: '.$assignedTo->name.'.');
        });
    }

    /**
     * Admin assigns report to department head
     */
    public function adminAssignToHead(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        if (! $user->isAdmin()) {
            return back()->with('error', 'Hanya admin yang dapat menugaskan laporan ke kepala departemen.');
        }

        return DB::transaction(function () use ($id, $user) {
            $report = Report::lockForUpdate()->findOrFail($id);

            if (! $report->department_id) {
                return back()->with('error', 'Laporan belum memiliki departemen.');
            }

            $head = User::where('role', 'department_head')
                ->where('department_id', $report->department_id)
                ->first();

            if (! $head) {
                return back()->with('error', 'Tidak ditemukan kepala departemen untuk laporan ini.');
            }

            // Use WorkflowService for proper assignment
            $this->workflowService->assignReport($report, $head, $user, 'Dikirim ke Kepala Departemen oleh Admin');

            return back()->with('success', 'Laporan berhasil dikirim ke Kepala Departemen: '.$head->name.'.');
        });
    }

    /**
     * Staff confirms and forwards to department head
     */
    public function staffConfirmAndForward($id)
    {
        $user = $this->user();

        return DB::transaction(function () use ($id, $user) {
            $report = Report::where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            })->lockForUpdate()->findOrFail($id);

            // Authorization: assigned staff or staff in department
            if ($user->role === 'staff' && (int) $report->assigned_to !== (int) $user->id && (int) $report->department_id !== (int) $user->department_id) {
                return back()->with('error', 'Anda tidak berhak mengirim laporan ini. Laporan belum ditugaskan kepada Anda.');
            }

            // Status guard: only allowed statuses can be forwarded
            $allowedStatuses = ['submitted', 'pending', 'verified', 'assigned', 'needs_revision'];
            if (! in_array($report->status, $allowedStatuses)) {
                return back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat diteruskan ke Kepala Departemen.');
            }

            // Initial verification belongs to admin (or the department head); staff may only forward verified work
            if ($user->isStaff() && in_array($report->status, ['submitted', 'pending'])) {
                return back()->with('error', 'Laporan harus diverifikasi Admin terlebih dahulu sebelum diteruskan.');
            }

            // If still submitted/pending, verify first via WorkflowService
            if (in_array($report->status, ['submitted', 'pending'])) {
                $this->workflowService->verifyReport($report, $user);

                // Audit: confirmed by staff
                AuditLog::record($report, 'confirmed_by_staff', null, ['status' => 'verified'], $user);
            }

            // Then forward to head
            $targetDeptId = $report->department_id ?: $user->department_id;
            $head = User::where('role', 'department_head')
                ->where('department_id', $targetDeptId)
                ->first();

            if (! $head) {
                return back()->with('error', 'Tidak ditemukan kepala departemen.');
            }

            $this->workflowService->assignReport($report, $head, $user, 'Dikonfirmasi dan diteruskan ke Kepala Departemen');

            return back()->with('success', 'Laporan dikonfirmasi dan dikirim ke Kepala Departemen: '.$head->name.'.');
        });
    }

    /**
     * Department head reviews and returns to staff
     */
    public function headReviewAndReturn(Request $request, $id)
    {
        $user = $this->user();

        if ($user->role !== 'department_head') {
            return back()->with('error', 'Hanya Kepala Departemen yang dapat mengembalikan laporan ke staff.');
        }

        $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $assignedTo = User::findOrFail($request->assigned_to);

        // Verify the assigned user is staff of the head's own department (multi-OPD isolation)
        if (! $assignedTo->isStaff()) {
            return back()->with('error', 'User yang dipilih bukan staff.');
        }

        if ((int) $assignedTo->department_id !== (int) $user->department_id) {
            return back()->with('error', 'Staff yang dipilih bukan bagian dari departemen Anda.');
        }

        return DB::transaction(function () use ($id, $assignedTo, $user, $request) {
            $report = Report::where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            })->lockForUpdate()->findOrFail($id);

            // Status guard
            if (! in_array($report->status, ['assigned', 'in_progress', 'verified', 'reviewed', 'awaiting_admin_approval', 'needs_revision'])) {
                return back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat dikembalikan ke staff.');
            }

            $oldStatus = $report->status;
            $newStatus = in_array($oldStatus, ['awaiting_admin_approval', 'reviewed']) ? 'needs_revision' : 'reviewed';

            $this->workflowService->assignReport($report, $assignedTo, $user, $request->notes ?: 'Dikembalikan ke staff untuk tindak lanjut', $newStatus);

            // Audit: reviewed by head
            AuditLog::record($report, 'reviewed_by_head', ['status' => $oldStatus], ['status' => $newStatus, 'assigned_to' => $assignedTo->id], $user);

            return back()->with('success', 'Laporan dikembalikan ke staff: '.$assignedTo->name.' untuk tindak lanjut.');
        });
    }

    /**
     * Staff confirms completion to admin
     */
    public function staffConfirmToAdmin(Request $request, $id)
    {
        $user = $this->user();

        $request->validate([
            'completion_notes' => 'required|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip|max:5120',
        ]);

        return DB::transaction(function () use ($id, $user, $request) {
            $report = Report::where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            })->lockForUpdate()->findOrFail($id);

            if ($user->role !== 'staff' || ((int) $report->assigned_to !== (int) $user->id && (int) $report->department_id !== (int) $user->department_id)) {
                return back()->with('error', 'Anda tidak berhak mengonfirmasi laporan ini ke admin.');
            }

            // Status guard: only allow if not already resolved, closed, or rejected
            $allowedStatuses = ['reviewed', 'in_progress', 'assigned', 'needs_revision', 'verified'];
            if (! in_array($report->status, $allowedStatuses)) {
                return back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat dikonfirmasi ke admin.');
            }

            // Handle evidence attachments
            $newAttachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $newAttachments[] = Attachments::store($file, 'resolutions');
                }
            }

            $this->workflowService->submitForApproval($report, $user, $request->completion_notes, $newAttachments);

            return back()->with('success', 'Laporan telah dikonfirmasi ke admin untuk persetujuan akhir.');
        });
    }

    /**
     * Admin approves and closes report
     */
    public function adminApproveAndClose(Request $request, $id)
    {
        $user = $this->user();

        if (! $user->isAdmin()) {
            return back()->with('error', 'Hanya admin yang dapat menyetujui dan menutup laporan.');
        }

        $request->validate([
            'final_notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($id, $user, $request) {
            $report = Report::lockForUpdate()->findOrFail($id);

            // Status guard: only work submitted by staff can be approved (see Report::STATUS_TRANSITIONS)
            if ($report->status !== 'awaiting_admin_approval') {
                return back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat disetujui.');
            }

            $oldStatus = $report->status;

            // Close any active assignments
            $report->assignments()->where('status', 'active')->update([
                'status' => 'completed',
                'completed_at' => now(),
                'notes' => $request->final_notes,
            ]);

            $report->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'final_notes' => $request->final_notes,
                'last_activity_at' => now(),
            ]);

            // Audit: approved by admin
            AuditLog::record(
                $report,
                'approved_by_admin',
                ['status' => $oldStatus],
                [
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'final_notes' => $request->final_notes,
                ],
                $user
            );

            // Fire event for status change
            event(new \App\Events\ReportStatusChanged($report, $oldStatus, 'resolved', $user));

            return back()->with('success', 'Laporan disetujui dan berstatus Selesai. Pelapor akan diminta mengonfirmasi penyelesaian.');
        });
    }

    /**
     * Admin rejects report back to staff
     */
    public function adminRejectToStaff(Request $request, $id)
    {
        $user = $this->user();

        if (! $user->isAdmin()) {
            return back()->with('error', 'Hanya admin yang dapat menolak laporan.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $assignedTo = User::findOrFail($request->assigned_to);

        // Revision work can only go back to field staff, never to a citizen or another role
        if (! $assignedTo->isStaff()) {
            return back()->with('error', 'User yang dipilih bukan staff.');
        }

        return DB::transaction(function () use ($id, $assignedTo, $user, $request) {
            $report = Report::lockForUpdate()->findOrFail($id);

            // Status guard
            if (! in_array($report->status, ['awaiting_admin_approval', 'reviewed'])) {
                return back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat ditolak.');
            }

            $oldStatus = $report->status;

            $this->workflowService->assignReport($report, $assignedTo, $user, $request->rejection_reason, 'needs_revision');

            // Audit: rejected by admin
            AuditLog::record(
                $report,
                'rejected_by_admin',
                ['status' => $oldStatus],
                [
                    'status' => 'needs_revision',
                    'assigned_to' => $assignedTo->id,
                    'rejection_reason' => $request->rejection_reason,
                ],
                $user
            );

            return back()->with('success', 'Laporan ditolak dan dikembalikan ke staff: '.$assignedTo->name.'.');
        });
    }
}
