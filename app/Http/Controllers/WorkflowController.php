<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends Controller
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
     * Check if user is authorized to manage the given report.
     */
    protected function canManageReport(User $user, Report $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDepartmentHead() && (int) $user->department_id === (int) $report->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Verify a report (Admin or Department Head)
     */
    public function verifyReport(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        if (! $this->canManageReport($user, $report)) {
            abort(403, 'Akses ditolak. Hanya admin atau kepala departemen terkait yang dapat memverifikasi laporan.');
        }

        if (! in_array($report->status, ['submitted', 'pending', 'awaiting_info'])) {
            return redirect()->back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat diverifikasi kembali.');
        }

        $request->validate([
            'category' => 'sometimes|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'department_id' => 'sometimes|exists:departments,id',
        ]);

        $this->workflowService->verifyReport($report, $user, $request->only([
            'category', 'priority', 'department_id',
        ]));

        return redirect()->back()->with('success', 'Laporan berhasil diverifikasi.');
    }

    /**
     * Reject a report (Admin or Department Head)
     */
    public function rejectReport(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        if (! $this->canManageReport($user, $report)) {
            abort(403, 'Akses ditolak. Hanya admin atau kepala departemen terkait yang dapat menolak laporan.');
        }

        if (! in_array($report->status, ['submitted', 'pending', 'verified', 'awaiting_info'])) {
            return redirect()->back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat ditolak.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $this->workflowService->rejectReport($report, $user, (string) $request->input('reason'));

        return redirect()->back()->with('success', 'Laporan berhasil ditolak.');
    }

    /**
     * Start working on a report (Assigned staff)
     */
    public function startWork($id): RedirectResponse
    {
        $report = Report::findOrFail($id);
        $user = $this->user();

        $isAssignedStaff = (int) $report->assigned_to === (int) $user->id;
        $isInDeptStaff = $user->isStaff() && ((int) $report->department_id === (int) $user->department_id);

        // Check if user is assigned to this report or is staff in the same department
        if (! $isAssignedStaff && ! $isInDeptStaff && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak ditugaskan untuk laporan ini.');
        }

        if (! in_array($report->status, ['assigned', 'reviewed', 'needs_revision'])) {
            return redirect()->back()->with('error', 'Pengerjaan tidak dapat dimulai untuk laporan berstatus "'.$report->status.'".');
        }

        if (! $report->assigned_to && $isInDeptStaff) {
            $report->update(['assigned_to' => $user->id]);
        }

        $this->workflowService->startWork($report, $user);

        return redirect()->back()->with('success', 'Pengerjaan laporan berhasil dimulai.');
    }

    /**
     * Add a comment to a report
     */
    public function addComment(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        $isInternal = false;

        if ($user->isCitizen()) {
            if ((int) $report->user_id !== (int) $user->id) {
                abort(403, 'Akses ditolak. Anda tidak berhak mengomentari laporan ini.');
            }
            // Citizens cannot add internal comments
            $isInternal = false;
        } else {
            // Verify staff/head has rights to this report's department
            if (! $user->isAdmin() && (int) $report->department_id !== (int) $user->department_id && (int) $report->assigned_to !== (int) $user->id) {
                abort(403, 'Akses ditolak. Anda tidak berwenang mengomentari laporan dari departemen lain.');
            }
            $isInternal = $request->boolean('is_internal');
        }

        $request->validate([
            'content' => 'required|string|max:2000',
            'is_internal' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip|max:5120',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = Attachments::store($file, 'comments');
            }
        }

        $this->workflowService->addComment(
            $report,
            $user,
            (string) $request->input('content'),
            $isInternal,
            $attachments
        );

        return redirect()->back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    /**
     * Set report to awaiting info
     */
    public function setAwaitingInfo(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        // Must be assigned staff, department head, or admin
        if (! ($user->isAdmin() || (int) $report->assigned_to === (int) $user->id || ($user->isDepartmentHead() && (int) $user->department_id === (int) $report->department_id))) {
            abort(403, 'Akses ditolak.');
        }

        if (! in_array($report->status, ['submitted', 'pending', 'in_progress', 'assigned'])) {
            return redirect()->back()->with('error', 'Laporan dengan status "'.$report->status.'" tidak dapat menunggu informasi.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $this->workflowService->setAwaitingInfo($report, $user, (string) $request->input('reason'));

        return redirect()->back()->with('success', 'Permintaan informasi tambahan telah dikirim ke pelapor.');
    }

    /**
     * Reopen a closed report
     */
    public function reopenReport(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        if ($user->isCitizen() && (int) $report->user_id !== (int) $user->id) {
            abort(403, 'Anda hanya dapat membuka kembali laporan milik Anda sendiri.');
        }

        if (! $user->isCitizen() && ! $this->canManageReport($user, $report)) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->workflowService->reopenReport($report, $user, (string) $request->input('reason'));

            return redirect()->back()->with('success', 'Laporan berhasil dibuka kembali.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get report workflow history (filtered by privacy)
     */
    public function getWorkflowHistory($id): JsonResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        $this->authorize('view', $report);

        // Only expose who acted, never their personal data
        $actorColumns = 'id,name,role';

        $auditLogs = $report->auditLogs()
            ->with("user:{$actorColumns}")
            ->orderBy('created_at', 'desc')
            ->get();

        // Privacy filter: Citizens must NEVER see internal comments
        $commentsQuery = $report->comments()
            ->with("user:{$actorColumns}")
            ->orderBy('created_at', 'desc');

        if ($user->isCitizen()) {
            $commentsQuery->where('is_internal', false);
        }

        $comments = $commentsQuery->get();

        $assignments = $report->assignments()
            ->with(["assignedTo:{$actorColumns}", "assignedBy:{$actorColumns}"])
            ->orderBy('created_at', 'desc')
            ->get();

        // Forensic fields (IP, user agent) are for admins only
        if (! $user->isAdmin()) {
            $auditLogs->makeHidden(['ip_address', 'user_agent']);
        }

        // Citizens get a timeline: no raw data snapshots and no internal disposition notes
        if ($user->isCitizen()) {
            $auditLogs->makeHidden(['old_values', 'new_values']);
            $assignments->makeHidden('notes');
        }

        return response()->json([
            'audit_logs' => $auditLogs,
            'comments' => $comments,
            'assignments' => $assignments,
        ]);
    }

    /**
     * Citizen provides additional information for awaiting_info report, returning it to admin verification
     */
    public function provideAdditionalInfo(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        if ((int) $report->user_id !== (int) $user->id) {
            abort(403, 'Akses ditolak. Anda bukan pemilik laporan ini.');
        }

        if ($report->status !== 'awaiting_info') {
            return redirect()->back()->with('error', 'Laporan saat ini tidak dalam status menunggu informasi.');
        }

        $request->validate([
            'information' => 'required|string|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip|max:5120',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = Attachments::store($file, 'reports');
            }
        }

        $this->workflowService->citizenProvideInfo($report, $user, (string) $request->input('information'), $attachments);

        return redirect()->back()->with('success', 'Data tambahan berhasil dikirim ke Admin untuk diverifikasi kembali.');
    }

    /**
     * Citizen confirms resolution as complete ("Selesai" -> closed)
     */
    public function citizenConfirmResolved(Request $request, $id): RedirectResponse
    {
        $user = $this->user();
        $report = Report::findOrFail($id);

        if ((int) $report->user_id !== (int) $user->id) {
            abort(403, 'Akses ditolak. Anda bukan pemilik laporan ini.');
        }

        if ($report->status !== 'resolved') {
            return redirect()->back()->with('error', 'Hanya laporan yang berstatus selesai yang dapat dikonfirmasi.');
        }

        $request->validate([
            'feedback' => 'nullable|string|max:1000',
        ]);

        $this->workflowService->citizenConfirmClosed($report, $user, $request->input('feedback'));

        return redirect()->back()->with('success', 'Terima kasih! Laporan telah dikonfirmasi selesai dan resmi diarsipkan.');
    }
}
