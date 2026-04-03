<div style="margin-top: 24px;">
    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
        <div style="flex-grow: 1; height: 1px; background: #e5e7eb;"></div>
        <span style="margin: 0 12px; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">O continua con</span>
        <div style="flex-grow: 1; height: 1px; background: #e5e7eb;"></div>
    </div>

    <a href="{{ route('auth.google') }}"
       style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; height: 44px; font-size: 14px; font-weight: 500; color: #3c4043; background: #fff; border: 1px solid #dadce0; border-radius: 6px; text-decoration: none; cursor: pointer; font-family: 'Google Sans', Roboto, Arial, sans-serif; transition: background 0.2s, box-shadow 0.2s;"
       onmouseover="this.style.background='#f8f9fa'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)';"
       onmouseout="this.style.background='#fff'; this.style.boxShadow='none';">
        <svg width="18" height="18" viewBox="0 0 24 24" style="flex-shrink: 0;">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continuar con Google
    </a>

    <p style="margin-top: 16px; text-align: center; font-size: 12px; color: #9ca3af;">
        Al continuar, aceptas los <a href="{{ route('portal.terms') }}" target="_blank" style="color: #3b82f6; text-decoration: none;">terminos</a> y la <a href="{{ route('portal.privacy') }}" target="_blank" style="color: #3b82f6; text-decoration: none;">politica de privacidad</a>
    </p>
</div>
