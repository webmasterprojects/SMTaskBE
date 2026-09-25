<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function appPublic(): JsonResponse
    {
        $get = fn (string $key) => Setting::where('group', 'report_settings')
            ->where('key', $key)
            ->whereNull('deleted_at')
            ->latest('id')
            ->value('value');

        return response()->json([
            'name' => $get('name'),
            'logo' => $get('logo'),
        ]);
    }

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
            'group'      => ['required', 'string', 'in:section,sub_section,severity,remark_template,resolution_template,service_repair_note,task_status,report_settings,smtp_settings,map_settings,service_category'],
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

    public function setDefault(Setting $setting): JsonResponse
    {
        // Clear default on all in same group
        Setting::where('group', $setting->group)->each(function ($s) {
            $meta = $s->meta ?? [];
            $meta['is_default'] = false;
            $s->update(['meta' => $meta]);
        });
        // Set this one as default
        $meta = $setting->meta ?? [];
        $meta['is_default'] = true;
        $setting->update(['meta' => $meta]);

        return response()->json($setting->fresh());
    }

    // GET  /settings/jsa-template
    public function getJsaTemplate(): JsonResponse
    {
        $setting = Setting::where('group', 'jsa_template')->where('key', 'default')->first();
        return response()->json($setting ? ($setting->meta['questions'] ?? []) : []);
    }

    // POST /settings/jsa-template
    public function saveJsaTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'questions'              => ['present', 'array'],
            'questions.*.question'   => ['required', 'string', 'max:500'],
            'questions.*.type'       => ['required', 'in:yes_no,always_sometimes_never,text,checkbox'],
            'questions.*.required'   => ['boolean'],
        ]);

        Setting::updateOrCreate(
            ['group' => 'jsa_template', 'key' => 'default'],
            ['value' => 'JSA Default Template', 'meta' => ['questions' => $data['questions']]]
        );

        return response()->json(['message' => 'JSA template saved']);
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
