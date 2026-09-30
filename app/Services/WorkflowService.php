<?php

namespace App\Services;

use App\Events\CommentAdded;
use App\Events\ReportAssigned;
use App\Events\ReportStatusChanged;
use App\Events\ReportSubmitted;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    /**
     * Submit a new report
     */
    public function submitReport(array $data, User $user): Report
    {
        return DB::transaction(function () use ($data, $user) {
            $report = Report::create(array_merge($data, [
                'user_id' => $user->id,
                'status' => 'submitted',
            ]));

            // Log the creation
            $this->logAudit($report, 'created', null, $report->toArray());

            // Fire submitted event
            event(new ReportSubmitted($report));

            // Auto-assign to least loaded admin without overriding 'submitted' status
            $adminUser = User::where('role', 'admin')
                ->withCount(['assignments' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->orderBy('assignments_count', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            if ($adminUser) {
                Assignment::create([
                    'assignable_id' => $report->id,
                    'assignable_type' => Report::class,
                    'assigned_to' => $adminUser->id,
                    'assigned_by' => $user->id,
                    'notes' => 'Auto-assigned to admin on submission (workload-balanced)',
                    'assigned_at' => now(),
                    'status' => 'active',
                ]);

                $report->update([
                    'assigned_to' => $adminUser->id,
                    'status' => 'submitted',
                    'reassign_count' => 0,
                ]);
            }

            return $report;
        });
    }

    /**
     * Verify a report (admin action)
     */
    public function verifyReport(Report $report, User $admin, array $data = []): Report
    {
        return DB::transaction(function () use ($report, $admin, $data) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            $updateData = array_merge([
                'status' => 'verified',
            ], $data);

            // Recalculate SLA if priority changed
            if (isset($data['priority']) && $data['priority'] !== $lockedReport->priority) {
                $lockedReport->priority = $data['priority'];
                $updateData['sla_due_at'] = $lockedReport->calculateSLADueDate();
            }

            $lockedReport->update($updateData);

            // Assign queue number on first verification
            if (empty($lockedReport->queue_no)) {
                $lockedReport->update([
                    'queue_no' => Report::nextQueueNo(),
                ]);
            }

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'verified', $oldData, $lockedReport->toArray(), $admin);

            // Fire event
            event(new ReportStatusChanged($lockedReport, $oldStatus, 'verified', $admin));

            return $lockedReport;
        });
    }

    /**
     * Reject a report (admin action)
     */
    public function rejectReport(Report $report, User $admin, ?string $reason = null): Report
    {
        return DB::transaction(function () use ($report, $admin, $reason) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            $lockedReport->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'resolution_notes' => $reason,
                'last_activity_at' => now(),
            ]);

            // Close active assignment if rejected
            $lockedReport->assignments()->where('status', 'active')->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'rejected', $oldData, $lockedReport->toArray(), $admin);

            // Fire event
            event(new ReportStatusChanged($lockedReport, $oldStatus, 'rejected', $admin));

            return $lockedReport;
        });
    }

    /**
     * Assign a report to staff or department head
     */
    public function assignReport(Report $report, User $assignedTo, User $assignedBy, ?string $notes = null, string $status = 'assigned'): Assignment
    {
        return DB::transaction(function () use ($report, $assignedTo, $assignedBy, $notes, $status) {
            // Lock the report row to prevent race conditions during concurrent assignments
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            // Check if there was any prior assignment to avoid incrementing reassign_count on first assignment
            $hadPriorAssignment = $lockedReport->assignments()->exists() || ! empty($lockedReport->assigned_to);

            // Deactivate any prior active assignments for this report
            $lockedReport->assignments()->where('status', 'active')->update([
                'status' => 'reassigned',
                'completed_at' => now(),
            ]);

            // Create assignment record
            $assignment = Assignment::create([
                'assignable_id' => $lockedReport->id,
                'assignable_type' => Report::class,
                'assigned_to' => $assignedTo->id,
                'assigned_by' => $assignedBy->id,
                'notes' => $notes,
                'assigned_at' => now(),
                'status' => 'active',
            ]);

            // Update report
            $lockedReport->update([
                'assigned_to' => $assignedTo->id,
                'status' => $status,
                'reassign_count' => $hadPriorAssignment ? (($lockedReport->reassign_count ?? 0) + 1) : 0,
                'last_activity_at' => now(),
            ]);

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'assigned', $oldData, $lockedReport->toArray(), $assignedBy);

            // Fire event
            event(new ReportAssigned($lockedReport, $assignedTo, $assignedBy));

            if ($oldStatus !== $status) {
                event(new ReportStatusChanged($lockedReport, $oldStatus, $status, $assignedBy));
            }

            return $assignment;
        });
    }

    /**
     * Start working on a report (staff action)
     */
    public function startWork(Report $report, User $staff): Report
    {
        return DB::transaction(function () use ($report, $staff) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            $lockedReport->update([
                'status' => 'in_progress',
                'last_activity_at' => now(),
            ]);

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'started_work', $oldData, $lockedReport->toArray(), $staff);

            // Fire event
            event(new ReportStatusChanged($lockedReport, $oldStatus, 'in_progress', $staff));

            return $lockedReport;
        });
    }

    /**
     * Add a comment to a report
     */
    public function addComment(Report $report, User $user, string $content, bool $isInternal = false, array $attachments = []): Comment
    {
        return DB::transaction(function () use ($report, $user, $content, $isInternal, $attachments) {
            $comment = Comment::create([
                'commentable_id' => $report->id,
                'commentable_type' => Report::class,
                'user_id' => $user->id,
                'content' => $content,
                'is_internal' => $isInternal,
                'attachments' => $attachments,
            ]);

            // Fire event
            event(new CommentAdded($comment));

            return $comment;
        });
    }

    /**
     * Set report to awaiting information from citizen
     */
    public function setAwaitingInfo(Report $report, User $staff, string $reason): Report
    {
        return DB::transaction(function () use ($report, $staff, $reason) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            $lockedReport->update([
                'status' => 'awaiting_info',
                'rejection_reason' => $reason,
                'last_activity_at' => now(),
            ]);

            // Add comment explaining what information is needed (visible to citizen)
            $this->addComment($lockedReport, $staff, "Informasi yang dibutuhkan: {$reason}", false);

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'awaiting_info', $oldData, $lockedReport->toArray(), $staff);

            // Fire event
            event(new ReportStatusChanged($lockedReport, $oldStatus, 'awaiting_info', $staff));

            return $lockedReport;
        });
    }

    /**
     * Reopen a closed report
     */
    public function reopenReport(Report $report, User $user, string $reason): Report
    {
        if (! $report->canBeReopened()) {
            throw new \Exception('Laporan ini tidak dapat dibuka kembali (hanya laporan selesai/ditutup maksimal 30 hari).');
        }

        return DB::transaction(function () use ($report, $user, $reason) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            // Re-activate assignment for the staff who worked on this report if unassigned
            $assignedStaffId = $lockedReport->assigned_to;
            if (! $assignedStaffId) {
                $lastAssignment = $lockedReport->assignments()->where('status', 'completed')->latest('id')->first();
                if ($lastAssignment) {
                    $assignedStaffId = $lastAssignment->assigned_to;
                }
            }

            if ($assignedStaffId) {
                // Deactivate any active assignments
                $lockedReport->assignments()->where('status', 'active')->update([
                    'status' => 'reassigned',
                    'completed_at' => now(),
                ]);

                Assignment::create([
                    'assignable_id' => $lockedReport->id,
                    'assignable_type' => Report::class,
                    'assigned_to' => $assignedStaffId,
                    'assigned_by' => $user->id,
                    'notes' => 'Laporan dibuka kembali (Masalah belum selesai): ' . $reason,
                    'assigned_at' => now(),
                    'status' => 'active',
                ]);
            }

            $lockedReport->update([
                'status' => 'in_progress',
                'assigned_to' => $assignedStaffId,
                'resolved_at' => null,
                'last_activity_at' => now(),
            ]);

            // Add comment with reopen reason
            $this->addComment($lockedReport, $user, "Laporan dibuka kembali oleh pelapor (Masalah belum selesai): {$reason}", false);

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'reopened', $oldData, $lockedReport->toArray(), $user);

            // Fire event
            event(new ReportStatusChanged($lockedReport, $oldStatus, 'in_progress', $user));

            return $lockedReport;
        });
    }

    /**
     * Citizen provides requested information for awaiting_info report, returning it to admin verification
     */
    public function citizenProvideInfo(Report $report, User $citizen, string $infoText, array $attachments = []): Report
    {
        return DB::transaction(function () use ($report, $citizen, $infoText, $attachments) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            // Merge new attachments
            $currentAttachments = $lockedReport->attachments ?? [];
            if (! empty($attachments)) {
                $currentAttachments = array_merge($currentAttachments, $attachments);
            }

            $lockedReport->update([
                'status' => 'submitted',
                'attachments' => $currentAttachments ?: null,
                'last_activity_at' => now(),
            ]);

            // Add public comment
            $this->addComment($lockedReport, $citizen, "Data tambahan dari pelapor: {$infoText}", false, $attachments);

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'info_provided', $oldData, $lockedReport->toArray(), $citizen);

            // Fire event to notify admin
            event(new ReportStatusChanged($lockedReport, $oldStatus, 'submitted', $citizen));

            return $lockedReport;
        });
    }

    /**
     * Citizen confirms report resolution as complete ("Selesai" -> closed)
     */
    public function citizenConfirmClosed(Report $report, User $citizen, ?string $feedback = null): Report
    {
        return DB::transaction(function () use ($report, $citizen, $feedback) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first() ?? $report;

            $oldStatus = $lockedReport->status;
            $oldData = $lockedReport->toArray();

            $lockedReport->update([
                'status' => 'closed',
                'resolved_at' => $lockedReport->resolved_at ?? now(),
                'last_activity_at' => now(),
            ]);

            if ($feedback) {
                $this->addComment($lockedReport, $citizen, "Konfirmasi penyelesaian oleh pelapor: {$feedback}", false);
            }

            $report->refresh();

            // Log the change
            $this->logAudit($lockedReport, 'confirmed_closed_by_citizen', $oldData, $lockedReport->toArray(), $citizen);

            // Fire event
            event(new ReportStatusChanged($lockedReport, $oldStatus, 'closed', $citizen));

            return $lockedReport;
        });
    }

    /**
     * Staff or department head hands finished work to admin for final approval.
     * Callers lock the report and check authorization and status first.
     */
    public function submitForApproval(Report $report, User $user, ?string $completionNotes, array $newAttachments = []): Report
    {
        $oldStatus = $report->status;
        $oldAssignedTo = $report->assigned_to;

        $report->assignments()->where('status', 'active')->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $completionNotes ?: 'Dikonfirmasi ke admin untuk persetujuan akhir',
        ]);

        $attachments = array_merge($report->attachments ?? [], $newAttachments);

        $report->update([
            'assigned_to' => null,
            'status' => 'awaiting_admin_approval',
            'last_activity_at' => now(),
            'completion_notes' => $completionNotes,
            'attachments' => $attachments ?: null,
        ]);

        $this->logAudit(
            $report,
            'confirmed_to_admin',
            ['assigned_to' => $oldAssignedTo, 'status' => $oldStatus],
            ['assigned_to' => null, 'status' => 'awaiting_admin_approval', 'completion_notes' => $completionNotes],
            $user
        );

        if ($oldStatus !== 'awaiting_admin_approval') {
            event(new ReportStatusChanged($report, $oldStatus, 'awaiting_admin_approval', $user));
        }

        return $report;
    }

    /**
     * Log audit trail
     */
    private function logAudit($model, string $event, ?array $oldValues = null, ?array $newValues = null, ?User $user = null)
    {
        AuditLog::record($model, $event, $oldValues, $newValues, $user);
    }
}
