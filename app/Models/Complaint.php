<?php

namespace App\Models;

use App\Exceptions\InvalidStatusTransition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_no',
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
        'investigation_notes',
        'resolution_notes',
        'rejection_reason',
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
     * Complaint workflow: current status => statuses it may move to. Enforced in the updating hook.
     * 'verified', 'assigned' and 'in_progress' are legacy complaint statuses, handled like 'investigating'.
     * Statuses not listed as keys (including 'rejected') are terminal.
     */
    public const STATUS_TRANSITIONS = [
        'submitted' => ['pending', 'investigating', 'rejected'],
        'pending' => ['investigating', 'rejected'],
        'verified' => ['investigating', 'rejected'],
        'assigned' => ['investigating', 'resolved', 'rejected'],
        'in_progress' => ['investigating', 'resolved', 'rejected'],
        'investigating' => ['resolved', 'rejected'],
        'resolved' => ['closed', 'investigating'],
        'closed' => ['investigating'],
    ];

    /**
     * Statuses this complaint may be set to right now (its current status plus valid next steps).
     */
    public function allowedStatuses(): array
    {
        return array_merge([$this->status], self::STATUS_TRANSITIONS[$this->status] ?? []);
    }

    public function canBeResolved(): bool
    {
        return in_array('resolved', self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($complaint) {
            if (empty($complaint->ticket_no)) {
                $datePrefix = now()->format('Ymd');
                $randomSuffix = strtoupper(Str::random(6));
                $complaint->ticket_no = 'CMP-'.$datePrefix.'-'.$randomSuffix;
            }

            // Calculate SLA due date based on priority if not explicitly set
            if (empty($complaint->sla_due_at)) {
                $complaint->sla_due_at = $complaint->calculateSLADueDate();
            }
        });

        static::updating(function ($complaint) {
            if ($complaint->isDirty('status')) {
                $from = $complaint->getOriginal('status');

                if (! in_array($complaint->status, self::STATUS_TRANSITIONS[$from] ?? [], true)) {
                    throw new InvalidStatusTransition($from, $complaint->status, 'Keluhan');
                }
            }

            $complaint->last_activity_at = now();

            // Automatically recalculate SLA due date when priority changes
            if ($complaint->isDirty('priority') && ! $complaint->isDirty('sla_due_at')) {
                $complaint->sla_due_at = $complaint->calculateSLADueDate();
            }
        });
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

    public function canBeReopened()
    {
        return $this->status === 'closed' &&
               $this->resolved_at &&
               $this->resolved_at->diffInDays(now()) <= 30;
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
