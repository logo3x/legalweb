<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\LegalAcceptance;
use App\Models\LegalCase;
use App\Models\PortalAccessLog;
use App\Notifications\ClientDocumentReadyNotification;
use App\Notifications\PortalAccessNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function show(string $token): View
    {
        // Portal es publico: bypass FirmScope u otros scopes que filtran por
        // firma/usuario. Sin esto, si una sesion previa autenticada queda en
        // el contexto de Livewire/Filament, el scope no se aplica de forma
        // homogenea entre navegadores. withoutGlobalScopes() lo evita.
        $case = LegalCase::withoutGlobalScopes()
            ->where('portal_token', $token)
            ->with([
                'client' => fn ($q) => $q->withoutGlobalScopes(),
                'caseType',
                'user.firm',
                'caseFlow',
                'events' => fn ($q) => $q->orderByDesc('event_date'),
                'flowProgress.flowStep',
                'flowProgress' => fn ($q) => $q->join('flow_steps', 'flow_steps.id', '=', 'case_flow_progress.flow_step_id')
                    ->orderBy('flow_steps.order')
                    ->select('case_flow_progress.*'),
                'documents' => fn ($q) => $q->orderByRaw("FIELD(status, 'pendiente', 'solicitado', 'en_tramite', 'recibido', 'no_aplica')")
                    ->orderByRaw("FIELD(priority, 'urgente', 'alta', 'media', 'baja')"),
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

        // Registrar acceso
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
        $case = LegalCase::withoutGlobalScopes()
            ->where('portal_token', $token)
            ->where('portal_enabled', true)
            ->with(['client' => fn ($q) => $q->withoutGlobalScopes()])
            ->firstOrFail();

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

        // Notificar al abogado que el cliente accedio al portal
        $case->user->notify(new PortalAccessNotification($case, $request->ip()));

        session()->put("portal_accepted_{$case->id}", true);

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
        $case = LegalCase::withoutGlobalScopes()
            ->where('portal_token', $token)
            ->where('portal_enabled', true)
            ->with([
                'user',
                'client' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->firstOrFail();

        $document = Document::withoutGlobalScopes()
            ->where('id', $documentId)
            ->where('legal_case_id', $case->id)
            ->where('responsible', 'cliente')
            ->firstOrFail();

        $document->update([
            'status' => 'en_tramite',
            'notes' => trim(($document->notes ?? '').' [Cliente confirmo que lo tiene listo el '.now()->format('d/m/Y H:i').']'),
        ]);

        if ($case->user) {
            try {
                $case->user->notify(new ClientDocumentReadyNotification($case, $document, 'ready'));
            } catch (\Throwable $e) {
                \Log::warning('Portal notify documentReady fallo: '.$e->getMessage(), [
                    'case_id' => $case->id,
                    'document_id' => $document->id,
                ]);
            }
        }

        return redirect()->route('portal.show', $token)
            ->with('doc_success', 'Avisamos a su abogado que tiene listo "'.$document->name.'". Pronto se contactara con usted.');
    }

    /**
     * Cliente envia un enlace al documento (Drive, OneDrive, etc).
     */
    public function documentLink(Request $request, string $token, int $documentId)
    {
        $request->validate([
            'external_url' => 'required|url|max:500',
        ], [
            'external_url.required' => 'Debe ingresar un enlace.',
            'external_url.url' => 'El enlace no es valido. Debe comenzar con https://',
            'external_url.max' => 'El enlace es demasiado largo.',
        ]);

        $case = LegalCase::withoutGlobalScopes()
            ->where('portal_token', $token)
            ->where('portal_enabled', true)
            ->with([
                'user',
                'client' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->firstOrFail();

        $document = Document::withoutGlobalScopes()
            ->where('id', $documentId)
            ->where('legal_case_id', $case->id)
            ->where('responsible', 'cliente')
            ->firstOrFail();

        $document->update([
            'external_url' => $request->input('external_url'),
            'status' => 'recibido',
            'received_at' => now(),
            'notes' => trim(($document->notes ?? '').' [Enlace enviado por el cliente desde el portal el '.now()->format('d/m/Y H:i').']'),
        ]);

        if ($case->user) {
            try {
                $case->user->notify(new ClientDocumentReadyNotification($case, $document, 'uploaded'));
            } catch (\Throwable $e) {
                \Log::warning('Portal notify documentLink fallo: '.$e->getMessage(), [
                    'case_id' => $case->id,
                    'document_id' => $document->id,
                ]);
            }
        }

        return redirect()->route('portal.show', $token)
            ->with('doc_success', 'Enlace de "'.$document->name.'" enviado correctamente. Su abogado fue notificado.');
    }
}
