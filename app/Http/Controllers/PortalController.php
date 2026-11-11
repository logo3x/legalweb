<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\LegalAcceptance;
use App\Models\LegalCase;
use App\Models\PortalAccessLog;
use App\Models\Scopes\FirmScope;
use App\Notifications\ClientDocumentReadyNotification;
use App\Notifications\PortalAccessNotification;
use App\Services\N8nWebhook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function show(string $token): View
    {
        $case = $this->caseQuery($token)
            ->with([
                'caseType',
                'user.firm',
                'caseFlow',
                'events' => fn ($q) => $q->orderByDesc('event_date'),
                'flowProgress.flowStep',
                'flowProgress' => fn ($q) => $q->join('flow_steps', 'flow_steps.id', '=', 'case_flow_progress.flow_step_id')
                    ->orderBy('flow_steps.order')
                    ->select('case_flow_progress.*'),
                'documents' => fn ($q) => $q->orderByRaw("CASE status WHEN 'pendiente' THEN 1 WHEN 'solicitado' THEN 2 WHEN 'en_tramite' THEN 3 WHEN 'recibido' THEN 4 WHEN 'no_aplica' THEN 5 ELSE 0 END")
                    ->orderByRaw("CASE priority WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 WHEN 'baja' THEN 4 ELSE 0 END"),
            ])
            ->firstOrFail();

        if (! $case->portal_enabled) {
            return view('portal.disabled', [
                'firmName' => $case->user?->firm?->name,
                'firmLogo' => $case->user?->firm?->logo_path ? asset('storage/'.$case->user->firm->logo_path) : null,
            ]);
        }

        $hasAccepted = session()->has("portal_accepted_{$case->id}");
        $portalToken = $token;
        $firm = $case->user->firm;
        $firmLogo = $firm?->logo_path ? asset('storage/'.$firm->logo_path) : null;

        PortalAccessLog::create([
            'legal_case_id' => $case->id,
            'firm_id' => $case->user->firm_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => $hasAccepted ? 'view' : 'landing',
        ]);

        $firmName = $firm?->name;

        return view('portal.show', compact('case', 'hasAccepted', 'portalToken', 'firmLogo', 'firmName'));
    }

    public function accept(Request $request, string $token)
    {
        $case = $this->caseQuery($token)->where('portal_enabled', true)->firstOrFail();

        // Solo se avisa al abogado una vez al dia por caso: repetir el formulario
        // no debe generar correos, WhatsApp de pago ni eventos en cadena.
        $alreadyAcceptedToday = LegalAcceptance::where('legal_case_id', $case->id)
            ->where('document_type', 'terminos_portal')
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        LegalAcceptance::create([
            'acceptor_type' => 'cliente',
            'acceptor_name' => $case->client->full_name,
            'acceptor_email' => $case->client->email,
            'document_type' => 'terminos_portal',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'legal_case_id' => $case->id,
        ]);

        PortalAccessLog::create([
            'legal_case_id' => $case->id,
            'firm_id' => $case->user->firm_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'action' => 'accept_terms',
        ]);

        session()->put("portal_accepted_{$case->id}", true);

        if (! $alreadyAcceptedToday) {
            $case->user->notify(new PortalAccessNotification($case, $request->ip()));

            app(N8nWebhook::class)->dispatch('portal.accepted', [
                'firm' => N8nWebhook::firmData($case->user?->firm),
                'case' => N8nWebhook::caseData($case),
            ], $case->firm_id);
        }

        return redirect()->route('portal.show', $token);
    }

    public function terms(Request $request): View
    {
        $portalToken = $request->query('ref');

        return view('portal.terms', compact('portalToken'));
    }

    public function privacy(Request $request): View
    {
        $portalToken = $request->query('ref');

        return view('portal.privacy', compact('portalToken'));
    }

    /**
     * Cliente marca documento como "Ya lo tengo listo".
     */
    public function documentReady(Request $request, string $token, int $documentId)
    {
        $case = $this->caseQuery($token)->where('portal_enabled', true)->with('user')->firstOrFail();
        $document = $this->clientDocument($case, $documentId);

        // Si ya aviso o ya lo envio, no se repite el aviso al abogado.
        if (in_array($document->status, ['en_tramite', 'recibido'], true)) {
            return redirect()->route('portal.show', $token)
                ->with('doc_success', 'Su abogado ya fue avisado sobre "'.$document->name.'".');
        }

        $document->update([
            'status' => 'en_tramite',
            'notes' => trim(($document->notes ?? '').' [Cliente confirmo que lo tiene listo el '.now()->format('d/m/Y H:i').']'),
        ]);

        $this->notifyLawyer($case, $document, 'ready');

        return redirect()->route('portal.show', $token)
            ->with('doc_success', 'Avisamos a su abogado que tiene listo "'.$document->name.'". Pronto se contactara con usted.');
    }

    /**
     * Cliente envia un enlace al documento (Drive, OneDrive, etc).
     */
    public function documentLink(Request $request, string $token, int $documentId)
    {
        $request->validate([
            'external_url' => 'required|url:http,https|max:500',
        ], [
            'external_url.required' => 'Debe ingresar un enlace.',
            'external_url.url' => 'El enlace no es valido. Debe comenzar con https://',
            'external_url.max' => 'El enlace es demasiado largo.',
        ]);

        $case = $this->caseQuery($token)->where('portal_enabled', true)->with('user')->firstOrFail();
        $document = $this->clientDocument($case, $documentId);

        $url = (string) $request->input('external_url');

        // Reenviar el mismo enlace no genera un aviso nuevo.
        if ($document->status === 'recibido' && $document->external_url === $url) {
            return redirect()->route('portal.show', $token)
                ->with('doc_success', 'Su abogado ya tiene el enlace de "'.$document->name.'".');
        }

        $document->update([
            'external_url' => $url,
            'status' => 'recibido',
            'received_at' => now(),
            'notes' => trim(($document->notes ?? '').' [Enlace enviado por el cliente desde el portal el '.now()->format('d/m/Y H:i').']'),
        ]);

        $this->notifyLawyer($case, $document, 'uploaded');

        return redirect()->route('portal.show', $token)
            ->with('doc_success', 'Enlace de "'.$document->name.'" enviado correctamente. Su abogado fue notificado.');
    }

    /**
     * El portal es publico: se quita solo FirmScope (que depende de la sesion del
     * panel) y se conservan los demas filtros, como la papelera. Un caso o un
     * cliente eliminado deja de estar disponible en el portal.
     *
     * @return Builder<LegalCase>
     */
    private function caseQuery(string $token): Builder
    {
        return LegalCase::withoutGlobalScope(FirmScope::class)
            ->where('portal_token', $token)
            ->whereHas('client', fn ($q) => $q->withoutGlobalScope(FirmScope::class))
            ->with(['client' => fn ($q) => $q->withoutGlobalScope(FirmScope::class)]);
    }

    private function clientDocument(LegalCase $case, int $documentId): Document
    {
        return Document::where('id', $documentId)
            ->where('legal_case_id', $case->id)
            ->where('responsible', 'cliente')
            ->firstOrFail();
    }

    private function notifyLawyer(LegalCase $case, Document $document, string $action): void
    {
        if ($case->user) {
            try {
                $case->user->notify(new ClientDocumentReadyNotification($case, $document, $action));
            } catch (\Throwable $e) {
                Log::warning('Portal: no se pudo notificar el documento: '.$e->getMessage(), [
                    'case_id' => $case->id,
                    'document_id' => $document->id,
                ]);
            }
        }

        app(N8nWebhook::class)->dispatch('document.client_ready', [
            'firm' => N8nWebhook::firmData($case->user?->firm),
            'case' => N8nWebhook::caseData($case),
            'document' => ['id' => $document->id, 'name' => $document->name, 'action' => $action === 'uploaded' ? 'link' : 'ready'],
        ], $case->firm_id);
    }
}
