<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Landing Page (includes public service-performance stats)
Route::get('/', [App\Http\Controllers\PublicTransparencyController::class, 'landing'])->name('landing');

// Public ticket tracking (no login); throttled to make ticket-number guessing impractical
Route::get('/lacak', [App\Http\Controllers\PublicTransparencyController::class, 'track'])
    ->middleware('throttle:30,1')
    ->name('public.track');

// Authentication Routes
Auth::routes();

// Logout confirmation page
Route::get('/logout/confirm', function () {
    return view('auth.logout');
})->name('logout.confirm');

// Dashboard Routes based on user role
Route::middleware(['auth'])->group(function () {
    // Redirect based on user role
    Route::get('/home', function () {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isDepartmentHead() || $user->isStaff()) {
            return redirect()->route('administration.dashboard');
        } else {
            return redirect()->route('citizen.dashboard');
        }
    })->name('home');

    // Admin Dashboard Routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [App\Http\Controllers\AdminDashboardController::class, 'reports'])->name('reports');
        Route::get('/reports/{id}/download', [App\Http\Controllers\AdminDashboardController::class, 'downloadReport'])->name('reports.download');
        Route::get('/reports/{id}/download-pdf', [App\Http\Controllers\DownloadController::class, 'downloadReportAsPdf'])->name('reports.download_pdf');
        Route::get('/reports/{id}/download-csv', [App\Http\Controllers\DownloadController::class, 'downloadReportAsCsv'])->name('reports.download_csv');
        Route::get('/reports/{id}/download-attachments', [App\Http\Controllers\DownloadController::class, 'downloadReportAttachments'])->name('reports.download_attachments');
        Route::post('/reports/{id}/send-to-head', [App\Http\Controllers\AdminDashboardController::class, 'sendReportToHead'])->name('reports.send_to_head');
        Route::get('/reports/{id}/edit', [App\Http\Controllers\AdminDashboardController::class, 'editReport'])->name('reports.edit');
        Route::put('/reports/{id}', [App\Http\Controllers\AdminDashboardController::class, 'updateReport'])->name('reports.update');
        Route::delete('/reports/{id}', [App\Http\Controllers\AdminDashboardController::class, 'deleteReport'])->name('reports.delete');
        Route::get('/complaints', [App\Http\Controllers\AdminDashboardController::class, 'complaints'])->name('complaints');
        // Tambahan fitur admin untuk keluhan
        Route::post('/complaints/{id}/confirm', [App\Http\Controllers\AdminDashboardController::class, 'confirmComplaint'])->name('complaints.confirm');
        Route::post('/complaints/{id}/assign', [App\Http\Controllers\AdminDashboardController::class, 'assignComplaint'])->name('complaints.assign');
        Route::get('/complaints/{id}/edit', [App\Http\Controllers\AdminDashboardController::class, 'editComplaint'])->name('complaints.edit');
        Route::put('/complaints/{id}', [App\Http\Controllers\AdminDashboardController::class, 'updateComplaint'])->name('complaints.update');
        Route::delete('/complaints/{id}', [App\Http\Controllers\AdminDashboardController::class, 'deleteComplaint'])->name('complaints.delete');
        Route::get('/users', [App\Http\Controllers\AdminDashboardController::class, 'users'])->name('users');
        Route::post('/users/{id}/toggle-status', [App\Http\Controllers\AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle_status');
        Route::get('/departments', [App\Http\Controllers\DepartmentController::class, 'index'])->name('departments');
        Route::post('/departments', [App\Http\Controllers\DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{id}', [App\Http\Controllers\DepartmentController::class, 'update'])->name('departments.update');
        Route::post('/departments/{id}/toggle-status', [App\Http\Controllers\DepartmentController::class, 'toggleStatus'])->name('departments.toggle_status');
        Route::delete('/departments/{id}', [App\Http\Controllers\DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::get('/monitoring', [App\Http\Controllers\AdminDashboardController::class, 'monitoring'])->name('monitoring');
    });

    // Administration Dashboard Routes (Department Head or Staff)
    Route::middleware(['administration_access'])->prefix('administration')->name('administration.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AdministrationDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [App\Http\Controllers\AdministrationDashboardController::class, 'reports'])->name('reports');
        Route::get('/reports/{id}/download', [App\Http\Controllers\AdministrationDashboardController::class, 'downloadReport'])->name('reports.download');
        Route::get('/complaints', [App\Http\Controllers\AdministrationDashboardController::class, 'complaints'])->name('complaints');
        Route::get('/staff', [App\Http\Controllers\AdministrationDashboardController::class, 'staff'])->name('staff');
        Route::post('/reports/{id}/confirm-to-admin', [App\Http\Controllers\AdministrationDashboardController::class, 'confirmToAdmin'])->name('reports.confirm_to_admin');
        Route::post('/reports/{id}/assign', [App\Http\Controllers\AdministrationDashboardController::class, 'assignReport'])->name('reports.assign');
        Route::post('/complaints/{id}/assign', [App\Http\Controllers\AdministrationDashboardController::class, 'assignComplaint'])->name('complaints.assign');
        Route::post('/complaints/{id}/resolve', [App\Http\Controllers\AdministrationDashboardController::class, 'resolveComplaint'])->name('complaints.resolve');
    });

    // Staff Routes (alias to administration for testing compatibility)
    Route::middleware(['staff'])->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AdministrationDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [App\Http\Controllers\AdministrationDashboardController::class, 'reports'])->name('reports.index');
    });

    // Department Head Routes (alias to administration for testing compatibility)
    Route::middleware(['department_head'])->prefix('department')->name('department.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AdministrationDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [App\Http\Controllers\AdministrationDashboardController::class, 'reports'])->name('reports.index');
    });

    // Citizen Dashboard Routes
    Route::middleware(['citizen'])->prefix('citizen')->name('citizen.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\CitizenDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [App\Http\Controllers\CitizenDashboardController::class, 'myReports'])->name('reports.index');
        Route::get('/reports/create', [App\Http\Controllers\CitizenDashboardController::class, 'createReport'])->name('reports.create');
        Route::post('/reports', [App\Http\Controllers\CitizenDashboardController::class, 'storeReport'])
            ->middleware(['throttle:10,1', 'validate_upload'])
            ->name('reports.store');
        Route::get('/reports/{id}', [App\Http\Controllers\CitizenDashboardController::class, 'showReport'])->name('reports.show');
        Route::get('/complaints', [App\Http\Controllers\CitizenDashboardController::class, 'myComplaints'])->name('complaints.index');
        Route::get('/complaints/create', [App\Http\Controllers\CitizenDashboardController::class, 'createComplaint'])->name('complaints.create');
        Route::post('/complaints', [App\Http\Controllers\CitizenDashboardController::class, 'storeComplaint'])
            ->middleware(['throttle:10,1', 'validate_upload'])
            ->name('complaints.store');
        Route::get('/complaints/{id}', [App\Http\Controllers\CitizenDashboardController::class, 'showComplaint'])->name('complaints.show');
    });

    // Profile Routes (untuk semua authenticated users)
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])
        ->middleware(['validate_upload'])
        ->name('profile.update');
    Route::get('/profile/settings', [App\Http\Controllers\ProfileController::class, 'settings'])->name('profile.settings');
    Route::put('/profile/settings', [App\Http\Controllers\ProfileController::class, 'updateSettings'])->name('profile.settings.update');
    Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'changePassword'])
        ->middleware('throttle:6,1')
        ->name('profile.password.update');
    Route::delete('/profile/avatar', [App\Http\Controllers\ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

    // Avatar fallback route (serve from storage if public symlink fails)
    Route::get('/avatar/{filename}', function ($filename) {
        $filename = basename($filename);
        $relative = 'avatars/'.$filename;
        if (! Storage::disk('public')->exists($relative)) {
            abort(404);
        }
        $full = storage_path('app/public/'.$relative);
        $mime = File::mimeType($full) ?: 'image/png';

        return response()->file($full, ['Content-Type' => $mime]);
    })->where('filename', '[A-Za-z0-9_\-\.]+')->name('avatar.show');
});

// File Management Routes
Route::middleware(['auth'])->group(function () {
    // File viewing and downloading routes
    Route::get('/files/{type}/{id}', [App\Http\Controllers\FileController::class, 'viewReportFiles'])->name('files.view');
    Route::get('/files/{type}/{id}/download/{filename}', [App\Http\Controllers\FileController::class, 'downloadFile'])->name('files.download');
    Route::get('/files/{type}/{id}/preview/{filename}', [App\Http\Controllers\FileController::class, 'previewImage'])->name('files.preview_image');
    Route::get('/files/{type}/{id}/view-preview/{filename}', [App\Http\Controllers\FileController::class, 'previewImage'])->name('files.preview');
    Route::get('/files/{type}/{id}/download-all', [App\Http\Controllers\FileController::class, 'downloadAllFiles'])->name('files.download_all');

    // Report Document Downloads (PDF & CSV)
    Route::get('/reports/{id}/download-pdf', [App\Http\Controllers\DownloadController::class, 'downloadReportAsPdf'])->name('reports.download_pdf');
    Route::get('/reports/{id}/download-csv', [App\Http\Controllers\DownloadController::class, 'downloadReportAsCsv'])->name('reports.download_csv');
});

// Workflow Routes
Route::middleware(['auth'])->group(function () {
    // General workflow routes accessible to report participants
    Route::post('/workflow/reports/{id}/comment', [App\Http\Controllers\WorkflowController::class, 'addComment'])
        ->middleware(['validate_upload'])
        ->name('workflow.reports.comment');
    Route::post('/workflow/reports/{id}/reopen', [App\Http\Controllers\WorkflowController::class, 'reopenReport'])
        ->name('workflow.reports.reopen');
    Route::post('/workflow/reports/{id}/provide-info', [App\Http\Controllers\WorkflowController::class, 'provideAdditionalInfo'])
        ->middleware(['validate_upload'])
        ->name('workflow.reports.provide_info');
    Route::post('/workflow/reports/{id}/citizen-close', [App\Http\Controllers\WorkflowController::class, 'citizenConfirmResolved'])
        ->name('workflow.reports.citizen_close');
    Route::get('/workflow/reports/{id}/history', [App\Http\Controllers\WorkflowController::class, 'getWorkflowHistory'])
        ->name('workflow.reports.history');

    // Administrative report workflow routes (defense-in-depth: restricted to Staff, Department Head, and Admin)
    Route::middleware(['administration_access'])->group(function () {
        Route::post('/workflow/reports/{id}/verify', [App\Http\Controllers\WorkflowController::class, 'verifyReport'])->name('workflow.reports.verify');
        Route::post('/workflow/reports/{id}/reject', [App\Http\Controllers\WorkflowController::class, 'rejectReport'])->name('workflow.reports.reject');
        Route::post('/workflow/reports/{id}/start-work', [App\Http\Controllers\WorkflowController::class, 'startWork'])->name('workflow.reports.start_work');
        Route::post('/workflow/reports/{id}/awaiting-info', [App\Http\Controllers\WorkflowController::class, 'setAwaitingInfo'])->name('workflow.reports.awaiting_info');
    });

    // Admin-only workflow management routes
    Route::middleware(['admin'])->group(function () {
        Route::post('/workflow/reports/{id}/admin-assign-staff', [App\Http\Controllers\WorkflowManagementController::class, 'adminAssignToStaff'])->name('workflow.reports.admin_assign_staff');
        Route::post('/workflow/reports/{id}/admin-assign-head', [App\Http\Controllers\WorkflowManagementController::class, 'adminAssignToHead'])->name('workflow.reports.admin_assign_head');
        Route::post('/workflow/reports/{id}/admin-approve-close', [App\Http\Controllers\WorkflowManagementController::class, 'adminApproveAndClose'])->name('workflow.reports.admin_approve_close');
        Route::post('/workflow/reports/{id}/admin-reject-staff', [App\Http\Controllers\WorkflowManagementController::class, 'adminRejectToStaff'])->name('workflow.reports.admin_reject_staff');
    });

    // Department Head-only workflow route
    Route::middleware(['department_head'])->group(function () {
        Route::post('/workflow/reports/{id}/head-review-return', [App\Http\Controllers\WorkflowManagementController::class, 'headReviewAndReturn'])->name('workflow.reports.head_review_return');
    });

    // Staff workflow route
    Route::middleware(['staff'])->group(function () {
        Route::post('/workflow/reports/{id}/staff-confirm-admin', [App\Http\Controllers\WorkflowManagementController::class, 'staffConfirmToAdmin'])->name('workflow.reports.staff_confirm_admin');
    });

    // Administration (Department Head or Staff) workflow route
    Route::middleware(['administration_access'])->group(function () {
        Route::post('/workflow/reports/{id}/staff-confirm-forward', [App\Http\Controllers\WorkflowManagementController::class, 'staffConfirmAndForward'])->name('workflow.reports.staff_confirm_forward');
    });
});

// API Routes for AJAX requests
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/departments', [App\Http\Controllers\DepartmentController::class, 'apiIndex'])->name('departments');

    // Administrative API endpoints restricted to admin
    Route::middleware(['admin'])->group(function () {
        Route::get('/reports/stats', [App\Http\Controllers\ReportController::class, 'stats'])->name('reports.stats');
        Route::get('/reports/kpi', [App\Http\Controllers\ReportController::class, 'kpiDashboard'])->name('reports.kpi');
        Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
        Route::get('/complaints/stats', [App\Http\Controllers\ComplaintController::class, 'stats'])->name('complaints.stats');
    });
});
