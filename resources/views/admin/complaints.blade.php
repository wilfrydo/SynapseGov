@extends('layouts.dashboard')

@section('title', 'Keluhan Admin')

@section('content')
@include('admin.heading')
<!-- Complaints Table -->
<div class="complaints-card">
    <div class="complaints-card-header">
        <i class="fas fa-list"></i>
        <h3>Daftar Keluhan ({{ $complaints->total() }} Total)</h3>
    </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Judul</th>
                                <th>Pengguna</th>
                                <th>Departemen</th>
                                <th>Status</th>
                                <th>Prioritas</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($complaints as $complaint)
                            <tr>
                                <td>{{ $complaint->id }}</td>
                                <td>
                                    <strong>{{ $complaint->title }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $complaint->description }}</small>
                                </td>
                                <td>{{ $complaint->user ? $complaint->user->name : 'N/A' }}</td>
                                <td>{{ $complaint->department ? $complaint->department->name : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $complaint->status == 'pending' ? 'warning' : ($complaint->status == 'resolved' ? 'success' : 'info') }}">
                                        {{ \App\Models\Report::statusLabel($complaint->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $complaint->priority == 'urgent' ? 'danger' : ($complaint->priority == 'high' ? 'warning' : ($complaint->priority == 'medium' ? 'info' : 'secondary')) }}">
                                        {{ \App\Models\Report::priorityLabel($complaint->priority) }}
                                    </span>
                                </td>
                                <td>{{ $complaint->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewComplaintModal{{ $complaint->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($complaint->status == 'pending')
                                        <form action="{{ route('admin.complaints.confirm', $complaint->id) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Konfirmasi">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#assignComplaintModal{{ $complaint->id }}" title="Assign ke Staff">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                        @endif
                                        <a href="{{ route('admin.complaints.edit', $complaint->id) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.complaints.delete', $complaint->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus keluhan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada keluhan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $complaints->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Complaint Modals -->
@foreach($complaints as $complaint)
<div class="modal fade" id="viewComplaintModal{{ $complaint->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Keluhan #{{ $complaint->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Keluhan</h6>
                        <p><strong>Judul:</strong> {{ $complaint->title }}</p>
                        <p><strong>Kategori:</strong> {{ $complaint->category }}</p>
                        <p><strong>Prioritas:</strong> 
                            <span class="badge bg-{{ $complaint->priority == 'urgent' ? 'danger' : ($complaint->priority == 'high' ? 'warning' : ($complaint->priority == 'medium' ? 'info' : 'secondary')) }}">
                                {{ \App\Models\Report::priorityLabel($complaint->priority) }}
                            </span>
                        </p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-{{ $complaint->status == 'pending' ? 'warning' : ($complaint->status == 'resolved' ? 'success' : 'info') }}">
                                {{ \App\Models\Report::statusLabel($complaint->status) }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Pengguna</h6>
                        <p><strong>Nama:</strong> {{ $complaint->user ? $complaint->user->name : 'Pengguna Dihapus' }}</p>
                        <p><strong>Email:</strong> {{ $complaint->user ? $complaint->user->email : '-' }}</p>
                        <p><strong>Departemen:</strong> {{ $complaint->department ? $complaint->department->name : 'N/A' }}</p>
                        <p><strong>Lokasi:</strong> {{ $complaint->location ?? 'Tidak disebutkan' }}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Deskripsi</h6>
                        <p>{{ $complaint->description }}</p>
                    </div>
                </div>
                @if($complaint->assignedUser)
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Ditugaskan ke:</h6>
                        <p>{{ $complaint->assignedUser->name }} ({{ $complaint->assignedUser->email }})</p>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Assign Complaint Modal -->
@foreach($complaints as $complaint)
<div class="modal fade" id="assignComplaintModal{{ $complaint->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tugaskan Keluhan #{{ $complaint->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.complaints.assign', $complaint->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assigned_to_{{ $complaint->id }}" class="form-label">Pilih Staff</label>
                        <select class="form-select" id="assigned_to_{{ $complaint->id }}" name="assigned_to" required>
                            <option value="">-- Pilih Staff --</option>
                            @foreach(\App\Models\User::where('role', 'staff')->where('is_active', true)->get() as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tugaskan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

