<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use App\Filament\Resources\LegalCases\RelationManagers\Concerns\ChecksCasePermissions;
use App\Models\CaseEvent;
use App\Models\Document;
use App\Notifications\CaseUpdatedNotification;
use App\Services\TybaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EventsRelationManager extends RelationManager
{
    use ChecksCasePermissions;

    protected static function casePermissionFor(string $action): ?string
    {
        return match ($action) {
            'viewAny', 'view' => 'events.view',
            'create' => 'events.create',
            default => 'events.edit',
        };
    }

    protected static string $relationship = 'events';

    protected static ?string $title = 'Actuaciones';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                // Los anexos de la Rama tambien se ven al abrir la actuacion, no solo con el boton de la lista.
                Section::make('Documentos anexos en la Rama Judicial')
                    ->key('ramaDocuments')
                    ->icon('heroicon-o-paper-clip')
                    ->compact()
                    ->columnSpanFull()
                    ->visible(fn (?CaseEvent $record): bool => $this->eventHasRamaDocuments($record))
                    ->schema([
                        SchemaView::make('filament.modals.rama-documents')
                            ->viewData(function (?CaseEvent $record): array {
                                [$documentos, $error] = $record ? $this->fetchRamaDocuments($record) : [[], null];

                                return [
                                    'documentos' => $documentos,
                                    'error' => $error,
                                    'guardados' => $record ? $this->savedDocumentUrls($record) : [],
                                ];
                            }),
                    ]),
                DateTimePicker::make('event_date')
                    ->label('Fecha del Evento')
                    ->required(),
                Select::make('event_type')
                    ->label('Tipo')
                    ->options([
                        'actuacion' => 'Actuación',
                        'audiencia' => 'Audiencia',
                        'notificacion' => 'Notificación',
                        'memorial' => 'Memorial',
                        'auto' => 'Auto',
                        'sentencia' => 'Sentencia',
                    ])
                    ->required()
                    ->default('actuacion'),
                Toggle::make('is_milestone')
                    ->label('Hito importante'),
                Select::make('user_id')
                    ->label('Registrado por')
                    ->relationship('user', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('event_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->weight(fn ($record) => $record->created_at?->gt(now()->subHours(24)) ? 'bold' : null)
                    ->icon(fn ($record) => $record->created_at?->gt(now()->subHours(24)) ? 'heroicon-s-sparkles' : null)
                    ->iconColor('warning')
                    ->tooltip(fn ($record) => $record->created_at?->gt(now()->subHours(24)) ? 'Nueva - detectada en ultima sincronizacion' : null),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'audiencia' => 'warning',
                        'sentencia' => 'success',
                        'notificacion' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->label('Detalle')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_milestone')
                    ->label('Hito')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_date', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva Actuación'),
            ])
            ->recordActions([
                $this->ramaDocumentsAction(),
                // Accion inmediata con confirmacion: el abogado ve a que correo se envia y si salio.
                Action::make('notify_client')
                    ->label('Avisar al cliente')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->visible(fn (): bool => $this->getOwnerRecord()->client !== null)
                    ->authorize(fn (): bool => static::userHasCasePermission($this->getOwnerRecord(), 'portal.share'))
                    ->disabled(fn (): bool => ! $this->clientCanReceiveEmail())
                    ->tooltip(fn (): ?string => $this->clientCanReceiveEmail() ? null : $this->clientEmailProblem())
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-envelope')
                    ->modalHeading('Avisar al cliente por correo')
                    ->modalDescription(fn (CaseEvent $record): string => 'Se enviará ahora a '.$this->getOwnerRecord()->client->email.' un correo con la actuación «'.$record->title.'» del '.$record->event_date->format('d/m/Y').'.')
                    ->modalSubmitActionLabel('Enviar correo')
                    ->action(function (CaseEvent $record): void {
                        $case = $this->getOwnerRecord();
                        $client = $case->client;

                        try {
                            $client->notifyNow(new CaseUpdatedNotification($case, $record));
                        } catch (\Throwable $e) {
                            Log::warning('Aviso de actuacion al cliente no enviado', ['event' => $record->id, 'error' => $e->getMessage()]);

                            Notification::make()
                                ->title('No se pudo enviar el correo')
                                ->body('Intente de nuevo en unos minutos. Si el problema sigue, revise el correo del cliente.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Correo enviado')
                            ->body("El cliente recibirá el aviso en {$client->email}. Si no le llega, pídale revisar la carpeta de spam.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Ver, descargar y guardar en Documentos del caso los archivos que el despacho publico en la actuacion.
     */
    private function ramaDocumentsAction(): Action
    {
        return Action::make('rama_documents')
            ->label('Documentos')
            ->icon('heroicon-o-paper-clip')
            ->color('gray')
            ->visible(fn (CaseEvent $record): bool => $this->eventHasRamaDocuments($record))
            ->modalHeading(fn (CaseEvent $record): string => 'Documentos de «'.$record->title.'»')
            ->modalDescription(fn (CaseEvent $record): string => 'Publicados por el despacho en la Rama Judicial para la actuación del '.$record->event_date->format('d/m/Y').'.')
            ->modalContent(function (CaseEvent $record): View {
                [$documentos, $error] = $this->fetchRamaDocuments($record);

                return view('filament.modals.rama-documents', [
                    'documentos' => $documentos,
                    'error' => $error,
                    'guardados' => $this->savedDocumentUrls($record),
                ]);
            })
            ->modalSubmitActionLabel('Guardar en Documentos del caso')
            ->modalSubmitAction(fn (Action $action): Action => $action->visible(static::userHasCasePermission($this->getOwnerRecord(), 'documents.upload')))
            ->modalCancelActionLabel('Cerrar')
            ->action(function (CaseEvent $record): void {
                [$documentos, $error] = $this->fetchRamaDocuments($record);

                if ($error !== null || $documentos === []) {
                    Notification::make()->title('No hay documentos para guardar')->body($error)->warning()->send();

                    return;
                }

                $existentes = $this->savedDocumentUrls($record);
                $guardados = 0;

                foreach ($documentos as $doc) {
                    if (in_array($doc['url'], $existentes, true)) {
                        continue;
                    }

                    Document::create([
                        'legal_case_id' => $record->legal_case_id,
                        'case_event_id' => $record->id,
                        'name' => mb_substr($doc['nombre'], 0, 250),
                        'description' => trim('Anexo de la actuación «'.$record->title.'» del '.$record->event_date->format('d/m/Y').' en la Rama Judicial. '.$doc['descripcion']),
                        'file_type' => $doc['tipo'] !== '' ? mb_substr($doc['tipo'], 0, 50) : null,
                        'external_url' => $doc['url'],
                        'responsible' => 'juzgado',
                        'status' => 'recibido',
                        'received_at' => $doc['fecha'] ? Carbon::createFromFormat('d/m/Y', $doc['fecha'])->startOfDay() : now(),
                        'uploaded_by' => auth()->id(),
                    ]);
                    $guardados++;
                }

                Notification::make()
                    ->title($guardados > 0 ? ($guardados === 1 ? 'Documento guardado' : "{$guardados} documentos guardados") : 'Ya estaban guardados')
                    ->body('Están en la pestaña Documentos del caso, con el enlace de descarga de la Rama Judicial.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array{0: list<array{id: string, nombre: string, descripcion: string, tipo: string, fecha: ?string, url: string}>, 1: ?string}
     */
    private function fetchRamaDocuments(CaseEvent $record): array
    {
        try {
            // Los anexos de una actuacion no cambian: se guardan un rato para que abrir y cerrar
            // la actuacion no consulte la Rama cada vez.
            $idRegActuacion = substr((string) $record->rama_ref, 2);
            $documentos = Cache::remember('rama-docs:'.$idRegActuacion, now()->addMinutes(30), fn (): array => app(TybaService::class)->actuacionDocuments($idRegActuacion));

            return [$documentos, null];
        } catch (\Throwable $e) {
            Log::warning('Documentos de actuacion: la Rama no respondio', ['event' => $record->id, 'error' => $e->getMessage()]);

            return [[], 'La Rama Judicial no respondió. Intente de nuevo en unos minutos.'];
        }
    }

    private function eventHasRamaDocuments(?CaseEvent $record): bool
    {
        return $record !== null && $record->rama_documents && str_starts_with((string) $record->rama_ref, 'r:');
    }

    /**
     * @return list<string>
     */
    private function savedDocumentUrls(CaseEvent $record): array
    {
        return Document::where('legal_case_id', $record->legal_case_id)->whereNotNull('external_url')->pluck('external_url')->all();
    }

    private function clientCanReceiveEmail(): bool
    {
        return $this->clientEmailProblem() === null;
    }

    /**
     * Por que no se le puede escribir al cliente, o null si se puede.
     */
    private function clientEmailProblem(): ?string
    {
        $client = $this->getOwnerRecord()->client;

        return match (true) {
            $client === null => 'El caso no tiene cliente.',
            $client->is_demo => 'Es un cliente de ejemplo: no se le envían correos.',
            blank($client->email) => 'El cliente no tiene correo. Agréguelo en Clientes para poder avisarle.',
            default => null,
        };
    }
}
