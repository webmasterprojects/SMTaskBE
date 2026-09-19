<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUploadFilename;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    use BuildsUploadFilename;
    public function index(Task $task): JsonResponse
    {
        return response()->json(
            $task->attachments()->latest()->get()->map(fn ($a) => $this->format($a))
        );
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'file'  => ['required', 'file', 'max:20480'], // 20 MB
        ]);

        $file     = $request->file('file');
        $ext      = $file->getClientOriginalExtension() ?: 'bin';
        $filename = $this->buildUploadFilename($ext, $task->property_id, $task->id);
        $path     = $file->storeAs("task-attachments/{$task->id}", $filename, 'local');

        $attachment = $task->attachments()->create([
            'created_by'    => auth()->id(),
            'name'          => $request->name,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json($this->format($attachment), 201);
    }

    public function destroy(Task $task, TaskAttachment $attachment): JsonResponse
    {
        abort_if($attachment->task_id !== $task->id, 404);
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();
        return response()->json(null, 204);
    }

    public function download(TaskAttachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
        return Storage::disk('local')->download($attachment->path, basename($attachment->path), [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    // Distinct names used across all task attachments for autofill suggestions
    public function names(): JsonResponse
    {
        $names = TaskAttachment::query()
            ->distinct()
            ->orderBy('name')
            ->pluck('name');

        return response()->json($names);
    }

    private function format(TaskAttachment $a): array
    {
        return [
            'id'            => $a->id,
            'name'          => $a->name,
            'original_name' => $a->original_name,
            'mime_type'     => $a->mime_type,
            'size'          => $a->size,
            'created_at'    => $a->created_at,
            'download_url'  => url("/api/v1/task-attachments/{$a->id}/download"),
        ];
    }
}
