<?php

namespace App\Filament\Resources\MassEmailCampaigns\Tables;

use App\Jobs\SendMassEmailCampaign;
use App\Models\MassEmailCampaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MassEmailCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->subject),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MassEmailCampaign::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'borrador' => 'gray',
                        'programado' => 'info',
                        'enviando' => 'warning',
                        'enviado' => 'success',
                        'fallido' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('audience_type')
                    ->label('Audiencia')
                    ->formatStateUsing(fn (string $state) => MassEmailCampaign::AUDIENCE_TYPES[$state] ?? $state),
                TextColumn::make('recipients_count')
                    ->label('Total')
                    ->alignCenter(),
                TextColumn::make('sent_count')
                    ->label('Enviados')
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('failed_count')
                    ->label('Fallidos')
                    ->alignCenter()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('scheduled_at')
                    ->label('Programado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextColumn::make('sent_at')
                    ->label('Enviado el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(MassEmailCampaign::STATUSES),
                SelectFilter::make('audience_type')
                    ->label('Audiencia')
                    ->options(MassEmailCampaign::AUDIENCE_TYPES),
            ])
            ->recordActions([
                Action::make('enviar_ahora')
                    ->label('Enviar ahora')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => 'Enviar correo a la audiencia seleccionada')
                    ->modalDescription('Se enviara el correo a todos los destinatarios que coincidan con los filtros de audiencia. Esta accion no se puede deshacer.')
                    ->modalSubmitActionLabel('Si, enviar ahora')
                    ->visible(fn ($record) => in_array($record->status, ['borrador', 'programado', 'fallido']))
                    ->action(function ($record) {
                        $count = $record->resolveRecipients()->count();
                        $record->update(['recipients_count' => $count, 'status' => 'programado']);
                        SendMassEmailCampaign::dispatch($record->id);
                        Notification::make()
                            ->title('Envio en curso')
                            ->body("La campania se esta enviando a {$count} destinatario(s). Vera el estado actualizado en unos segundos.")
                            ->success()
                            ->send();
                    }),
                Action::make('preview_recipients')
                    ->label('Ver destinatarios')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $users = $record->resolveRecipients();

                        return view('filament.modals.mass-email-recipients', [
                            'users' => $users,
                            'count' => $users->count(),
                        ]);
                    }),
                EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['borrador', 'programado', 'fallido'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
