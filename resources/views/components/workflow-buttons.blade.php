@props(['report', 'user', 'staffList' => null, 'mode' => 'dropdown'])

<div class="workflow-buttons mt-2">
    @if($mode === 'dropdown')
        <div class="dropdown">
            <button class="btn btn-sm btn-dark dropdown-toggle rounded-pill shadow-sm px-3" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                <i class="fas fa-tasks me-1"></i> Tindak Lanjut
            </button>
<style>
    .compact-dropdown {
        padding: 0.35rem;
    }
    .compact-dropdown .dropdown-item {
        padding-top: 0.35rem !important;
        padding-bottom: 0.35rem !important;
        font-size: 0.85rem;
        border-radius: 6px;
        margin-bottom: 2px;
    }
    .compact-dropdown .dropdown-header {
        padding: 0.25rem 0.75rem;
    }
</style>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 compact-dropdown" style="border-radius: 12px; min-width: 180px; margin-top: 8px;">
                <li class="dropdown-header text-uppercase fw-bold text-primary mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Status: {{ \App\Models\Report::statusLabel($report->status) }}</li>
                
                @if($user->isAdmin())
                    {{-- Admin Actions --}}
                    @if(in_array($report->status, ['submitted', 'pending', 'awaiting_info']))
                        <li>
                            <form action="{{ route('workflow.reports.verify', $report->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-success" onclick="return confirm('Apakah Anda yakin laporan ini layak dan akan diverifikasi?')">
                                    <i class="fas fa-check w-20px me-2"></i> Verifikasi (Layak)
                                </button>
                            </form>
                        </li>
                        @if($report->status !== 'awaiting_info')
                        <li>
                            <button type="button" class="dropdown-item py-2 text-warning" data-bs-toggle="modal" data-bs-target="#awaitingInfoModal{{ $report->id }}">
                                <i class="fas fa-question-circle w-20px me-2"></i> Minta Info Tambahan
                            </button>
                        </li>
                        @endif
                        <li>
                            <button type="button" class="dropdown-item py-2 text-danger" data-bs-toggle="modal" data-bs-target="#rejectInitialModal{{ $report->id }}">
                                <i class="fas fa-times w-20px me-2"></i> Tolak (Tidak Layak)
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                                <i class="fas fa-user-plus text-primary w-20px me-2"></i> Disposisi ke Staf
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#assignHeadModal{{ $report->id }}">
                                <i class="fas fa-user-tie text-info w-20px me-2"></i> Disposisi ke Kepala Dinas
                            </button>
                        </li>
                    @elseif($report->status === 'verified')
                        <li>
                            <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#assignHeadModal{{ $report->id }}">
                                <i class="fas fa-user-tie text-info w-20px me-2"></i> Disposisi ke Kepala Dinas
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                                <i class="fas fa-user-plus text-primary w-20px me-2"></i> Tugaskan Staf Lapangan
                            </button>
                        </li>
                    @elseif($report->status === 'awaiting_admin_approval')
                        <li>
                            <button type="button" class="dropdown-item py-2 text-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $report->id }}">
                                <i class="fas fa-check w-20px me-2"></i> Setujui (Selesai)
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item py-2 text-warning" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $report->id }}">
                                <i class="fas fa-undo w-20px me-2"></i> Tolak / Perlu Revisi
                            </button>
                        </li>
                    @else
                        <li><span class="dropdown-item text-muted"><i class="fas fa-info-circle me-2"></i>Tidak ada aksi</span></li>
                    @endif

                @elseif($user->isStaff())
                    {{-- Staff Actions --}}
                    @php
                        $isAssignedStaff = (int) $report->assigned_to === (int) $user->id;
                        $isInDept = (int) $report->department_id === (int) $user->department_id;
                        $canAct = $isAssignedStaff || $isInDept;
                        // Mirror the controller guards so staff never see a button that would be rejected
                        $canStart = $canAct && in_array($report->status, ['assigned', 'reviewed', 'needs_revision']); // WorkflowController::startWork
                        $canAskInfo = $isAssignedStaff && in_array($report->status, ['assigned', 'in_progress']); // WorkflowController::setAwaitingInfo
                        $canSubmitResult = $canAct && in_array($report->status, ['assigned', 'reviewed', 'in_progress', 'needs_revision']); // staffConfirmToAdmin
                    @endphp
                    @if($canStart || $canAskInfo || $canSubmitResult)
                        @if($canStart)
                        <li>
                            <form action="{{ route('workflow.reports.start_work', $report->id) }}" method="POST">
                                @csrf
                                @if($report->status === 'needs_revision')
                                    <button type="submit" class="dropdown-item py-2 text-warning">
                                        <i class="fas fa-tools w-20px me-2"></i> Kerjakan Revisi
                                    </button>
                                @else
                                    <button type="submit" class="dropdown-item py-2 text-primary">
                                        <i class="fas fa-play w-20px me-2"></i> Mulai Kerjakan
                                    </button>
                                @endif
                            </form>
                        </li>
                        @endif
                        @if($canAskInfo)
                        <li>
                            <button type="button" class="dropdown-item py-2 text-warning" data-bs-toggle="modal" data-bs-target="#awaitingInfoModal{{ $report->id }}">
                                <i class="fas fa-question-circle w-20px me-2"></i> Minta Info Tambahan
                            </button>
                        </li>
                        @endif
                        @if($canSubmitResult)
                        <li>
                            <button type="button" class="dropdown-item py-2 text-success" data-bs-toggle="modal" data-bs-target="#completeModal{{ $report->id }}">
                                <i class="fas fa-check-circle w-20px me-2"></i> Ajukan Hasil & Bukti
                            </button>
                        </li>
                        @endif
                    @else
                        <li><span class="dropdown-item text-muted"><i class="fas fa-info-circle me-2"></i>Tidak ada aksi</span></li>
                    @endif

                @elseif($user->isDepartmentHead())
                    {{-- Department Head Actions --}}
                    @php
                        $canHeadAct = ((int) $report->assigned_to === (int) $user->id) || ((int) $report->department_id === (int) $user->department_id);
                    @endphp
                    @if($canHeadAct && in_array($report->status, ['submitted', 'pending', 'verified', 'assigned']))
                        <li>
                            <button type="button" class="dropdown-item py-2 text-primary" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                                <i class="fas fa-user-plus w-20px me-2"></i> Disposisi ke Staf
                            </button>
                        </li>
                        @if(in_array($report->status, ['verified', 'assigned']))
                        <li>
                            <button type="button" class="dropdown-item py-2 text-warning" data-bs-toggle="modal" data-bs-target="#reviewReturnModal{{ $report->id }}">
                                <i class="fas fa-undo w-20px me-2 text-secondary"></i> Review / Kembalikan
                            </button>
                        </li>
                        @endif
                    @elseif($canHeadAct && in_array($report->status, ['in_progress', 'reviewed', 'needs_revision']))
                        <li>
                            <button type="button" class="dropdown-item py-2 text-primary" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                                <i class="fas fa-user-plus w-20px me-2"></i> Alihkan ke Staf Lain
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item py-2 text-warning" data-bs-toggle="modal" data-bs-target="#reviewReturnModal{{ $report->id }}">
                                <i class="fas fa-undo w-20px me-2"></i> Review & Kembalikan ke Staf
                            </button>
                        </li>
                    @elseif($canHeadAct && $report->status === 'awaiting_admin_approval')
                        <li>
                            <form action="{{ route('administration.reports.confirm_to_admin', $report->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-success" onclick="return confirm('Konfirmasi dan rekomendasikan penyelesaian laporan ini ke Admin Utama?')">
                                    <i class="fas fa-check-circle w-20px me-2"></i> Rekomendasikan ke Admin
                                </button>
                            </form>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item py-2 text-danger" data-bs-toggle="modal" data-bs-target="#reviewReturnModal{{ $report->id }}">
                                <i class="fas fa-undo w-20px me-2"></i> Kembalikan untuk Revisi Staf
                            </button>
                        </li>
                    @else
                        <li><span class="dropdown-item text-muted"><i class="fas fa-info-circle me-2"></i>Tidak ada aksi</span></li>
                    @endif
                @endif
            </ul>
        </div>
    @else
        {{-- Standard Button Group Mode for Details Page --}}
        <div class="d-flex flex-wrap gap-2">
            @if($user->isAdmin())
                @if(in_array($report->status, ['submitted', 'pending', 'awaiting_info']))
                    <form action="{{ route('workflow.reports.verify', $report->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm shadow-sm" onclick="return confirm('Apakah Anda yakin laporan ini layak dan akan diverifikasi?')">
                            <i class="fas fa-check me-1"></i> Verifikasi (Layak)
                        </button>
                    </form>
                    @if($report->status !== 'awaiting_info')
                    <button type="button" class="btn btn-warning btn-sm text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#awaitingInfoModal{{ $report->id }}">
                        <i class="fas fa-question-circle me-1"></i> Minta Info
                    </button>
                    @endif
                    <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectInitialModal{{ $report->id }}">
                        <i class="fas fa-times me-1"></i> Tolak (Tidak Layak)
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                        <i class="fas fa-user-plus me-1"></i> Disposisi ke Staf
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#assignHeadModal{{ $report->id }}">
                        <i class="fas fa-user-tie me-1"></i> Disposisi ke Kepala Dinas
                    </button>
                @elseif($report->status === 'verified')
                    <button type="button" class="btn btn-info btn-sm text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#assignHeadModal{{ $report->id }}">
                        <i class="fas fa-user-tie me-1"></i> Disposisi ke Kepala Dinas
                    </button>
                    <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                        <i class="fas fa-user-plus me-1"></i> Tugaskan Staf Lapangan
                    </button>
                @elseif($report->status === 'awaiting_admin_approval')
                    <button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal{{ $report->id }}">
                        <i class="fas fa-check me-1"></i> Setujui (Selesai)
                    </button>
                    <button type="button" class="btn btn-warning btn-sm text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $report->id }}">
                        <i class="fas fa-undo me-1"></i> Perlu Revisi
                    </button>
                @endif
            @elseif($user->isStaff())
                @php
                    $isAssignedStaff = (int) $report->assigned_to === (int) $user->id;
                    $isInDept = (int) $report->department_id === (int) $user->department_id;
                    $canAct = $isAssignedStaff || $isInDept;
                    // Same guards as the dropdown mode above
                    $canStart = $canAct && in_array($report->status, ['assigned', 'reviewed', 'needs_revision']);
                    $canAskInfo = $isAssignedStaff && in_array($report->status, ['assigned', 'in_progress']);
                    $canSubmitResult = $canAct && in_array($report->status, ['assigned', 'reviewed', 'in_progress', 'needs_revision']);
                @endphp
                @if($canStart)
                    <form action="{{ route('workflow.reports.start_work', $report->id) }}" method="POST" class="d-inline">
                        @csrf
                        @if($report->status === 'needs_revision')
                            <button type="submit" class="btn btn-warning btn-sm text-dark shadow-sm">
                                <i class="fas fa-tools me-1"></i> Kerjakan Revisi
                            </button>
                        @else
                            <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                                <i class="fas fa-play me-1"></i> Mulai Kerjakan
                            </button>
                        @endif
                    </form>
                @endif
                @if($canAskInfo)
                    <button type="button" class="btn btn-warning btn-sm text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#awaitingInfoModal{{ $report->id }}">
                        <i class="fas fa-question-circle me-1"></i> Minta Info
                    </button>
                @endif
                @if($canSubmitResult)
                    <button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#completeModal{{ $report->id }}">
                        <i class="fas fa-check-circle me-1"></i> Ajukan Hasil & Bukti
                    </button>
                @endif
            @elseif($user->isDepartmentHead())
                @php
                    $canHeadAct = ((int) $report->assigned_to === (int) $user->id) || ((int) $report->department_id === (int) $user->department_id);
                @endphp
                @if($canHeadAct && in_array($report->status, ['submitted', 'pending', 'verified', 'assigned']))
                    <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                        <i class="fas fa-user-plus me-1"></i> Disposisi ke Staf
                    </button>
                    @if(in_array($report->status, ['verified', 'assigned']))
                    <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#reviewReturnModal{{ $report->id }}">
                        <i class="fas fa-undo me-1"></i> Kembalikan
                    </button>
                    @endif
                @elseif($canHeadAct && in_array($report->status, ['in_progress', 'reviewed', 'needs_revision']))
                    <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#assignStaffModal{{ $report->id }}">
                        <i class="fas fa-user-plus me-1"></i> Alihkan Staf
                    </button>
                    <button type="button" class="btn btn-warning btn-sm text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#reviewReturnModal{{ $report->id }}">
                        <i class="fas fa-undo me-1"></i> Review & Kembalikan
                    </button>
                @elseif($canHeadAct && $report->status === 'awaiting_admin_approval')
                    <form action="{{ route('administration.reports.confirm_to_admin', $report->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm shadow-sm" onclick="return confirm('Konfirmasi dan rekomendasikan laporan ini ke Admin Utama?')">
                            <i class="fas fa-check-circle me-1"></i> Rekomendasikan ke Admin
                        </button>
                    </form>
                    <button type="button" class="btn btn-warning btn-sm text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#reviewReturnModal{{ $report->id }}">
                        <i class="fas fa-undo me-1"></i> Minta Revisi Staf
                    </button>
                @endif
            @endif
        </div>
    @endif
</div>

{{-- Modals for Admin (deferred to end of body to avoid stacking issues) --}}
@if($user->isAdmin())
    @pushOnce('modals', 'workflow-modals-admin-'.$report->id)
        @include('components.modals.assign-staff', ['report' => $report, 'staffList' => $staffList])
        @include('components.modals.assign-head', ['report' => $report])
        @include('components.modals.approve-report', ['report' => $report])
        @include('components.modals.reject-report', ['report' => $report, 'staffList' => $staffList])
        @include('components.modals.awaiting-info', ['report' => $report])
        @include('components.modals.reject-initial', ['report' => $report])
    @endPushOnce
@endif

{{-- Modals for Staff (deferred) --}}
@if($user->isStaff())
    @pushOnce('modals', 'workflow-modals-staff-'.$report->id)
        @include('components.modals.confirm-forward', ['report' => $report])
        @include('components.modals.complete-report', ['report' => $report])
        @include('components.modals.awaiting-info', ['report' => $report])
    @endPushOnce
@endif

{{-- Modals for Department Head (deferred) --}}
@if($user->isDepartmentHead())
    @pushOnce('modals', 'workflow-modals-head-'.$report->id)
        @include('components.modals.assign-staff', ['report' => $report, 'staffList' => $staffList])
        @include('components.modals.review-return', ['report' => $report])
    @endPushOnce
@endif
