<?php

namespace App\Http\Controllers;

use App\Models\LegalAcceptance;
use App\Models\LegalCase;
use App\Models\PortalAccessLog;
use App\Notifications\PortalAccessNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function show(string $token): View
    {
        $case = LegalCase::where('portal_token', $token)
            ->where('portal_enabled', true)
            ->with([
                'client',
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
        $case = LegalCase::where('portal_token', $token)
            ->where('portal_enabled', true)
            ->with('client')
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
}
