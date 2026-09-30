<?php

namespace App\Models;

use App\Exceptions\InvalidStatusTransition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App\Models\Report
 *
 * @property int $id
 * @property string $ticket_no
 * @property string|null $queue_no
 * @property string $title
 * @property string $description
 * @property string $category
 * @property string $status
 * @property string $priority
 * @property int $user_id
 * @property int|null $department_id
 * @property int|null $assigned_to
 * @property string|null $location
 * @property array|null $attachments
 * @property string|null $resolution_notes
 * @property string|null $completion_notes
 * @property string|null $final_notes
 * @property string|null $info_request
 * @property \Carbon\Carbon|null $resolved_at
 * @property \Carbon\Carbon|null $sla_due_at
 * @property bool $is_escalated
 * @property int $reassign_count
 * @property \Carbon\Carbon|null $last_activity_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\User|null $assignedUser
 */
class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_no',
        'queue_no',
        'title',
        'description',
        'category',
        'status',
        'priority',
        'user_id',
        'department_id',
        'assigned_to',
        'location',
        'attachments',
        'resolution_notes',
        'completion_notes',
        'final_notes',
        'rejection_reason',
        'info_request',
        'resolved_at',
        'sla_due_at',
        'is_escalated',
        'reassign_count',
        'last_activity_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'is_escalated' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Single source of truth for the report workflow: current status => statuses it may move to.
     * Enforced in the updating hook, so every code path (service, controller, admin edit) obeys it.
     * Statuses not listed as keys (including 'rejected') are terminal.
     */
    public const STATUS_TRANSITIONS = [
        'submitted' => ['verified', 'rejected', 'awaiting_info', 'assigned', 'pending'],
        'pending' => ['verified', 'rejected', 'awaiting_info', 'assigned'],
        'awaiting_info' => ['submitted', 'verified', 'rejected', 'assigned'],
        'verified' => ['assigned', 'reviewed', 'rejected', 'awaiting_admin_approval'],
        'assigned' => ['in_progress', 'awaiting_info', 'reviewed', 'awaiting_admin_approval'],
        'in_progress' => ['assigned', 'awaiting_info', 'reviewed', 'awaiting_admin_approval'],
        'reviewed' => ['assigned', 'in_progress', 'needs_revision', 'awaiting_admin_approval'],
        'needs_revision' => ['assigned', 'in_progress', 'reviewed', 'awaiting_admin_approval'],
        'awaiting_admin_approval' => ['resolved', 'needs_revision'],
        'resolved' => ['closed', 'in_progress'],
        'closed' => ['in_progress'],
    ];

    /**
     * Indonesian display labels for statuses. Reports and complaints share this status vocabulary.
     */
    public const STATUS_LABELS = [
        'submitted' => 'Baru masuk',
        'pending' => 'Menunggu verifikasi',
        'verified' => 'Siap ditugaskan',
        'assigned' => 'Ditugaskan',
        'in_progress' => 'Dalam pengerjaan',
        'reviewed' => 'Ditinjau',
        'needs_revision' => 'Perlu revisi',
        'awaiting_info' => 'Menunggu informasi',
        'awaiting_admin_approval' => 'Persetujuan admin',
        'investigating' => 'Dalam investigasi',
        'resolved' => 'Selesai',
        'closed' => 'Ditutup',
        'rejected' => 'Ditolak',
    ];

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    /**
     * Indonesian display labels for priorities, shared by reports and complaints.
     */
    public const PRIORITY_LABELS = [
        'low' => 'Rendah',
        'medium' => 'Normal',
        'high' => 'Tinggi',
        'urgent' => 'Mendesak',
    ];

    public static function priorityLabel(?string $priority): string
    {
        return self::PRIORITY_LABELS[$priority] ?? ucfirst((string) $priority);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->ticket_no)) {
                // Generate ticket number with date prefix for better tracking
                $datePrefix = now()->format('Ymd');
                $randomSuffix = strtoupper(Str::random(6));
                $report->ticket_no = 'RPT-'.$datePrefix.'-'.$randomSuffix;
            }

            // Calculate SLA due date based on priority if not explicitly set
            if (empty($report->sla_due_at)) {
                $report->sla_due_at = $report->calculateSLADueDate();
            }
        });

        static::updating(function ($report) {
            if ($report->isDirty('status')) {
                $from = $report->getOriginal('status');

                if (! in_array($report->status, self::STATUS_TRANSITIONS[$from] ?? [], true)) {
                    throw new InvalidStatusTransition($from, $report->status);
                }
            }

            $report->last_activity_at = now();

            // Automatically recalculate SLA due date when priority changes
            if ($report->isDirty('priority') && ! $report->isDirty('sla_due_at')) {
                $report->sla_due_at = $report->calculateSLADueDate();
            }
        });
    }

    /**
     * Generate next queue number for today, format: Q-YYYYMMDD-####
     */
    public static function nextQueueNo(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'Q-'.$date.'-';

        $latest = static::where('queue_no', 'like', $prefix.'%')
            ->orderBy('queue_no', 'desc')
            ->lockForUpdate()
            ->value('queue_no');

        if ($latest) {
            $lastSeq = (int) substr($latest, strlen($prefix));
            $seq = str_pad((string) ($lastSeq + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix.$seq;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignments()
    {
        return $this->morphMany(Assignment::class, 'assignable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function calculateSLADueDate()
    {
        $slaHours = [
            'urgent' => 2,
            'high' => 8,
            'medium' => 24,
            'low' => 72,
        ];

        $hours = $slaHours[$this->priority] ?? 24;

        return now()->addHours($hours);
    }

    public function isSLABreached()
    {
        return $this->sla_due_at && now()->isAfter($this->sla_due_at);
    }

    /**
     * Statuses this report may be set to right now (its current status plus valid next steps).
     */
    public function allowedStatuses(): array
    {
        return array_merge([$this->status], self::STATUS_TRANSITIONS[$this->status] ?? []);
    }

    public function canBeReopened()
    {
        return in_array($this->status, ['closed', 'resolved']) &&
               ($this->resolved_at === null || $this->resolved_at->diffInDays(now()) <= 30);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'verified']);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['assigned', 'in_progress', 'awaiting_info']);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEscalated($query)
    {
        return $query->where('is_escalated', true);
    }
}
