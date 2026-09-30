<?php

namespace Tests\Feature;

use App\Exceptions\InvalidStatusTransition;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintAndAdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;

    private User $admin;

    private User $head;

    private User $staff;

    private User $citizen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::factory()->create(['code' => 'CMP1']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->head = User::factory()->create(['role' => 'department_head', 'department_id' => $this->dept->id]);
        $this->staff = User::factory()->create(['role' => 'staff', 'department_id' => $this->dept->id, 'is_active' => true]);
        $this->citizen = User::factory()->create(['role' => 'citizen']);
    }

    private function complaint(string $status): Complaint
    {
        return Complaint::factory()->create([
            'status' => $status,
            'department_id' => $this->dept->id,
            'user_id' => $this->citizen->id,
        ]);
    }

    public function test_complaint_cannot_be_resolved_before_it_is_investigated(): void
    {
        $complaint = $this->complaint('submitted');

        $this->actingAs($this->head)
            ->post("/administration/complaints/{$complaint->id}/resolve", ['resolution_notes' => 'Sudah ditangani'])
            ->assertSessionHas('error');

        $this->assertSame('submitted', $complaint->fresh()->status);
    }

    public function test_investigated_complaint_can_be_resolved(): void
    {
        $complaint = $this->complaint('submitted');

        $this->actingAs($this->head)
            ->post("/administration/complaints/{$complaint->id}/assign", ['assigned_to' => $this->staff->id])
            ->assertSessionHas('success');
        $this->assertSame('investigating', $complaint->fresh()->status);

        $this->actingAs($this->head)
            ->post("/administration/complaints/{$complaint->id}/resolve", ['resolution_notes' => 'Sudah ditangani'])
            ->assertSessionHas('success');
        $this->assertSame('resolved', $complaint->fresh()->status);
    }

    public function test_rejected_complaint_is_terminal(): void
    {
        $complaint = $this->complaint('rejected');

        $this->expectException(InvalidStatusTransition::class);

        $complaint->update(['status' => 'investigating']);
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/users/{$this->staff->id}/toggle-status")
            ->assertSessionHas('success');
        $this->assertFalse($this->staff->fresh()->is_active);

        $this->actingAs($this->admin)
            ->post("/admin/users/{$this->staff->id}/toggle-status")
            ->assertSessionHas('success');
        $this->assertTrue($this->staff->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/users/{$this->admin->id}/toggle-status")
            ->assertSessionHas('error');

        $this->assertNotFalse($this->admin->fresh()->is_active);
    }

    public function test_non_admin_cannot_toggle_user_status(): void
    {
        $this->actingAs($this->head)
            ->post("/admin/users/{$this->staff->id}/toggle-status")
            ->assertForbidden();
    }

    public function test_staff_report_page_renders_each_modal_once_in_indonesian(): void
    {
        $this->withoutVite();

        $report = Report::factory()->create([
            'ticket_no' => 'RPT-20261001-UIC001',
            'status' => 'assigned',
            'priority' => 'high',
            'department_id' => $this->dept->id,
            'user_id' => $this->citizen->id,
            'assigned_to' => $this->staff->id,
            'sla_due_at' => now()->setDate(2026, 10, 1),
        ]);

        $html = $this->actingAs($this->staff)->get('/administration/reports')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="completeModal'.$report->id.'"'));
        $this->assertStringContainsString('Tinggi', $html);
        $this->assertStringContainsString('01 Okt', $html);
        $this->assertStringNotContainsString('01 Oct', $html);
    }
}
