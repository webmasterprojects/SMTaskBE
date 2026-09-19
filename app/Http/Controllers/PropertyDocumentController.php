<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUploadFilename;
use App\Models\Property;
use App\Models\PropertyDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyDocumentController extends Controller
{
    use BuildsUploadFilename;

    public function index(Property $property): JsonResponse
    {
        return response()->json(
            $property->documents()->latest()->get()->map(fn ($d) => $this->format($d))
        );
    }

    public function store(Request $request, Property $property): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file     = $request->file('file');
        $ext      = $file->getClientOriginalExtension() ?: 'bin';
        $filename = $this->buildUploadFilename($ext, $property->id, null);
        $path     = $file->storeAs("property-documents/{$property->id}", $filename, 'local');

        $doc = $property->documents()->create([
            'created_by'    => auth()->id(),
            'name'          => $request->name,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json($this->format($doc), 201);
    }

    public function destroy(Property $property, PropertyDocument $document): JsonResponse
    {
        abort_if($document->property_id !== $property->id, 404);
        Storage::disk('local')->delete($document->path);
        $document->delete();
        return response()->json(null, 204);
    }

    public function download(PropertyDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        return Storage::disk('local')->download($document->path, basename($document->path), [
            'Content-Type' => $document->mime_type,
        ]);
    }

    public function names(): JsonResponse
    {
        return response()->json(
            PropertyDocument::query()->distinct()->orderBy('name')->pluck('name')
        );
    }

    private function format(PropertyDocument $d): array
    {
        return [
            'id'            => $d->id,
            'name'          => $d->name,
            'original_name' => $d->original_name,
            'mime_type'     => $d->mime_type,
            'size'          => $d->size,
            'created_at'    => $d->created_at,
            'download_url'  => url("/api/v1/property-documents/{$d->id}/download"),
        ];
    }
}
