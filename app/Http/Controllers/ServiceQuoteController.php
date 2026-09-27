<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceQuoteResource;
use App\Models\ServiceQuote;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ServiceQuoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $quotes = ServiceQuote::query()
            ->with(['property', 'client', 'quoteAssets'])
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->task_id, fn ($q) => $q->where('task_id', $request->task_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->is_defect_quote !== null, fn ($q) => $q->where('is_defect_quote', $request->boolean('is_defect_quote')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json(ServiceQuoteResource::collection($quotes)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id'      => ['nullable', 'integer', 'exists:properties,id'],
            'client_id'        => ['nullable', 'integer', 'exists:clients,id'],
            'task_id'          => ['nullable', 'integer', 'exists:tasks,id'],
            'billing_id'       => ['nullable', 'integer', 'exists:billings,id'],
            'is_defect_quote'  => ['nullable', 'boolean'],
            'description'      => ['nullable', 'string'],
            'review_date'      => ['nullable', 'date'],
            'expiry_date'      => ['nullable', 'date'],
            'supervisor_id'    => ['nullable', 'integer', 'exists:users,id'],
            'sales_person_id'  => ['nullable', 'integer', 'exists:users,id'],
            'tags'             => ['nullable', 'array'],
            'scope_of_work'    => ['nullable', 'string'],
            'terms_and_condition' => ['nullable', 'string'],
            'internal_note'    => ['nullable', 'string'],
            'status'           => ['nullable', Rule::in(['draft', 'sent', 'approved', 'declined', 'cancelled'])],
        ]);

        $quote = ServiceQuote::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return response()->json(ServiceQuoteResource::make($quote->load('quoteAssets')), 201);
    }

    public function show(ServiceQuote $serviceQuote): JsonResponse
    {
        return response()->json(ServiceQuoteResource::make(
            $serviceQuote->load(['property', 'client', 'quoteAssets'])
        ));
    }

    public function update(Request $request, ServiceQuote $serviceQuote): JsonResponse
    {
        $serviceQuote->update($request->only([
            'description', 'review_date', 'expiry_date', 'tags',
            'scope_of_work', 'terms_and_condition', 'internal_note', 'status',
            'total_cost_price', 'total_markup', 'sub_total', 'total_sales_price',
            'total_gst', 'total_amount', 'total_profit', 'total_quantity',
        ]));

        return response()->json(ServiceQuoteResource::make($serviceQuote->fresh('quoteAssets')));
    }

    public function destroy(ServiceQuote $serviceQuote): JsonResponse
    {
        $serviceQuote->delete();

        return response()->json(null, 204);
    }

    public function addAsset(Request $request, ServiceQuote $serviceQuote): JsonResponse
    {
        $data = $request->validate([
            'asset_id'    => ['nullable', 'integer', 'exists:assets,id'],
            'label'       => ['required', 'string'],
            'variant'     => ['nullable', 'string'],
            'type'        => ['nullable', 'string'],
            'line_type'   => ['nullable', 'string'],   // "product" or "repair"
            'serial'      => ['nullable', 'string'],
            'bar_code'    => ['nullable', 'string'],
            'make'        => ['nullable', 'string'],
            'size'        => ['nullable', 'string'],
            'model'       => ['nullable', 'string'],
            'quantity'    => ['nullable', 'integer', 'min:1'],
            'cost_price'  => ['nullable', 'numeric', 'min:0'],
            'markup'      => ['nullable', 'numeric', 'min:0'],
            'sales_price' => ['nullable', 'numeric', 'min:0'],
            'gst'         => ['nullable', 'numeric', 'min:0'],
            'remarks'     => ['nullable', 'string'],
        ]);

        $asset = $serviceQuote->quoteAssets()->create($data);

        return response()->json($asset->fresh(), 201);
    }

    public function updateAssetStatus(Request $request, ServiceQuote $serviceQuote, int $assetId): JsonResponse
    {
        $data = $request->validate([
            'status'  => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $quoteAsset = $serviceQuote->quoteAssets()->findOrFail($assetId);
        $quoteAsset->update($data);

        return response()->json($quoteAsset->fresh());
    }

    public function sendEmail(Request $request, ServiceQuote $serviceQuote): JsonResponse
    {
        $data = $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string'],
            'message' => ['nullable', 'string'],
            'pdf'     => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $smtp = Setting::where('group', 'smtp_settings')->get()->pluck('value', 'key')->toArray();

        if (empty($smtp['host'])) {
            return response()->json(['message' => 'SMTP not configured. Please set up SMTP in Settings.'], 422);
        }

        Config::set('mail.mailers.smtp', [
            'transport'  => 'smtp',
            'host'       => $smtp['host'],
            'port'       => $smtp['port'] ?? 587,
            'encryption' => $smtp['encryption'] ?? 'tls',
            'username'   => $smtp['username'],
            'password'   => $smtp['password'],
        ]);

        $companyName = Setting::where('group', 'report_settings')->where('key', 'name')->value('value') ?? '';
        Config::set('mail.from', [
            'address' => $smtp['from_email'] ?? $smtp['username'],
            'name'    => $smtp['from_name'] ?? $companyName,
        ]);

        $userMessage = $data['message'] ?? '';
        $pdfFile     = $request->file('pdf');

        if ($pdfFile) {
            $pdfBytes = file_get_contents($pdfFile->getRealPath());
            $filename = 'ServiceQuote-' . $serviceQuote->id . '.pdf';
            Mail::mailer('smtp')->send([], [], function ($mail) use ($data, $userMessage, $pdfBytes, $filename, $companyName) {
                $mail->to($data['to'])
                    ->subject($data['subject'])
                    ->html("<p>" . nl2br(htmlspecialchars($userMessage)) . "</p><p style='color:#666;font-size:12px'>Generated by {$companyName}</p>")
                    ->attachData($pdfBytes, $filename, ['mime' => 'application/pdf']);
            });
        } else {
            Mail::mailer('smtp')->send([], [], function ($mail) use ($data, $userMessage, $companyName) {
                $mail->to($data['to'])
                    ->subject($data['subject'])
                    ->html("<p>" . nl2br(htmlspecialchars($userMessage)) . "</p><p style='color:#666;font-size:12px'>Generated by {$companyName}</p>");
            });
        }

        return response()->json(['message' => 'Email sent successfully']);
    }
}
