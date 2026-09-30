<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'auditable_id',
        'auditable_type',
        'user_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Record an action on a report, complaint or user. The actor defaults to the logged-in user,
     * and the request's IP and user agent are captured for forensic review (admin-only).
     */
    public static function record(Model $subject, string $event, ?array $oldValues = null, ?array $newValues = null, ?User $user = null): self
    {
        return static::create([
            'auditable_type' => $subject::class,
            'auditable_id' => $subject->getKey(),
            'user_id' => $user ? $user->id : Auth::id(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
