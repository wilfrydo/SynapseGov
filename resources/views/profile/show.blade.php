@extends('layouts.dashboard')

@section('title', 'Profil Saya')

@section('content')
    <style>
        .profile-wrap {
            max-width: 900px;
            margin: 0 auto;
            padding-bottom: 4rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Gradient Profile Header */
        .profile-header-clean {
            display: flex;
            align-items: center;
            gap: 2.5rem;
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
            position: relative;
            overflow: hidden;
            color: #ffffff;
        }

        .avatar-clean {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background-color: #ffffff;
            color: #b71c1c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            object-fit: cover;
            border: 4px solid #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .profile-meta h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 0.25rem 0;
            letter-spacing: -0.02em;
        }

        .badge-clean {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: rgba(255,255,255,0.15);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-secondary-clean {
            background: rgba(255,255,255,0.15);
        }

        .profile-actions-clean {
            margin-left: auto;
            display: flex;
            gap: 1rem;
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

        .profile-actions-clean .btn-primary-clean {
            background: #ffffff;
            color: #b71c1c;
        }

        .profile-actions-clean .btn-primary-clean:hover {
            background: rgba(255,255,255,0.9);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            color: #b71c1c;
        }

        .profile-actions-clean .btn-outline-clean {
            background: #ffffff;
            color: #000000;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .profile-actions-clean .btn-outline-clean:hover {
            background: #f8fafc;
            color: #000000;
            transform: translateY(-2px);
        }

        /* Minimalist Cards */
        .grid-clean {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .card-clean {
            background: transparent;
            border-radius: 24px;
            padding: 2.5rem;
            border: 2px solid #000000;
            box-shadow: none;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card-header-clean {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header-clean i {
            color: #000000;
        }

        .data-list-clean {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            flex-grow: 1;
        }

        .data-item-clean {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .data-label-clean {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #000000;
            font-weight: 700;
        }

        .data-value-clean {
            font-size: 1.1rem;
            color: #000000;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .data-value-clean i {
            color: #000000;
            font-size: 1rem;
        }

        /* Stats Dashboard Clean */
        .stats-clean-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .stat-item-clean {
            background: transparent;
            border-radius: 16px;
            padding: 1.5rem;
            border: 2px solid #000000;
            box-shadow: none;
        }

        .stat-number-clean {
            font-size: 2.5rem;
            font-weight: 800;
            color: #000000;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label-clean {
            font-size: 0.9rem;
            color: #000000;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-label-clean i {
            color: #000000;
        }

        @media (max-width: 768px) {
            .profile-header-clean {
                flex-direction: column;
                text-align: center;
                padding: 2rem;
            }

            .profile-actions-clean {
                margin-left: 0;
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }

            .grid-clean {
                grid-template-columns: 1fr;
            }
        }

        /* Dark Mode Rules */
        html.dark .card-clean,
        html.dark .stat-item-clean,
        html.dark .btn-outline-clean:not(.profile-actions-clean .btn-outline-clean) {
            border-color: #ffffff !important;
        }
        
        html.dark .card-header-clean,
        html.dark .card-header-clean i,
        html.dark .data-label-clean,
        html.dark .data-value-clean,
        html.dark .data-value-clean i,
        html.dark .stat-number-clean,
        html.dark .stat-label-clean,
        html.dark .stat-label-clean i,
        html.dark .btn-outline-clean:not(.profile-actions-clean .btn-outline-clean) {
            color: #ffffff !important;
        }
        
        html.dark .btn-outline-clean:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>

    <div class="container-fluid profile-wrap">

        <!-- Header / Identitas -->
        <div class="profile-header-clean">
            @php
                $avatarPath = $user->avatar;
                $avatarUrl = $avatarPath ? route('avatar.show', basename($avatarPath)) : null;
                $initials = $user->getAvatarInitials();
            @endphp

            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="avatar-clean"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="avatar-clean" style="display:none;">{{ $initials }}</div>
            @else
                <div class="avatar-clean">{{ $initials }}</div>
            @endif

            <div class="profile-meta" style="display: flex; flex-direction: row; align-items: center; gap: 1rem;">
                <h1 style="margin: 0; line-height: 1;">{{ $user->name }}</h1>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <span class="badge-clean">
                        <i class="fas fa-shield-alt"></i> {{ $user->getRoleDisplayName() }}
                    </span>
                    @if($user->department)
                        <span class="badge-clean badge-secondary-clean">
                            <i class="fas fa-building"></i> {{ $user->department->name }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="profile-actions-clean">
                <a href="{{ route('profile.settings') }}" class="btn-clean btn-outline-clean">
                    <i class="fas fa-cog"></i> Pengaturan
                </a>
                <a href="{{ route('profile.edit') }}" class="btn-clean btn-primary-clean">
                    <i class="fas fa-edit"></i> Edit Profil
                </a>
            </div>
        </div>

        <!-- Informasi / Data -->
        <div class="grid-clean">

            <!-- Kolom Kiri: Kontak -->
            <div class="card-clean">
                <h2 class="card-header-clean">
                    <i class="fas fa-address-book"></i> Informasi Kontak
                </h2>
                <div class="data-list-clean">
                    @php
                        $isOwner = auth()->check() && (auth()->id() === $user->id || auth()->user()->isAdmin());
                        $showEmail = $user->getSettings('privacy.show_email', false);
                        $showPhone = $user->getSettings('privacy.show_phone', false);
                        $showAddress = $user->getSettings('privacy.show_address', false);
                    @endphp

                    @if($isOwner || $showEmail)
                    <div class="data-item-clean">
                        <span class="data-label-clean">Alamat Email</span>
                        <span class="data-value-clean">
                            <i class="fas fa-envelope"></i> {{ $user->email }}
                            @if(auth()->id() === $user->id)
                                <span class="badge {{ $showEmail ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} ms-1" style="font-size: 0.7rem;">
                                    <i class="fas {{ $showEmail ? 'fa-globe' : 'fa-eye-slash' }} me-1"></i>{{ $showEmail ? 'Publik' : 'Privat' }}
                                </span>
                            @endif
                        </span>
                    </div>
                    @endif

                    @if($user->phone && ($isOwner || $showPhone))
                        <div class="data-item-clean">
                            <span class="data-label-clean">No. Telepon</span>
                            <span class="data-value-clean">
                                <i class="fas fa-phone-alt"></i> {{ $user->phone }}
                                @if(auth()->id() === $user->id)
                                    <span class="badge {{ $showPhone ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} ms-1" style="font-size: 0.7rem;">
                                        <i class="fas {{ $showPhone ? 'fa-globe' : 'fa-eye-slash' }} me-1"></i>{{ $showPhone ? 'Publik' : 'Privat' }}
                                    </span>
                                @endif
                            </span>
                        </div>
                    @endif

                    @if($user->address && ($isOwner || $showAddress))
                        <div class="data-item-clean">
                            <span class="data-label-clean">Domisili</span>
                            <span class="data-value-clean">
                                <i class="fas fa-map-pin"></i> {{ $user->address }}
                                @if(auth()->id() === $user->id)
                                    <span class="badge {{ $showAddress ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} ms-1" style="font-size: 0.7rem;">
                                        <i class="fas {{ $showAddress ? 'fa-globe' : 'fa-eye-slash' }} me-1"></i>{{ $showAddress ? 'Publik' : 'Privat' }}
                                    </span>
                                @endif
                            </span>
                        </div>
                    @endif

                    @if($user->employee_id)
                        <div class="data-item-clean">
                            <span class="data-label-clean">ID Pegawai</span>
                            <span class="data-value-clean"><i class="fas fa-id-badge"></i> {{ $user->employee_id }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kolom Kanan: Data Pribadi -->
            <div class="card-clean">
                <h2 class="card-header-clean">
                    <i class="fas fa-user-circle"></i> Data Pribadi
                </h2>
                <div class="data-list-clean">
                    @if($user->birth_date)
                        <div class="data-item-clean">
                            <span class="data-label-clean">Tanggal Lahir</span>
                            <span class="data-value-clean"><i class="fas fa-calendar-day"></i>
                                {{ $user->birth_date->translatedFormat('d F Y') }}</span>
                        </div>
                    @endif
                    @if($user->gender)
                        <div class="data-item-clean">
                            <span class="data-label-clean">Jenis Kelamin</span>
                            <span class="data-value-clean"><i class="fas fa-venus-mars"></i>
                                {{ $user->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</span>
                        </div>
                    @endif
                    <div class="data-item-clean">
                        <span class="data-label-clean">Terdaftar Pada</span>
                        <span class="data-value-clean"><i class="fas fa-clock"></i>
                            {{ $user->created_at->translatedFormat('d F Y') }}</span>
                    </div>
                    @if($user->last_login_at)
                        <div class="data-item-clean">
                            <span class="data-label-clean">Login Terakhir</span>
                            <span class="data-value-clean"><i class="fas fa-sign-in-alt"></i>
                                {{ $user->last_login_at->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kolom Penuh: Statistik (Jika Bukan Warga) -->
            @if(!$user->isCitizen())
                <div class="card-clean" style="grid-column: 1 / -1;">
                    <h2 class="card-header-clean">
                        <i class="fas fa-chart-line"></i> Ringkasan Aktivitas
                    </h2>
                    <div class="stats-clean-grid">
                        @if($user->isAdmin())
                            <div class="stat-item-clean">
                                <div class="stat-number-clean">{{ \App\Models\User::count() }}</div>
                                <div class="stat-label-clean"><i class="fas fa-users"></i> Pengguna Terdaftar</div>
                            </div>
                            <div class="stat-item-clean">
                                <div class="stat-number-clean">{{ \App\Models\Report::count() }}</div>
                                <div class="stat-label-clean"><i class="fas fa-file-alt"></i> Total Laporan Publik</div>
                            </div>
                            <div class="stat-item-clean">
                                <div class="stat-number-clean">{{ \App\Models\Complaint::count() }}</div>
                                <div class="stat-label-clean"><i class="fas fa-exclamation-circle"></i> Aspirasi / Keluhan</div>
                            </div>
                            <div class="stat-item-clean">
                                <div class="stat-number-clean">{{ \App\Models\Department::count() }}</div>
                                <div class="stat-label-clean"><i class="fas fa-building"></i> Instansi Terhubung</div>
                            </div>
                        @else
                            <div class="stat-item-clean">
                                <div class="stat-number-clean">{{ $user->assignedReports()->count() }}</div>
                                <div class="stat-label-clean"><i class="fas fa-tasks"></i> Total Tugas Masuk</div>
                            </div>
                            <div class="stat-item-clean">
                                <div class="stat-number-clean">{{ $user->assignedReports()->where('status', 'resolved')->count() }}
                                </div>
                                <div class="stat-label-clean"><i class="fas fa-check-double"></i> Berhasil Diselesaikan</div>
                            </div>
                            <div class="stat-item-clean" style="grid-column: 1 / -1;">
                                <div class="stat-number-clean">
                                    {{ $user->assignedReports()->whereIn('status', ['submitted', 'pending', 'in_progress'])->count() }}
                                </div>
                                <div class="stat-label-clean"><i class="fas fa-spinner"></i> Sedang Dalam Penanganan</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection