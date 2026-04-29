<div style="padding: 8px 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af;">
    <a href="{{ route('portal.terms') }}" target="_blank" style="color: #9ca3af; text-decoration: none; display: block; padding: 2px 0;">Terminos y Condiciones</a>
    <a href="{{ route('portal.privacy') }}" target="_blank" style="color: #9ca3af; text-decoration: none; display: block; padding: 2px 0;">Politica de Privacidad</a>
    @if (auth()->user()?->firm?->logo_path)
        <div style="display: flex; align-items: center; gap: 4px; padding-top: 8px; margin-top: 6px; border-top: 1px solid #f3f4f6; color: #cbd5e1; font-size: 10px;">
            <span>Powered by</span>
            <img src="{{ asset('images/logo-icon.svg') }}" alt="LegalWeb" style="height: 12px; width: auto; opacity: .6;">
            <span style="font-weight: 600; color: #94a3b8;">LegalWeb</span>
        </div>
    @endif
</div>
