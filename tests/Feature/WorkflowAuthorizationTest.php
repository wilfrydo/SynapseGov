<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;

    private Department $otherDept;

    private User $admin;

    private User $head;

    private User $staff;

    private User $otherStaff;

    private User $citizen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::factory()->create(['code' => 'TST1']);
        $this->otherDept = Department::factory()->create(['code' => 'TST2']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->head = User::factory()->create(['role' => 'department_head', 'department_id' => $this->dept->id]);
        $this->staff = User::factory()->create(['role' => 'staff', 'department_id' => $this->dept->id]);
        $this->otherStaff = User::factory()->create(['role' => 'staff', 'department_id' => $this->otherDept->id]);
        $this->citizen = User::factory()->create(['role' => 'citizen']);
    }

    private function report(string $ticketNo, string $status): Report
    {
        return Report::factory()->create([
            'ticket_no' => $ticketNo,
            'status' => $status,
            'department_id' => $this->dept->id,
            'user_id' => $this->citizen->id,
        ]);
    }

    public function test_department_head_cannot_hand_report_to_staff_of_another_department(): void
    {
        $report = $this->report('RPT-20261001-AUTH01', 'assigned');

        $this->actingAs($this->head)
            ->post("/workflow/reports/{$report->id}/head-review-return", ['assigned_to' => $this->otherStaff->id])
            ->assertSessionHas('error');

        $this->assertNotSame($this->otherStaff->id, (int) $report->fresh()->assigned_to);
        $this->assertSame('assigned', $report->fresh()->status);
    }

    public function test_department_head_can_hand_report_to_own_staff(): void
    {
        $report = $this->report('RPT-20261001-AUTH02', 'assigned');

        $this->actingAs($this->head)
            ->post("/workflow/reports/{$report->id}/head-review-return", ['assigned_to' => $this->staff->id])
            ->assertSessionHas('success');

        $this->assertSame($this->staff->id, (int) $report->fresh()->assigned_to);
        $this->assertSame('reviewed', $report->fresh()->status);
    }

    public function test_admin_cannot_return_revision_to_a_citizen(): void
    {
        $report = $this->report('RPT-20261001-AUTH03', 'awaiting_admin_approval');

        $this->actingAs($this->admin)
            ->post("/workflow/reports/{$report->id}/admin-reject-staff", [
                'assigned_to' => $this->citizen->id,
                'rejection_reason' => 'Foto bukti kurang jelas',
            ])
            ->assertSessionHas('error');

        $this->assertSame('awaiting_admin_approval', $report->fresh()->status);
    }

    public function test_admin_can_return_revision_to_staff(): void
    {
        $report = $this->report('RPT-20261001-AUTH04', 'awaiting_admin_approval');

        $this->actingAs($this->admin)
            ->post("/workflow/reports/{$report->id}/admin-reject-staff", [
                'assigned_to' => $this->staff->id,
                'rejection_reason' => 'Foto bukti kurang jelas',
            ])
            ->assertSessionHas('success');

        $this->assertSame('needs_revision', $report->fresh()->status);
        $this->assertSame($this->staff->id, (int) $report->fresh()->assigned_to);
    }

    public function test_staff_cannot_skip_admin_verification(): void
    {
        $report = $this->report('RPT-20261001-AUTH05', 'submitted');

        $this->actingAs($this->staff)
            ->post("/workflow/reports/{$report->id}/staff-confirm-forward")
            ->assertSessionHas('error');

        $this->assertSame('submitted', $report->fresh()->status);
        $this->assertNull($report->fresh()->queue_no);
    }

    public function test_staff_submits_finished_work_for_admin_approval(): void
    {
        $report = $this->report('RPT-20261001-AUTH06', 'in_progress');
        $report->update(['assigned_to' => $this->staff->id]);

        $this->actingAs($this->staff)
            ->post("/workflow/reports/{$report->id}/staff-confirm-admin", ['completion_notes' => 'Lubang sudah ditambal'])
            ->assertSessionHas('success');

        $fresh = $report->fresh();
        $this->assertSame('awaiting_admin_approval', $fresh->status);
        $this->assertNull($fresh->assigned_to);
        $this->assertSame('Lubang sudah ditambal', $fresh->completion_notes);
        $this->assertTrue($fresh->auditLogs()->where('event', 'confirmed_to_admin')->where('user_id', $this->staff->id)->exists());
    }

    public function test_department_head_recommends_report_to_admin(): void
    {
        $report = $this->report('RPT-20261001-AUTH07', 'reviewed');

        $this->actingAs($this->head)
            ->post("/administration/reports/{$report->id}/confirm-to-admin")
            ->assertSessionHas('success');

        $this->assertSame('awaiting_admin_approval', $report->fresh()->status);
        $this->assertTrue($report->fresh()->auditLogs()->where('event', 'confirmed_to_admin')->where('user_id', $this->head->id)->exists());
    }

    public function test_staff_cannot_take_over_a_colleagues_report(): void
    {
        $colleague = User::factory()->create(['role' => 'staff', 'department_id' => $this->dept->id]);
        $report = $this->report('RPT-20261001-AUTH08', 'assigned');
        $report->update(['assigned_to' => $colleague->id]);

        $this->actingAs($this->staff)
            ->post("/workflow/reports/{$report->id}/start-work")
            ->assertForbidden();

        $this->actingAs($this->staff)
            ->post("/workflow/reports/{$report->id}/staff-confirm-admin", ['completion_notes' => 'Selesai'])
            ->assertSessionHas('error');

        $this->assertSame('assigned', $report->fresh()->status);
        $this->assertSame($colleague->id, (int) $report->fresh()->assigned_to);
    }

    public function test_staff_picking_up_an_unassigned_report_is_recorded_as_assignment(): void
    {
        $report = $this->report('RPT-20261001-AUTH09', 'needs_revision');

        $this->actingAs($this->staff)
            ->post("/workflow/reports/{$report->id}/start-work")
            ->assertSessionHas('success');

        $fresh = $report->fresh();
        $this->assertSame('in_progress', $fresh->status);
        $this->assertSame($this->staff->id, (int) $fresh->assigned_to);
        $this->assertTrue($fresh->assignments()->where('assigned_to', $this->staff->id)->where('status', 'active')->exists());
    }

    public function test_information_request_is_not_shown_as_a_rejection(): void
    {
        $report = $this->report('RPT-20261001-AUTH10', 'submitted');

        $this->actingAs($this->admin)
            ->post("/workflow/reports/{$report->id}/awaiting-info", ['reason' => 'Mohon kirim foto lokasi'])
            ->assertSessionHas('success');

        $fresh = $report->fresh();
        $this->assertSame('awaiting_info', $fresh->status);
        $this->assertSame('Mohon kirim foto lokasi', $fresh->info_request);
        $this->assertNull($fresh->rejection_reason);

        $this->actingAs($this->citizen)
            ->post("/workflow/reports/{$report->id}/provide-info", ['information' => 'Lokasi di depan pasar'])
            ->assertSessionHas('success');

        $this->assertSame('submitted', $report->fresh()->status);
        $this->assertNull($report->fresh()->info_request);
    }

    public function test_queue_number_collision_is_retried_instead_of_failing(): void
    {
        $report = $this->report('RPT-20261001-AUTH11', 'submitted');

        // Simulate a concurrent verification that grabs the same number just before this one is saved.
        // The first save hits the unique index; before the fix that surfaced as a 500 error.
        $attempts = 0;
        Report::updating(function (Report $saving) use (&$attempts) {
            if ($saving->isDirty('queue_no') && ++$attempts === 1) {
                Report::factory()->create([
                    'ticket_no' => 'RPT-20261001-AUTH12',
                    'status' => 'verified',
                    'queue_no' => $saving->queue_no,
                ]);
            }
        });

        app(\App\Services\WorkflowService::class)->verifyReport($report, $this->admin);

        $this->assertSame(2, $attempts);
        $this->assertSame('verified', $report->fresh()->status);
        $this->assertNotNull($report->fresh()->queue_no);
    }
}
