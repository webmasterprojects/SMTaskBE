<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with(['client', 'property', 'task'])
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->task_id, fn ($q) => $q->where('task_id', $request->task_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? $request->limit ?? 20);

        return response()->json(InvoiceResource::collection($invoices)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'            => ['nullable', 'integer', 'exists:clients,id'],
            'property_id'          => ['nullable', 'integer', 'exists:properties,id'],
            'task_id'              => ['nullable', 'integer', 'exists:tasks,id'],
            'service_quote_id'     => ['nullable', 'integer', 'exists:service_quotes,id'],
            'reference'            => ['nullable', 'string', 'max:255'],
            'description_of_works' => ['nullable', 'string'],
            'date_issued'          => ['nullable', 'date'],
            'date_due'             => ['nullable', 'date'],
            'status'               => ['nullable', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'line_items'           => ['nullable', 'array'],
            'line_items.*.description' => ['required', 'string'],
            'line_items.*.quantity'    => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'line_items.*.gst_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Auto-assign invoice number
        $prefix = Setting::where('group', 'invoice_settings')->where('key', 'invoice_prefix')->value('value') ?? 'INV';
        $lastId = Invoice::max('id') ?? 0;
        $data['invoice_number'] = $prefix . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        $data['created_by'] = auth()->id();

        $invoice = Invoice::create($data);

        return response()->json(InvoiceResource::make($invoice->load(['client', 'property', 'task'])), 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(InvoiceResource::make(
            $invoice->load(['client', 'property', 'task'])
        ));
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'reference'            => ['nullable', 'string', 'max:255'],
            'description_of_works' => ['nullable', 'string'],
            'date_issued'          => ['nullable', 'date'],
            'date_due'             => ['nullable', 'date'],
            'status'               => ['nullable', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'line_items'           => ['nullable', 'array'],
            'line_items.*.description' => ['required', 'string'],
            'line_items.*.quantity'    => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'line_items.*.gst_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoice->update($data);

        return response()->json(InvoiceResource::make($invoice->fresh(['client', 'property', 'task'])));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(null, 204);
    }

    public function sendEmail(Request $request, Invoice $invoice): JsonResponse
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

        $companyName = Setting::where('group', 'report_settings')->where('key', 'name')->value('value') ?? '';
        $userMessage = $data['message'] ?? '';
        $pdfFile     = $request->file('pdf');
        $fromAddress = $smtp['from_email'] ?? $smtp['username'];
        $fromName    = $smtp['from_name'] ?? $companyName;

        // Build Symfony transport directly so we can disable SSL peer verification
        $transport = new EsmtpTransport(
            $smtp['host'],
            (int)($smtp['port'] ?? 587),
            strtolower($smtp['encryption'] ?? 'tls') === 'ssl'
        );
        $transport->setUsername($smtp['username']);
        $transport->setPassword($smtp['password']);
        // Only disable SSL verification in local development (avoids MITM risk in production)
        if (app()->environment('local')) {
            $transport->getStream()->setStreamOptions([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true],
            ]);
        }

        $email = (new SymfonyEmail())
            ->from(new \Symfony\Component\Mime\Address($fromAddress, $fromName))
            ->to($data['to'])
            ->subject($data['subject'])
            ->html("<p>" . nl2br(htmlspecialchars($userMessage)) . "</p><p style='color:#666;font-size:12px'>Generated by {$companyName}</p>");

        if ($pdfFile) {
            $pdfBytes = file_get_contents($pdfFile->getRealPath());
            $filename = 'Invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';
            $email->attach($pdfBytes, $filename, 'application/pdf');
        }

        $mailer = new SymfonyMailer($transport);
        $mailer->send($email);

        return response()->json(['message' => 'Email sent successfully']);
    }
}
