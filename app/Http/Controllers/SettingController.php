<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Setting::orderBy('sort_order')->orderBy('id');

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }
        if ($request->has('key')) {
            $query->where('key', $request->key);
        }
        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group'      => ['required', 'string', 'in:section,sub_section,severity,remark_template,resolution_template,service_repair_note,task_status,report_settings,smtp_settings'],
            'key'        => ['nullable', 'string'],
            'value'      => ['required', 'string'],
            'meta'       => ['nullable', 'array'],
            'is_active'  => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $setting = Setting::create($data);
        return response()->json($setting, 201);
    }

    public function update(Request $request, Setting $setting): JsonResponse
    {
        $data = $request->validate([
            'key'        => ['nullable', 'string'],
            'value'      => ['sometimes', 'string'],
            'meta'       => ['nullable', 'array'],
            'is_active'  => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $setting->update($data);
        return response()->json($setting);
    }

    public function destroy(Setting $setting): JsonResponse
    {
        if ($setting->group === 'task_status') {
            $inUse = Task::where('status', $setting->key)->exists();
            if ($inUse) {
                return response()->json(['message' => 'Cannot delete: tasks are using this status.'], 422);
            }
        }
        $setting->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function trashed(Request $request): JsonResponse
    {
        $query = Setting::onlyTrashed()->orderBy('deleted_at', 'desc');

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        return response()->json($query->get());
    }

    public function restore(int $id): JsonResponse
    {
        $setting = Setting::onlyTrashed()->findOrFail($id);
        $setting->restore();
        return response()->json($setting);
    }
}
