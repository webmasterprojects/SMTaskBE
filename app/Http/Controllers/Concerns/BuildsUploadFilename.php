<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Setting;
use Illuminate\Support\Str;

trait BuildsUploadFilename
{
    protected function buildUploadFilename(string $ext, ?int $propertyId = null, ?int $taskId = null): string
    {
        $company = Setting::where('group', 'report_settings')->where('key', 'name')->value('value');
        $company = $company ? Str::slug($company) : 'upload';

        $parts = [$company];
        if ($propertyId) $parts[] = 'P' . $propertyId;
        if ($taskId)     $parts[] = 'T' . $taskId;
        $parts[] = now()->format('ymd');
        $parts[] = Str::uuid();

        return implode('-', $parts) . '.' . $ext;
    }
}
