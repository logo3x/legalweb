<x-filament-panels::page>
    <div style="max-width: 800px;">
        {{-- Info --}}
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px;">
            <div style="display: flex; gap: 10px;">
                <svg width="20" height="20" fill="none" stroke="#0284c7" viewBox="0 0 24 24" style="min-width: 20px; margin-top: 2px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div style="font-size: 13px; color: #0369a1;">
                    Calcule fechas de vencimiento de terminos procesales teniendo en cuenta dias habiles, festivos colombianos (Ley 51/1983) y vacancia judicial (Dic 20 - Ene 11). Esta herramienta es orientativa y no sustituye la verificacion manual del abogado.
                </div>
            </div>
        </div>

        {{-- Formulario --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Fecha de inicio</label>
                    <input type="date" wire:model="startDate" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Termino</label>
                    <input type="number" wire:model="term" min="1" max="365" placeholder="Ej: 20" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Tipo de dias</label>
                    <select wire:model="termType" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        <option value="business">Dias habiles</option>
                        <option value="calendar">Dias calendario</option>
                        <option value="months">Meses</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button wire:click="calculate" style="padding: 10px 24px; background: #3A86FF; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    Calcular vencimiento
                </button>
                <button wire:click="clear" style="padding: 10px 24px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    Limpiar
                </button>
            </div>
        </div>

        {{-- Resultado --}}
        @if($result)
            <div style="background: #fff; border-radius: 12px; border: 2px solid #10b981; padding: 24px; margin-bottom: 24px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Fecha de vencimiento</div>
                    <div style="font-size: 36px; font-weight: 800; color: #111827;">
                        {{ $result['deadline']->format('d/m/Y') }}
                    </div>
                    <div style="font-size: 14px; color: #6b7280;">
                        {{ $result['deadline']->translatedFormat('l') }}
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
                    <div style="text-align: center; padding: 12px; background: #f9fafb; border-radius: 8px;">
                        <div style="font-size: 20px; font-weight: 700; color: #3A86FF;">{{ $result['start_date']->format('d/m/Y') }}</div>
                        <div style="font-size: 11px; color: #6b7280;">Fecha inicio</div>
                    </div>
                    <div style="text-align: center; padding: 12px; background: #f9fafb; border-radius: 8px;">
                        <div style="font-size: 20px; font-weight: 700; color: #f59e0b;">{{ $result['calendar_days'] }}</div>
                        <div style="font-size: 11px; color: #6b7280;">Dias calendario</div>
                    </div>
                    <div style="text-align: center; padding: 12px; background: #f9fafb; border-radius: 8px;">
                        <div style="font-size: 20px; font-weight: 700; color: #10b981;">
                            {{ $result['type'] === 'business' ? $result['business_days'] . ' habiles' : ($result['type'] === 'months' ? $this->term . ' meses' : $result['calendar_days'] . ' calendario') }}
                        </div>
                        <div style="font-size: 11px; color: #6b7280;">Termino aplicado</div>
                    </div>
                </div>

                @if(count($result['skipped_days']) > 0)
                    <details style="margin-top: 12px;">
                        <summary style="font-size: 13px; color: #3A86FF; cursor: pointer; font-weight: 500;">
                            {{ count($result['skipped_days']) }} dias no habiles excluidos
                        </summary>
                        <div style="margin-top: 8px; max-height: 200px; overflow-y: auto;">
                            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                                <tr style="background: #f9fafb;">
                                    <th style="padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">Fecha</th>
                                    <th style="padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">Dia</th>
                                    <th style="padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">Razon</th>
                                </tr>
                                @foreach($result['skipped_days'] as $skipped)
                                    <tr>
                                        <td style="padding: 4px 8px; border-bottom: 1px solid #f3f4f6;">{{ \Carbon\Carbon::parse($skipped['date'])->format('d/m/Y') }}</td>
                                        <td style="padding: 4px 8px; border-bottom: 1px solid #f3f4f6;">{{ \Carbon\Carbon::parse($skipped['date'])->translatedFormat('l') }}</td>
                                        <td style="padding: 4px 8px; border-bottom: 1px solid #f3f4f6;">
                                            <span style="padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 500;
                                                {{ $skipped['reason'] === 'Festivo' ? 'background: #fef3c7; color: #92400e;' : ($skipped['reason'] === 'Vacancia judicial' ? 'background: #fee2e2; color: #991b1b;' : 'background: #f3f4f6; color: #6b7280;') }}">
                                                {{ $skipped['reason'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </details>
                @endif
            </div>

            {{-- Terminos comunes --}}
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin-bottom: 12px;">Terminos comunes de referencia</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 12px;">
                    @foreach([
                        ['Contestacion demanda (verbal)' => '20 dias habiles'],
                        ['Contestacion demanda (laboral)' => '10 dias habiles'],
                        ['Recurso de apelacion' => '3 dias habiles'],
                        ['Recurso de reposicion' => '3 dias habiles'],
                        ['Accion de tutela (fallo)' => '10 dias calendario'],
                        ['Traslado excepciones' => '3 dias habiles'],
                        ['Caducidad nulidad y restablecimiento' => '4 meses'],
                        ['Caducidad reparacion directa' => '2 anos'],
                    ] as $item)
                        @foreach($item as $name => $term)
                            <div style="display: flex; justify-content: space-between; padding: 6px 10px; background: #f9fafb; border-radius: 6px;">
                                <span style="color: #374151;">{{ $name }}</span>
                                <span style="font-weight: 600; color: #111827;">{{ $term }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
