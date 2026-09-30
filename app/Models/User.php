<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property int|null $department_id
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $id_number
 * @property bool $is_active
 * @property string|null $avatar
 * @property \Carbon\Carbon|null $birth_date
 * @property string|null $gender
 * @property string|null $employee_id
 * @property string|null $position
 * @property string|null $bio
 * @property array|null $settings
 * @property \Carbon\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Department|null $department
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'phone',
        'address',
        'id_number',
        'is_active',
        'avatar',
        'birth_date',
        'gender',
        'employee_id',
        'position',
        'bio',
        'settings',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Personal data (NIK, contact, login trail) must never leak through JSON responses
        'id_number',
        'phone',
        'address',
        'birth_date',
        'last_login_at',
        'last_login_ip',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'birth_date' => 'date',
        'settings' => 'array',
        'last_login_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function assignedReports()
    {
        return $this->hasMany(Report::class, 'assigned_to');
    }

    public function assignedComplaints()
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'assigned_to');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDepartmentHead(): bool
    {
        return $this->role === 'department_head';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }

    /**
     * Get user's avatar URL
     */
    public function getAvatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return asset('storage/'.$this->avatar);
    }

    /**
     * Get avatar initials for default display
     */
    public function getAvatarInitials()
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials ?: strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Get avatar background color based on name
     */
    public function getAvatarColor()
    {
        $colors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e',
            '#e74a3b', '#858796', '#5a5c69', '#6f42c1',
            '#fd7e14', '#20c997', '#6610f2', '#e83e8c',
        ];

        $index = ord(strtolower($this->name[0])) % count($colors);

        return $colors[$index];
    }

    /**
     * Get user's full name with position
     */
    public function getFullNameWithPosition()
    {
        $name = $this->name;
        if ($this->position) {
            $name .= ' ('.$this->position.')';
        }

        return $name;
    }

    /**
     * Get user's settings with defaults
     *
     * @param  string|null  $key
     * @param  mixed  $default
     */
    public function getSettings($key = null, $default = null): mixed
    {
        $defaultSettings = [
            'dashboard_layout' => 'comfortable',
            'items_per_page' => 15,
            'language' => 'id',
            'theme' => 'light',
            'notifications' => [
                'email' => true,
                'browser' => true,
                'sms' => false,
                'reports' => true,
                'complaints' => true,
                'status' => true,
            ],
            'privacy' => [
                'show_email' => false,
                'show_phone' => false,
                'show_address' => false,
            ],
        ];

        $settings = is_array($this->settings) ? $this->settings : [];
        $mergedSettings = array_replace_recursive($defaultSettings, $settings);

        if ($key) {
            return data_get($mergedSettings, $key, $default);
        }

        return $mergedSettings;
    }

    /**
     * Update user settings
     */
    public function updateSettings(array $newSettings)
    {
        $currentSettings = $this->getSettings();
        $this->settings = array_replace_recursive($currentSettings, $newSettings);
        $this->save();
    }

    /**
     * Get user's role display name
     */
    public function getRoleDisplayName()
    {
        $roles = [
            'admin' => 'Administrator',
            'department_head' => 'Kepala Departemen',
            'staff' => 'Staff',
            'citizen' => 'Warga',
        ];

        return $roles[$this->role] ?? ucfirst($this->role);
    }
}
