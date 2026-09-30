<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ValidateFileUpload
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allFiles = $request->allFiles();

        if (empty($allFiles)) {
            return $next($request);
        }

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip-compressed',
            'text/plain',
            'text/csv',
        ];

        $dangerousExtensions = [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'sh',
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'py', 'pl', 'cgi', 'asp', 'aspx', 'jsp', 'htm', 'html', 'shtml',
            'svg', 'vbe', 'wsf', 'wsh', 'ps1', 'jar', 'msi', 'bin', 'dll',
        ];

        $maxSizeBytes = 5 * 1024 * 1024; // 5MB

        // Flatten all uploaded files recursively
        $filesToValidate = $this->flattenFiles($allFiles);

        foreach ($filesToValidate as $file) {
            if (! ($file instanceof UploadedFile) || ! $file->isValid()) {
                continue;
            }

            // 1. Check file size
            if ($file->getSize() > $maxSizeBytes) {
                return $this->errorResponse($request, 'Ukuran berkas terlalu besar. Maksimal 5MB.');
            }

            // 2. Check MIME type
            if (! in_array($file->getMimeType(), $allowedMimes, true)) {
                return $this->errorResponse($request, 'Format berkas tidak diizinkan. Format yang didukung: JPG, PNG, GIF, WEBP, PDF, DOC, DOCX, XLS, XLSX, ZIP, CSV, TXT.');
            }

            // 3. Inspect original filename for null byte, directory traversal, and multi/double extensions
            $originalName = strtolower($file->getClientOriginalName());

            if (str_contains($originalName, "\0") || str_contains($originalName, '..') || str_contains($originalName, '/')) {
                return $this->errorResponse($request, 'Nama berkas tidak valid atau mencurigakan.');
            }

            // Split filename by dots to detect double extensions (e.g., "malware.php.jpg")
            $parts = explode('.', $originalName);

            // Check final extension
            $finalExtension = strtolower($file->getClientOriginalExtension());
            if (in_array($finalExtension, $dangerousExtensions, true)) {
                return $this->errorResponse($request, 'Ekstensi berkas tidak diizinkan demi keamanan.');
            }

            // Check all intermediate extensions for double extension attack
            if (count($parts) > 2) {
                // Remove first part (filename base)
                array_shift($parts);
                foreach ($parts as $extensionPart) {
                    $cleanedPart = trim($extensionPart);
                    if (in_array($cleanedPart, $dangerousExtensions, true)) {
                        return $this->errorResponse($request, 'Berkas dengan ekstensi ganda berbahaya (double extension) tidak diizinkan.');
                    }
                }
            }
        }

        return $next($request);
    }

    /**
     * Flatten nested array of files into a 1D list of UploadedFile instances.
     *
     * @return array<int, UploadedFile>
     */
    private function flattenFiles(array $files): array
    {
        $flattened = [];
        array_walk_recursive($files, function ($item) use (&$flattened) {
            if ($item instanceof UploadedFile) {
                $flattened[] = $item;
            }
        });

        return $flattened;
    }

    /**
     * Format standardized error response based on request expectation.
     */
    private function errorResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    'attachments' => [$message],
                ],
            ], 422);
        }

        return back()->withErrors(['attachments' => $message])->withInput();
    }
}
