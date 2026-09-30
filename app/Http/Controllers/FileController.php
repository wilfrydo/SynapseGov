<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Report;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    /**
     * View uploaded files for a report or complaint
     */
    public function viewReportFiles($type, $id)
    {
        if ($type === 'report') {
            $reportable = Report::findOrFail($id);
        } else {
            $reportable = Complaint::findOrFail($id);
        }

        // Check if user has permission to view files
        Gate::authorize('view', $reportable);

        $files = $this->getFileDetails($reportable->attachments ?? []);

        return view('admin.files.view', [
            'reportable' => $reportable,
            'files' => $files,
            'type' => $type,
        ]);
    }

    /**
     * Download a specific file
     */
    public function downloadFile(Request $request, $type, $id, $filename)
    {
        try {
            $reportable = $type === 'report' ? Report::findOrFail($id) : Complaint::findOrFail($id);

            // Check if user has permission to download files
            Gate::authorize('download', $reportable);

            $filePath = $this->getFilePath($reportable, $filename);

            if (! $filePath) {
                abort(404, 'Berkas tidak ditemukan.');
            }

            return response()->download($filePath, basename($filePath));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Akses ditolak. Anda tidak memiliki izin untuk mengunduh berkas ini.',
                ], 403);
            }

            return back()->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengunduh berkas ini.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Berkas tidak ditemukan.',
                ], 404);
            }

            return back()->with('error', 'Berkas tidak ditemukan.');
        } catch (\Exception $e) {
            \Log::error('File Download Error', [
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Terjadi kesalahan saat mengunduh berkas.',
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan saat mengunduh berkas.');
        }
    }

    /**
     * Preview an image file
     */
    public function previewImage(Request $request, $type, $id, $filename)
    {
        $reportable = $type === 'report' ? Report::findOrFail($id) : Complaint::findOrFail($id);

        // Check if user has permission to view files
        Gate::authorize('preview', $reportable);

        $filePath = $this->getFilePath($reportable, $filename);

        if (! $filePath) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        // Support previewing images and PDF documents directly in the browser
        $previewExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($extension, $previewExtensions)) {
            abort(400, 'Format berkas tidak mendukung pratinjau.');
        }

        $mimeType = $extension === 'pdf' ? 'application/pdf' : (mime_content_type($filePath) ?: 'application/octet-stream');

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($filePath).'"',
        ]);
    }

    /**
     * Download all files as ZIP
     */
    public function downloadAllFiles(Request $request, $type, $id)
    {
        $reportable = $type === 'report' ? Report::findOrFail($id) : Complaint::findOrFail($id);

        // Check if user has permission to download files
        Gate::authorize('download', $reportable);

        $files = $reportable->attachments ?? [];

        if (empty($files)) {
            return redirect()->back()->with('error', 'Tidak ada berkas untuk diunduh.');
        }

        // Check if ZipArchive is available
        if (! class_exists('ZipArchive')) {
            return $this->downloadFilesAsList($reportable, $files);
        }

        try {
            $zip = new \ZipArchive;
            $zipPath = storage_path('app/temp/'.$reportable->ticket_no.'_files_'.time().'.zip');

            if (! is_dir(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return redirect()->back()->with('error', 'Gagal membuat arsip unduhan.');
            }

            foreach ($files as $file) {
                $fullPath = Attachments::path($file);
                if ($fullPath) {
                    $zip->addFile($fullPath, basename($file));
                }
            }

            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Zip creation failed: '.$e->getMessage());

            return $this->downloadFilesAsList($reportable, $files);
        }
    }

    /**
     * Download files as a list (fallback when ZipArchive is not available)
     */
    private function downloadFilesAsList($reportable, $files)
    {
        $fileList = [];

        foreach ($files as $file) {
            $fullPath = Attachments::path($file);
            if ($fullPath) {
                $fileList[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($fullPath),
                    'url' => route('files.download', [$reportable instanceof Report ? 'report' : 'complaint', $reportable->id, basename($file)]),
                ];
            }
        }

        $filename = $reportable->ticket_no.'_files_'.date('Y-m-d_H-i-s').'.json';

        return response()->json([
            'ticket_no' => $reportable->ticket_no,
            'title' => $reportable->title,
            'files' => $fileList,
            'total_files' => count($fileList),
            'download_instructions' => 'Download individual files using the URLs provided',
        ])
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Get file details with metadata
     */
    private function getFileDetails($attachments)
    {
        $files = [];

        foreach ($attachments as $file) {
            $fullPath = Attachments::path($file);

            if ($fullPath) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $fileInfo = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($fullPath),
                    'size_formatted' => $this->formatFileSize(filesize($fullPath)),
                    'extension' => $ext,
                    'mime_type' => mime_content_type($fullPath),
                    'is_image' => $this->isImageFile($file),
                    'is_pdf' => $ext === 'pdf',
                    'created_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
                ];

                $files[] = $fileInfo;
            }
        }

        return $files;
    }

    /**
     * Get file path for download
     */
    private function getFilePath($reportable, $filename)
    {
        $attachments = $reportable->attachments ?? [];
        $cleanFilename = basename(str_replace(['../', '..\\'], '', (string) $filename));

        foreach ($attachments as $file) {
            $normalized = str_replace('\\', '/', (string) $file);
            if (str_contains($normalized, '..')) {
                continue;
            }

            if (basename($normalized) === $cleanFilename) {
                return Attachments::path($normalized);
            }
        }

        return null;
    }

    /**
     * Check if file is an image
     */
    private function isImageFile($filename)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $imageExtensions);
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
