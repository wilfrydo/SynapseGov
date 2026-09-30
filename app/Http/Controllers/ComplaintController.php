<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = auth()->check() ? auth()->user()->getSettings('items_per_page', 20) : 20;
        $complaints = Complaint::with(['user', 'department', 'assignedUser'])
            ->latest()
            ->paginate($perPage);

        return view('admin.complaints', compact('complaints'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->get();

        return view('citizen.complaints.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'location' => 'nullable|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip|max:5120',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = Attachments::store($file, 'complaints');
            }
        }

        $complaint = Complaint::create([
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'department_id' => $request->department_id,
            'location' => $request->location,
            'priority' => $request->priority,
            'user_id' => Auth::id(),
            'status' => 'submitted',
            'attachments' => $attachments ?: null,
        ]);

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Keluhan berhasil dikirim');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $complaint = Complaint::with(['user', 'department', 'assignedUser'])->findOrFail($id);

        // Check if user can view this complaint
        $user = Auth::user();
        if ($user->isCitizen() && (int) $complaint->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized access to complaint.');
        }
        if (in_array($user->role, ['department_head', 'staff']) && (int) $complaint->department_id !== (int) $user->department_id && (int) $complaint->assigned_to !== (int) $user->id) {
            abort(403, 'Unauthorized access to complaint from another department.');
        }

        return view('citizen.complaints.show', compact('complaint'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $complaint = Complaint::findOrFail($id);
        $departments = Department::all();
        $staff = User::where('role', 'staff')->get();

        return view('admin.complaints_edit', compact('complaint', 'departments', 'staff'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'status' => 'required|in:submitted,pending,investigating,in_progress,resolved,closed,rejected',
            'priority' => 'required|in:low,medium,high,urgent',
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'assigned_to' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
        ]);

        $complaint = Complaint::findOrFail($id);
        $oldData = $complaint->toArray();

        $complaint->update($request->only([
            'title', 'description', 'category', 'status', 'priority', 'department_id', 'assigned_to', 'location',
        ]));

        // Audit log for direct admin update
        AuditLog::create([
            'auditable_type' => Complaint::class,
            'auditable_id' => $complaint->id,
            'user_id' => Auth::id(),
            'event' => 'updated_by_admin',
            'old_values' => $oldData,
            'new_values' => $complaint->fresh()->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('admin.complaints')->with('success', 'Keluhan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $complaint = Complaint::findOrFail($id);

        // Clean up file attachments
        if (! empty($complaint->attachments) && is_array($complaint->attachments)) {
            foreach ($complaint->attachments as $attachment) {
                Attachments::delete($attachment);
            }
        }

        // Audit log before delete
        AuditLog::create([
            'auditable_type' => Complaint::class,
            'auditable_id' => $complaint->id,
            'user_id' => Auth::id(),
            'event' => 'deleted_by_admin',
            'old_values' => $complaint->toArray(),
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $complaint->delete();

        return redirect()->route('admin.complaints')->with('success', 'Keluhan berhasil dihapus.');
    }

    /**
     * API endpoint for complaint statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Complaint::count(),
            'pending' => Complaint::where('status', 'pending')->count(),
            'investigating' => Complaint::where('status', 'investigating')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'by_priority' => Complaint::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority'),
            'by_category' => Complaint::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
        ];

        return response()->json($stats);
    }
}
