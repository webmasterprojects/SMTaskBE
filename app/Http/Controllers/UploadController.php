<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
    ];

    private const MAX_SIZE_KB = 10240; // 10 MB

    public function store(Request $request): JsonResponse
    {
        // Accept 'file' (single), 'files' (single or multiple)
        $incoming = $request->file('files') ?? $request->file('file');

        if (! $incoming) {
            return response()->json(['message' => 'No file provided.'], 422);
        }

        // Normalise to array so we handle both single and multiple uploads
        $files  = is_array($incoming) ? $incoming : [$incoming];
        $folder = $request->input('folder', 'uploads');
        $urls   = [];
        $paths  = [];

        foreach ($files as $file) {
            if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
                return response()->json(['message' => 'File too large (max 10 MB).'], 422);
            }

            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file->getRealPath());
            finfo_close($finfo);

            if (! in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
                return response()->json(['message' => 'File type not allowed.'], 422);
            }

            $ext      = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid() . '.' . $ext;
            $disk     = config('filesystems.default', 'public');

            try {
                $path = Storage::disk($disk)->putFileAs($folder, $file, $filename);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
            }

            if (! $path) {
                return response()->json(['message' => 'Upload failed: could not store file.'], 500);
            }

            $urls[]  = Storage::disk($disk)->url($path);
            $paths[] = $path;
        }

        return response()->json([
            'urls'  => $urls,
            'paths' => $paths,
            // convenience single-value aliases kept for backwards compat
            'url'  => $urls[0]  ?? null,
            'path'  => $paths[0] ?? null,
        ], 201);
    }
}
