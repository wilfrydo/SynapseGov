@extends('layouts.dashboard')

@php
    $lang = $settings['language'] ?? (auth()->user()->getSettings('language','id') ?? 'id');
    $t = function(string $key) use ($lang) {
        $id = [
            'settings' => 'Pengaturan',
            'back_to_profile' => 'Kembali ke Profil',
            'general_prefs' => 'Preferensi Umum',
            'display_prefs' => 'Preferensi Tampilan',
            'dashboard_layout' => 'Layout Dashboard',
            'compact' => 'Kompak',
            'comfortable' => 'Nyaman',
            'spacious' => 'Luas',
            'items_per_page' => 'Item Per Halaman',
            'language' => 'Bahasa',
            'notifications' => 'Notifikasi',
            'email_notifications' => 'Notifikasi Email',
            'browser_notifications' => 'Notifikasi Browser',
            'sms_notifications' => 'Notifikasi SMS',
            'report_notifications' => 'Notifikasi Laporan Baru',
            'complaint_notifications' => 'Notifikasi Keluhan Baru',
            'status_update_notifications' => 'Notifikasi Update Status',
            'privacy' => 'Privasi',
            'show_email' => 'Tampilkan Email',
            'show_phone' => 'Tampilkan Telepon',
            'show_address' => 'Tampilkan Alamat',
            'save_settings' => 'Simpan Pengaturan',
            'change_password' => 'Ubah Password',
            'current_password' => 'Password Saat Ini',
            'new_password' => 'Password Baru',
            'confirm_new_password' => 'Konfirmasi Password Baru',
            'update_password' => 'Ubah Password',
            'account_info' => 'Informasi Akun',
            'role' => 'Role',
            'department' => 'Departemen',
            'joined' => 'Bergabung',
            'last_login' => 'Login Terakhir',
            'status' => 'Status',
            'active' => 'Aktif',
        ];
        $en = [
            'settings' => 'Settings',
            'back_to_profile' => 'Back to Profile',
            'general_prefs' => 'General Preferences',
            'display_prefs' => 'Display Preferences',
            'dashboard_layout' => 'Dashboard Layout',
            'compact' => 'Compact',
            'comfortable' => 'Comfortable',
            'spacious' => 'Spacious',
            'items_per_page' => 'Items Per Page',
            'language' => 'Language',
            'notifications' => 'Notifications',
            'email_notifications' => 'Email Notifications',
            'browser_notifications' => 'Browser Notifications',
            'sms_notifications' => 'SMS Notifications',
            'report_notifications' => 'New Report Notifications',
            'complaint_notifications' => 'New Complaint Notifications',
            'status_update_notifications' => 'Status Update Notifications',
            'privacy' => 'Privacy',
            'show_email' => 'Show Email',
            'show_phone' => 'Show Phone',
            'show_address' => 'Show Address',
            'save_settings' => 'Save Settings',
            'change_password' => 'Change Password',
            'current_password' => 'Current Password',
            'new_password' => 'New Password',
            'confirm_new_password' => 'Confirm New Password',
            'update_password' => 'Update Password',
            'account_info' => 'Account Information',
            'role' => 'Role',
            'department' => 'Department',
            'joined' => 'Joined',
            'last_login' => 'Last Login',
            'status' => 'Status',
            'active' => 'Active',
        ];
        return $lang === 'en' ? ($en[$key] ?? $key) : ($id[$key] ?? $key);
    };
@endphp

@section('title', $t('settings'))

@section('content')
<style>
    .profile-wrap {
        max-width: 900px;
        margin: 0 auto;
        padding-bottom: 4rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    
    /* Gradient Header */
    .edit-header-clean {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        padding: 2.5rem;
        border-radius: 12px;
        background-color: #b71c1c;
        background-image: 
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 100' preserveAspectRatio='none'%3E%3Cpath d='M200,0 L200,100 L30,100 C130,100 80,0 160,0 Z' fill='%23333333'/%3E%3C/svg%3E"),
            linear-gradient(115deg, transparent 35%, rgba(0,0,0,0.1) 36%, rgba(0,0,0,0.1) 55%, transparent 56%),
            linear-gradient(65deg, rgba(255,255,255,0.05) 25%, transparent 26%, transparent 65%, rgba(0,0,0,0.1) 66%),
            linear-gradient(135deg, #b71c1c 0%, #d32f2f 50%, #991b1b 100%);
        background-position: right center, center, center, center;
        background-size: 35% 100%, cover, cover, cover;
        background-repeat: no-repeat;
        border: none;
        border-bottom: 4px solid #f59e0b;
        box-shadow: 0 8px 25px -8px rgba(183, 28, 28, 0.45);
        flex-wrap: wrap;
        gap: 1.5rem;
        position: relative;
        overflow: hidden;
        color: #ffffff;
    }
    
    .edit-header-clean h1 {
        font-size: 2rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0;
        letter-spacing: -0.02em;
    }
    .edit-header-clean h1 i { color: #ffffff; }
    
    .edit-header-clean .btn-outline-clean {
        background: #ffffff;
        color: #000000;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .edit-header-clean .btn-outline-clean:hover {
        background: #f8fafc;
        color: #000000;
        transform: translateY(-2px);
    }
    
    .btn-clean {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.5rem;
        border-radius: 100px;
        font-size: 0.95rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    .btn-primary-clean {
        background: #b71c1c;
        color: #ffffff;
    }
    .btn-primary-clean:hover {
        background: #991b1b;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(183, 28, 28, 0.2);
        color: white;
    }
    .btn-outline-clean {
        background: transparent;
        color: #000000;
        border: 2px solid #000000;
    }
    .btn-outline-clean:hover {
        background: rgba(0,0,0,0.05);
        color: #000000;
    }
    
    /* Card Styling */
    .card-clean {
        background: #ffffff;
        border: 2px solid #000000;
        border-radius: 16px;
        box-shadow: none;
        margin-bottom: 2rem;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-clean:hover {
        box-shadow: 4px 4px 0px #000000;
    }
    .card-clean .card-header {
        background: transparent;
        border-bottom: 2px solid #000000;
        padding: 1.5rem 2rem;
        font-weight: 700;
        font-size: 1.25rem;
        color: #000000;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .card-clean .card-header i { color: #000000; }
    .card-clean .card-body {
        padding: 2rem;
    }

    .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #000000;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #000000;
    }
    .form-section-title i { color: #000000; }
    
    /* Dark Mode Overrides */
    html.dark .card-clean {
        background: #1e293b;
        border-color: #ffffff;
    }
    html.dark .card-clean:hover {
        box-shadow: 4px 4px 0px #ffffff;
    }
    html.dark .card-clean .card-header,
    html.dark .form-section-title {
        border-color: #ffffff;
        color: #ffffff;
    }
    html.dark .card-clean .card-header i,
    html.dark .form-section-title i {
        color: #ffffff;
    }
    html.dark .btn-outline-clean {
        color: #ffffff;
        border-color: #ffffff;
    }
    html.dark .btn-outline-clean:hover {
        background: rgba(255,255,255,0.1);
        color: #ffffff;
    }
    html.dark .edit-header-clean h1 { color: #ffffff !important; }
    html.dark .edit-header-clean h1 i { color: #ffffff !important; }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="edit-header-clean mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-cog me-2"></i>{{ $t('settings') }}
                </h1>
                <a href="{{ route('profile.show') }}" class="btn-clean btn-outline-clean">
                    <i class="fas fa-arrow-left me-1"></i>{{ $t('back_to_profile') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Terdapat kesalahan:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif



    <div class="row">
        <!-- Settings Form -->
        <div class="col-lg-8">
            <div class="card card-clean mb-4">
                <div class="card-header">
                        <i class="fas fa-sliders-h me-2"></i>{{ $t('general_prefs') }}
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Display Preferences -->
                            <div class="col-md-6 mb-4">
                                <div class="form-section-title">
                                    <i class="fas fa-desktop me-2"></i>{{ $t('display_prefs') }}
                                </div>
                                
                                <div class="mb-3">
                                    <label for="dashboard_layout" class="form-label">{{ $t('dashboard_layout') }}</label>
                                    <select class="form-select" id="dashboard_layout" name="dashboard_layout">
                                        <option value="compact" {{ ($settings['dashboard_layout'] ?? 'comfortable') === 'compact' ? 'selected' : '' }}>
                                            📊 {{ $t('compact') }}
                                        </option>
                                        <option value="comfortable" {{ ($settings['dashboard_layout'] ?? 'comfortable') === 'comfortable' ? 'selected' : '' }}>
                                            🖥️ {{ $t('comfortable') }}
                                        </option>
                                        <option value="spacious" {{ ($settings['dashboard_layout'] ?? 'comfortable') === 'spacious' ? 'selected' : '' }}>
                                            🎯 {{ $t('spacious') }}
                                        </option>
                                    </select>
                                    <small class="text-muted d-block">Atur jarak dan ukuran elemen dashboard</small>
                                    <div id="layout-preview" class="mt-2 p-2 border rounded bg-light" style="display: none;">
                                        <small class="text-muted d-block mb-1"><strong>Preview:</strong></small>
                                        <div id="preview-content" class="text-muted small"></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="items_per_page" class="form-label">{{ $t('items_per_page') }}</label>
                                    <select class="form-select" id="items_per_page" name="items_per_page">
                                        <option value="10" {{ ($settings['items_per_page'] ?? 15) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="15" {{ ($settings['items_per_page'] ?? 15) == 15 ? 'selected' : '' }}>15</option>
                                        <option value="25" {{ ($settings['items_per_page'] ?? 15) == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ ($settings['items_per_page'] ?? 15) == 50 ? 'selected' : '' }}>50</option>
                                    </select>
                                    <small class="text-muted">Jumlah item per halaman pada tabel</small>
                                </div>

                                <div class="mb-3">
                                    <label for="language" class="form-label">{{ $t('language') }}</label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="id" {{ $settings['language'] === 'id' ? 'selected' : '' }}>
                                            🇮🇩 Bahasa Indonesia
                                        </option>
                                        <option value="en" {{ $settings['language'] === 'en' ? 'selected' : '' }}>
                                            🇺🇸 English
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Notification Settings -->
                            <div class="col-md-6 mb-4">
                                <div class="form-section-title">
                                    <i class="fas fa-bell me-2"></i>{{ $t('notifications') }}
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2">Channel Notifikasi:</small>
                                    
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notifications_email" name="notifications[email]" value="1" {{ ($settings['notifications']['email'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notifications_email">
                                            <i class="fas fa-envelope me-2"></i>{{ $t('email_notifications') }}
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notifications_browser" name="notifications[browser]" value="1" {{ ($settings['notifications']['browser'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notifications_browser">
                                            <i class="fas fa-desktop me-2"></i>{{ $t('browser_notifications') }}
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="notifications_sms" name="notifications[sms]" value="1" {{ ($settings['notifications']['sms'] ?? false) ? 'checked' : '' }} disabled>
                                        <label class="form-check-label text-muted" for="notifications_sms">
                                            <i class="fas fa-sms me-2"></i>{{ $t('sms_notifications') }}
                                            <span class="badge bg-secondary-subtle text-secondary ms-1 fw-normal" style="font-size: 0.72rem;">Segera Hadir</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2">Tipe Notifikasi:</small>
                                    
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notifications_reports" name="notifications[reports]" value="1" {{ ($settings['notifications']['reports'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notifications_reports">
                                            <i class="fas fa-file-alt me-2"></i>{{ $t('report_notifications') }}
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notifications_complaints" name="notifications[complaints]" value="1" {{ ($settings['notifications']['complaints'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notifications_complaints">
                                            <i class="fas fa-exclamation-triangle me-2"></i>{{ $t('complaint_notifications') }}
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notifications_status" name="notifications[status]" value="1" {{ ($settings['notifications']['status'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notifications_status">
                                            <i class="fas fa-sync me-2"></i>{{ $t('status_update_notifications') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy Settings -->
                        <div class="row">
                            <div class="col-12 mb-4">
                                <div class="form-section-title">
                                    <i class="fas fa-shield-alt me-2"></i>{{ $t('privacy') }}
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="privacy_show_email" name="privacy[show_email]" value="1" {{ $settings['privacy']['show_email'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="privacy_show_email">
                                                <i class="fas fa-envelope me-2"></i>{{ $t('show_email') }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="privacy_show_phone" name="privacy[show_phone]" value="1" {{ $settings['privacy']['show_phone'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="privacy_show_phone">
                                                <i class="fas fa-phone me-2"></i>{{ $t('show_phone') }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="privacy_show_address" name="privacy[show_address]" value="1" {{ $settings['privacy']['show_address'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="privacy_show_address">
                                                <i class="fas fa-map-marker-alt me-2"></i>{{ $t('show_address') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-clean btn-primary-clean">
                                <i class="fas fa-save me-1"></i>{{ $t('save_settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password & Account Actions -->
        <div class="col-lg-4">
            <!-- Change Password -->
            <div class="card card-clean mb-4">
                <div class="card-header">
                        <i class="fas fa-key me-2"></i>{{ $t('change_password') }}
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">{{ $t('current_password') }}</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary toggle-pwd-btn" type="button" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ $t('new_password') }}</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary toggle-pwd-btn" type="button" data-target="password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-shield-alt me-1"></i>Min. 8 karakter, berbeda dari password saat ini.
                            </small>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ $t('confirm_new_password') }}</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary toggle-pwd-btn" type="button" data-target="password_confirmation">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-lock me-1"></i>{{ $t('update_password') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card card-clean">
                <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>{{ $t('account_info') }}
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">{{ $t('role') }}:</small>
                        <div class="fw-bold">{{ $user->getRoleDisplayName() }}</div>
                    </div>
                    
                    @if($user->department)
                    <div class="mb-3">
                        <small class="text-muted">{{ $t('department') }}:</small>
                        <div class="fw-bold">{{ $user->department->name }}</div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <small class="text-muted">{{ $t('joined') }}:</small>
                        <div class="fw-bold">{{ $user->created_at ? $user->created_at->format($lang === 'en' ? 'M d, Y' : 'd F Y') : '-' }}</div>
                    </div>

                    @if($user->last_login_at)
                    <div class="mb-3">
                        <small class="text-muted">{{ $t('last_login') }}:</small>
                        <div class="fw-bold">{{ $user->last_login_at->format($lang === 'en' ? 'M d, Y, H:i' : 'd F Y, H:i') }}</div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <small class="text-muted">{{ $t('status') }}:</small>
                        <span class="badge bg-success">{{ $t('active') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-check-input:checked {
    background-color: #0284c7;
    border-color: #0284c7;
}

.form-check-label {
    font-weight: 500;
    color: #5a5c69;
}

.card {
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0;
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(2, 132, 199, 0.05) 100%);
}

.form-switch .form-check-input {
    width: 2.5em;
    height: 1.25em;
}

.form-switch .form-check-input:checked {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);
}

/* Layout Variants CSS */
:root {
    --card-padding: 1.5rem;
    --element-spacing: 1.5rem;
    --font-size-base: 1rem;
}

.layout-compact .card {
    padding: var(--card-padding);
}

.layout-compact .mb-3,
.layout-compact .mb-4 {
    margin-bottom: var(--element-spacing) !important;
}

.layout-comfortable .card {
    padding: var(--card-padding);
}

.layout-spacious .card {
    padding: var(--card-padding);
}

.layout-spacious .card-body {
    font-size: var(--font-size-base);
    line-height: 1.8;
}
</style>
@endsection

@push('scripts')
<script>
    // Apply dashboard layout immediately when changed
    document.addEventListener('DOMContentLoaded', function() {
        const layoutSelect = document.getElementById('dashboard_layout');
        const layoutPreview = document.getElementById('layout-preview');
        const previewContent = document.getElementById('preview-content');
        
        if (!layoutSelect) return;

        const layoutDescriptions = {
            'compact': 'Hemat ruang dengan padding lebih kecil (0.75rem) dan font lebih kecil (14px). Cocok untuk layar kecil atau menampilkan lebih banyak informasi.',
            'comfortable': 'Seimbang dengan padding standar (1.5rem) dan font normal (16px). Pilihan default yang nyaman untuk kebanyakan pengguna.',
            'spacious': 'Lebih lega dengan padding besar (2rem) dan font lebih besar (18px). Lebih mudah dibaca dan nyaman untuk mata.'
        };

        function showPreview(layout) {
            if (previewContent) {
                previewContent.textContent = layoutDescriptions[layout] || '';
                if (layoutPreview) {
                    layoutPreview.style.display = 'block';
                }
            }
        }

        function applyLayout(layout) {
            const root = document.documentElement;
            root.classList.remove('layout-compact', 'layout-comfortable', 'layout-spacious');
            
            if (layout === 'compact') {
                root.style.setProperty('--card-padding', '0.75rem');
                root.style.setProperty('--element-spacing', '0.5rem');
                root.style.setProperty('--font-size-base', '0.875rem');
            } else if (layout === 'spacious') {
                root.style.setProperty('--card-padding', '2rem');
                root.style.setProperty('--element-spacing', '2rem');
                root.style.setProperty('--font-size-base', '1.125rem');
            } else { // comfortable (default)
                root.style.setProperty('--card-padding', '1.5rem');
                root.style.setProperty('--element-spacing', '1.5rem');
                root.style.setProperty('--font-size-base', '1rem');
            }
            
            root.classList.add('layout-' + layout);
            showPreview(layout);
        }

        // Apply current value on load
        const currentLayout = layoutSelect.value || 'comfortable';
        applyLayout(currentLayout);

        layoutSelect.addEventListener('change', function() {
            const val = this.value;
            try { localStorage.setItem('dashboard_layout', val); } catch(e) {}
            applyLayout(val);
        });

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                try {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } else {
                        alert.style.display = 'none';
                    }
                } catch (e) {
                    console.log('Alert close error:', e);
                }
            }, 5000);
        });

        // Password visibility toggler
        document.querySelectorAll('.toggle-pwd-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        if (icon) {
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        }
                    } else {
                        input.type = 'password';
                        if (icon) {
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    }
                }
            });
        });
    });
</script>
@endpush

