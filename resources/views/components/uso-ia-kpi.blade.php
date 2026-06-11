@props(['label', 'value', 'color' => '#3A86FF'])

<div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 18px;">
    <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; margin-bottom: 6px;">{{ $label }}</div>
    <div style="font-size: 24px; font-weight: 800; color: {{ $color }}; font-family: 'Poppins',sans-serif;">{{ $value }}</div>
</div>
