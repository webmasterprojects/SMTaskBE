<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Setting;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class ReportController extends Controller
{
    // ── List reports for a task ────────────────────────────────────────────────
    public function index(Task $task): JsonResponse
    {
        $reports = $task->reports()->with('generatedBy')->latest()->get()->map(fn ($r) => [
            'id'            => $r->id,
            'report_number' => $r->report_number,
            'generated_by'  => $r->generatedBy?->full_name ?? $r->generatedBy?->name,
            'created_at'    => $r->created_at,
        ]);

        return response()->json($reports);
    }

    // ── Get one report (full snapshot) ─────────────────────────────────────────
    public function show(Report $report): JsonResponse
    {
        return response()->json($report);
    }

    // ── Generate & save report snapshot ───────────────────────────────────────
    public function store(Request $request, Task $task): JsonResponse
    {
        $task->load([
            'property.client',
            'routines.asset.assetType',
            'routines.asset.assetTypeVariant',
            'appointments.technicians',
        ]);

        // Work history for all assets on this task
        $workHistory = \App\Models\AssetWorkHistory::where('task_id', $task->id)
            ->with(['asset.assetType', 'asset.assetTypeVariant', 'recordedBy'])
            ->get();

        // Company / report settings
        $company = $this->getSettings('report_settings');

        // Severity settings (for color lookup) — all severities, keyed by value
        $severitySettings = Setting::where('group', 'severity')->orderBy('sort_order')->get();
        $severities = $severitySettings->keyBy('value')->map(fn ($s) => $s->meta ?? []);

        // Task-specific asset results from task_assets table
        $taskAssets = \Illuminate\Support\Facades\DB::table('task_assets')
            ->where('task_id', $task->id)
            ->get()
            ->keyBy('asset_id');

        // ── Scope of works: unique asset types + frequencies ──────────────────
        $scopeMap = [];
        foreach ($task->routines as $routine) {
            $at  = $routine->asset?->assetType;
            $key = $at?->id ?? 'unknown';
            if (!isset($scopeMap[$key])) {
                $scopeMap[$key] = [
                    'category'  => $at?->name ?? 'Unknown',
                    'standard'  => $at?->standard ?? '',
                    'frequency' => [],
                    'count'     => 0,
                ];
            }
            foreach ((array)($routine->frequency ?? []) as $freq) {
                $scopeMap[$key]['frequency'][] = $freq;
            }
            $scopeMap[$key]['count']++;
        }
        foreach ($scopeMap as &$s) {
            $s['frequency'] = array_unique($s['frequency']);
        }

        // ── Servicing summary: category → asset variant → count ───────────────
        $servicingMap = [];
        foreach ($task->routines as $routine) {
            $at  = $routine->asset?->assetType;
            $av  = $routine->asset?->assetTypeVariant;
            $key = ($at?->id ?? 'u') . '_' . ($av?->id ?? 'u');
            if (!isset($servicingMap[$key])) {
                $servicingMap[$key] = [
                    'service'  => $at?->name ?? 'Unknown',
                    'standard' => $at?->standard ?? '',
                    'asset'    => $av?->name ?? ($at?->name ?? 'Unknown'),
                    'quantity' => 0,
                ];
            }
            $servicingMap[$key]['quantity']++;
        }

        // ── Defect summary: all severity levels pre-seeded with count 0 ──────
        // Build case-insensitive lookup: lowercase(value) → meta
        $severityByLower = [];
        foreach ($severitySettings as $s) {
            $severityByLower[strtolower($s->value)] = ['label' => $s->value, 'meta' => $s->meta ?? []];
        }

        $defectSummary = [];
        foreach ($severitySettings as $s) {
            $meta = $s->meta ?? [];
            $defectSummary[$s->value] = [
                'severity' => $s->value,
                'color'    => $meta['warning_color'] ?? '#6c757d',
                'count'    => 0,
            ];
        }
        // Count any work history entry that has a severity set (FAIL or NO_TEST with severity)
        foreach ($workHistory->filter(fn ($wh) => !empty($wh->severity)) as $wh) {
            $rawSev   = $wh->severity;
            $lookup   = $severityByLower[strtolower($rawSev)] ?? null;
            $sev      = $lookup ? $lookup['label'] : $rawSev;
            $meta     = $lookup ? $lookup['meta'] : [];
            if (!isset($defectSummary[$sev])) {
                $defectSummary[$sev] = [
                    'severity' => $sev,
                    'color'    => $meta['warning_color'] ?? '#6c757d',
                    'count'    => 0,
                ];
            }
            $defectSummary[$sev]['count']++;
        }

        // ── Maintenance: group assets by category ─────────────────────────────
        $maintenanceMap = [];
        foreach ($task->routines as $routine) {
            $asset = $routine->asset;
            $at    = $asset?->assetType;
            $catId = $at?->id ?? 'unknown';

            if (!isset($maintenanceMap[$catId])) {
                $maintenanceMap[$catId] = [
                    'category' => $at?->name ?? 'Unknown',
                    'standard' => $at?->standard ?? '',
                    'assets'   => [],
                ];
            }

            $assetWh     = $workHistory->where('asset_id', $asset?->id);
            $taskAssetRow = $taskAssets->get($asset?->id);
            $rawStatus    = $taskAssetRow?->status ?? null;
            $assetStatus  = $rawStatus ?? 'N/A';

            // Include FAIL entries and any entry with a severity set (e.g. NO_TEST + severity)
            $assetFailWh = $assetWh->filter(fn ($wh) => $wh->status === 'FAIL' || !empty($wh->severity));

            $defects = $assetFailWh->map(function ($wh) use ($severityByLower, $severities) {
                $rawSev = $wh->severity ?? '';
                $lookup = $severityByLower[strtolower($rawSev)] ?? null;
                $sev    = $lookup ? $lookup['label'] : ($rawSev ?: 'Unknown');
                $meta   = $lookup ? $lookup['meta'] : ($severities[$rawSev] ?? []);
                return [
                    'status'         => $wh->status,
                    'severity'       => $sev,
                    'severity_color' => $meta['warning_color'] ?? '#6c757d',
                    'severity_text'  => $meta['text_color'] ?? '#fff',
                    'remarks'        => $wh->remarks,
                    'resolution'     => $wh->resolution,
                    'images'         => $wh->images ?? [],
                    'recorded_at'    => $wh->recorded_at?->toDateTimeString(),
                    'recorded_by'    => $wh->recordedBy?->full_name ?? $wh->recordedBy?->name,
                ];
            })->values()->toArray();

            // Per-asset severity breakdown — all levels with count
            $assetSevSummary = [];
            foreach ($severitySettings as $s) {
                $meta = $s->meta ?? [];
                $assetSevSummary[$s->value] = [
                    'severity' => $s->value,
                    'color'    => $meta['warning_color'] ?? '#6c757d',
                    'count'    => 0,
                ];
            }
            foreach ($assetFailWh as $wh) {
                $rawSev = $wh->severity ?? '';
                $lookup = $severityByLower[strtolower($rawSev)] ?? null;
                $sev    = $lookup ? $lookup['label'] : ($rawSev ?: 'Unknown');
                if (!isset($assetSevSummary[$sev])) {
                    $meta = $lookup ? $lookup['meta'] : [];
                    $assetSevSummary[$sev] = ['severity' => $sev, 'color' => $meta['warning_color'] ?? '#6c757d', 'count' => 0];
                }
                $assetSevSummary[$sev]['count']++;
            }

            $maintenanceMap[$catId]['assets'][] = [
                'id'               => $asset?->id,
                'label'            => $asset?->label ?? '',
                'location'         => $asset?->location ?? '',
                'status'           => $assetStatus,
                'severity_summary' => array_values($assetSevSummary),
                'defects'          => $defects,
            ];
        }

        // ── Performed date from last appointment ───────────────────────────────
        $lastAppt       = $task->appointments->sortBy('start_date_time')->last();
        $performedDate  = $lastAppt?->start_date_time;
        $technicians    = $lastAppt?->technicians->map(fn ($t) => $t->full_name ?? $t->name)->implode(', ');

        // ── Report number ──────────────────────────────────────────────────────
        $lastId = Report::max('id') ?? 0;
        $reportNumber = 'R-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);

        // ── Build snapshot ─────────────────────────────────────────────────────
        $snapshot = [
            'report_number'    => $reportNumber,
            'generated_at'     => now()->toDateTimeString(),
            'generated_by'     => auth()->user()?->full_name ?? auth()->user()?->name,
            'company'          => $company,
            'property'         => [
                'name'         => $task->property?->name,
                'address'      => $task->property?->address['formattedAddress'] ?? '',
                'client_name'  => $task->property?->client?->name,
                'strata_plan'  => $task->property?->strata_plan ?? '',
            ],
            'task'             => [
                'id'    => $task->id,
                'label' => $task->label,
            ],
            'issued_by'        => auth()->user()?->full_name ?? auth()->user()?->name,
            'issued_date'      => now()->format('jS F Y'),
            'performed_date'   => $performedDate ? \Carbon\Carbon::parse($performedDate)->format('jS F Y') : '',
            'technicians'      => $technicians,
            'scope_of_works'   => array_values($scopeMap),
            'defect_summary'   => array_values($defectSummary),
            'servicing_summary'=> array_values($servicingMap),
            'maintenance'      => array_values($maintenanceMap),
        ];

        $report = Report::create([
            'task_id'       => $task->id,
            'report_number' => $reportNumber,
            'data'          => $snapshot,
            'generated_by'  => auth()->id(),
        ]);

        return response()->json(['id' => $report->id, 'report_number' => $reportNumber, 'data' => $snapshot], 201);
    }

    // ── Send report via email ──────────────────────────────────────────────────
    public function sendEmail(Request $request, Report $report): JsonResponse
    {
        $data = $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string'],
            'message' => ['nullable', 'string'],
        ]);

        $smtp = $this->getSettings('smtp_settings');

        if (empty($smtp['host'])) {
            return response()->json(['message' => 'SMTP not configured. Please set up SMTP in Settings.'], 422);
        }

        // Apply SMTP config dynamically
        Config::set('mail.mailers.smtp', [
            'transport'  => 'smtp',
            'host'       => $smtp['host'],
            'port'       => $smtp['port'] ?? 587,
            'encryption' => $smtp['encryption'] ?? 'tls',
            'username'   => $smtp['username'],
            'password'   => $smtp['password'],
        ]);
        Config::set('mail.from', [
            'address' => $smtp['from_email'] ?? $smtp['username'],
            'name'    => $smtp['from_name'] ?? 'Service Matrix',
        ]);

        $reportData = $report->data;
        $userMessage = $data['message'] ?? '';

        $attachmentHtml = $this->buildReportAttachmentHtml($reportData);
        $filename = 'Report-' . ($reportData['report_number'] ?? $report->id) . '.html';

        Mail::mailer('smtp')->send([], [], function ($mail) use ($data, $reportData, $userMessage, $attachmentHtml, $filename) {
            $mail->to($data['to'])
                ->subject($data['subject'])
                ->html($this->buildEmailHtml($reportData, $userMessage))
                ->attachData($attachmentHtml, $filename, ['mime' => 'text/html']);
        });

        return response()->json(['message' => 'Email sent successfully']);
    }

    // ── Test SMTP connection ───────────────────────────────────────────────────
    public function testSmtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
        ]);

        $smtp = $this->getSettings('smtp_settings');

        if (empty($smtp['host'])) {
            return response()->json(['message' => 'SMTP not configured.'], 422);
        }

        Config::set('mail.mailers.smtp', [
            'transport'  => 'smtp',
            'host'       => $smtp['host'],
            'port'       => $smtp['port'] ?? 587,
            'encryption' => $smtp['encryption'] ?? 'tls',
            'username'   => $smtp['username'],
            'password'   => $smtp['password'],
        ]);
        Config::set('mail.from', [
            'address' => $smtp['from_email'] ?? $smtp['username'],
            'name'    => $smtp['from_name'] ?? 'Service Matrix',
        ]);

        try {
            Mail::mailer('smtp')->raw('This is a test email from Service Matrix.', function ($mail) use ($data) {
                $mail->to($data['to'])->subject('SMTP Test - Service Matrix');
            });
            return response()->json(['message' => 'Test email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 422);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    private function getSettings(string $group): array
    {
        return Setting::where('group', $group)->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    private function buildReportAttachmentHtml(array $r): string
    {
        $rn       = htmlspecialchars($r['report_number'] ?? '');
        $company  = htmlspecialchars($r['company']['name'] ?? 'Service Matrix');
        $logo     = htmlspecialchars($r['company']['logo'] ?? '');
        $prop     = htmlspecialchars($r['property']['name'] ?? '');
        $address  = htmlspecialchars($r['property']['address'] ?? '');
        $client   = htmlspecialchars($r['property']['client_name'] ?? '');
        $strata   = htmlspecialchars($r['property']['strata_plan'] ?? '');
        $issued   = htmlspecialchars($r['issued_date'] ?? '');
        $issuedBy = htmlspecialchars($r['issued_by'] ?? '');
        $performed = htmlspecialchars($r['performed_date'] ?? '');
        $techs    = htmlspecialchars($r['technicians'] ?? '');

        $style = '
            body{font-family:Arial,sans-serif;font-size:13px;color:#222;margin:0;padding:0}
            .header{background:#1e3a5f;color:#fff;padding:20px 30px;display:flex;align-items:center;gap:20px}
            .header img{max-height:60px;max-width:160px;object-fit:contain}
            .header h1{margin:0;font-size:20px}
            .header p{margin:2px 0;font-size:12px;opacity:.85}
            .section{padding:20px 30px;border-bottom:1px solid #e5e7eb}
            .section h2{font-size:14px;color:#1e3a5f;text-transform:uppercase;letter-spacing:.5px;margin:0 0 12px}
            table{width:100%;border-collapse:collapse;font-size:12px}
            th{background:#f1f5f9;text-align:left;padding:7px 10px;font-size:11px;color:#475569;border:1px solid #e2e8f0}
            td{padding:7px 10px;border:1px solid #e2e8f0;vertical-align:top}
            .badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
            .pass{background:#dcfce7;color:#166534}
            .fail{background:#fee2e2;color:#991b1b}
            .no-test{background:#fef9c3;color:#854d0e}
            .meta{display:flex;flex-wrap:wrap;gap:16px;font-size:12px;color:#374151}
            .meta span{display:flex;flex-direction:column}
            .meta strong{font-size:11px;color:#6b7280;font-weight:600;margin-bottom:2px}
            .footer{background:#f8f9fa;padding:14px 30px;font-size:11px;color:#6c757d;text-align:center}
            @media print{body{margin:0}@page{margin:15mm}}
        ';

        $logoTag = $logo ? "<img src='{$logo}' alt='logo'>" : '';

        // Info section
        $infoHtml = "<div class='meta'>
            <span><strong>Property</strong>{$prop}</span>
            <span><strong>Address</strong>{$address}</span>
            <span><strong>Client</strong>{$client}</span>"
            . ($strata ? "<span><strong>Strata Plan</strong>{$strata}</span>" : '') . "
            <span><strong>Report No.</strong>{$rn}</span>
            <span><strong>Issued Date</strong>{$issued}</span>
            <span><strong>Issued By</strong>{$issuedBy}</span>
            <span><strong>Performed Date</strong>{$performed}</span>
            <span><strong>Technicians</strong>{$techs}</span>
        </div>";

        // Scope of works
        $scopeRows = '';
        foreach (($r['scope_of_works'] ?? []) as $s) {
            $freq = implode(', ', (array)($s['frequency'] ?? []));
            $scopeRows .= "<tr><td>" . htmlspecialchars($s['category'] ?? '') . "</td>"
                . "<td>" . htmlspecialchars($s['standard'] ?? '') . "</td>"
                . "<td>" . htmlspecialchars($freq) . "</td>"
                . "<td>" . htmlspecialchars((string)($s['count'] ?? '')) . "</td></tr>";
        }
        $scopeTable = $scopeRows ? "<table><thead><tr><th>Category</th><th>Standard</th><th>Frequency</th><th>Qty</th></tr></thead><tbody>{$scopeRows}</tbody></table>" : '<p style="color:#6b7280">No scope recorded.</p>';

        // Defect summary
        $defectRows = '';
        foreach (($r['defect_summary'] ?? []) as $d) {
            $color = htmlspecialchars($d['color'] ?? '#6c757d');
            $defectRows .= "<tr><td><span class='badge' style='background:{$color};color:#fff'>"
                . htmlspecialchars($d['severity'] ?? '') . "</span></td>"
                . "<td>" . htmlspecialchars((string)($d['count'] ?? 0)) . "</td></tr>";
        }
        $defectTable = $defectRows ? "<table><thead><tr><th>Severity</th><th>Count</th></tr></thead><tbody>{$defectRows}</tbody></table>" : '<p style="color:#6b7280">No defects recorded.</p>';

        // Maintenance
        $maintHtml = '';
        foreach (($r['maintenance'] ?? []) as $cat) {
            $catName = htmlspecialchars($cat['category'] ?? 'Unknown');
            $maintHtml .= "<h3 style='font-size:13px;color:#1e3a5f;margin:16px 0 8px'>{$catName}</h3><table><thead><tr><th>Asset</th><th>Location</th><th>Status</th><th>Defects</th></tr></thead><tbody>";
            foreach (($cat['assets'] ?? []) as $asset) {
                $status = strtolower($asset['status'] ?? 'n/a');
                $badgeClass = $status === 'pass' ? 'pass' : ($status === 'fail' ? 'fail' : 'no-test');
                $defects = '';
                foreach (($asset['defects'] ?? []) as $def) {
                    $sev = htmlspecialchars($def['severity'] ?? '');
                    $rem = htmlspecialchars($def['remarks'] ?? '');
                    $res = $def['resolution'] ? ' <em>→ ' . htmlspecialchars($def['resolution']) . '</em>' : '';
                    $sc  = htmlspecialchars($def['severity_color'] ?? '#6c757d');
                    $st  = htmlspecialchars($def['severity_text'] ?? '#fff');
                    $defects .= "<div style='margin-bottom:4px'><span class='badge' style='background:{$sc};color:{$st}'>{$sev}</span> {$rem}{$res}</div>";
                }
                $maintHtml .= "<tr>"
                    . "<td>" . htmlspecialchars($asset['label'] ?? '') . "</td>"
                    . "<td>" . htmlspecialchars($asset['location'] ?? '') . "</td>"
                    . "<td><span class='badge {$badgeClass}'>" . strtoupper($asset['status'] ?? 'N/A') . "</span></td>"
                    . "<td>" . ($defects ?: '<span style="color:#6b7280">None</span>') . "</td>"
                    . "</tr>";
            }
            $maintHtml .= "</tbody></table>";
        }

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Report {$rn}</title><style>{$style}</style></head><body>
            <div class='header'>{$logoTag}<div><h1>{$company}</h1><p>Service Report — {$rn}</p></div></div>
            <div class='section'>{$infoHtml}</div>
            <div class='section'><h2>Scope of Works</h2>{$scopeTable}</div>
            <div class='section'><h2>Defect Summary</h2>{$defectTable}</div>
            <div class='section'><h2>Maintenance Details</h2>{$maintHtml}</div>
            <div class='footer'>Generated by {$company} &mdash; {$rn} &mdash; {$issued}</div>
        </body></html>";
    }

    private function buildEmailHtml(array $report, string $userMessage): string
    {
        $rn      = $report['report_number'] ?? '';
        $prop    = $report['property']['name'] ?? '';
        $issued  = $report['issued_date'] ?? '';
        $company = $report['company']['name'] ?? 'Service Matrix';

        return "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
            <div style='background:#1e3a5f;color:#fff;padding:20px'>
                <h2 style='margin:0'>{$company}</h2>
            </div>
            <div style='padding:24px'>
                " . ($userMessage ? "<p>" . nl2br(htmlspecialchars($userMessage)) . "</p><hr>" : "") . "
                <p>Please find attached the service report <strong>{$rn}</strong> for <strong>{$prop}</strong>, issued on {$issued}.</p>
                <p>This report contains the inspection results and any defects found during the service.</p>
                <br>
                <p>Regards,<br><strong>{$company}</strong></p>
            </div>
            <div style='background:#f8f9fa;padding:12px;font-size:12px;color:#6c757d;text-align:center'>
                Generated by Service Matrix
            </div>
        </div>";
    }
}
