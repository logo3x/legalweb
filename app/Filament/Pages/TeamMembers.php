<?php

namespace App\Filament\Pages;

use App\Models\FirmInvitation;
use App\Models\LegalCase;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TeamMembers extends Page
{
    protected string $view = 'filament.pages.team-members';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Configuracion';

    protected static ?string $navigationLabel = 'Equipo';

    protected static ?string $title = 'Equipo de Trabajo';

    protected static ?int $navigationSort = 21;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getMembers()
    {
        return User::where('firm_id', auth()->user()->firm_id)
            ->with('casePermissions.legalCase')
            ->get();
    }

    public function getPendingInvitations()
    {
        return FirmInvitation::where('firm_id', auth()->user()->firm_id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();
    }

    public function getFirmCases()
    {
        return LegalCase::where('firm_id', auth()->user()->firm_id)
            ->where('is_demo', false)
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Invitar Colaborador')
                ->icon('heroicon-o-user-plus')
                ->form([
                    TextInput::make('email')
                        ->label('Correo de Google del colaborador')
                        ->email()
                        ->required()
                        ->placeholder('colaborador@gmail.com')
                        ->helperText('El colaborador debera iniciar sesion con esta cuenta de Google'),
                    Select::make('role')
                        ->label('Rol')
                        ->options([
                            'abogado' => 'Abogado',
                            'asistente' => 'Asistente',
                        ])
                        ->required()
                        ->default('abogado')
                        ->helperText('Despues de vincularse podra asignarle casos y permisos especificos'),
                ])
                ->action(function (array $data) {
                    $email = strtolower(trim($data['email']));
                    $firm = auth()->user()->firm;

                    $existingUser = User::where('email', $email)->where('firm_id', $firm->id)->first();
                    if ($existingUser) {
                        Notification::make()->title('Este usuario ya es parte de su equipo.')->warning()->send();

                        return;
                    }

                    $existingInvite = FirmInvitation::where('email', $email)
                        ->where('firm_id', $firm->id)
                        ->where('status', 'pending')
                        ->first();

                    if ($existingInvite) {
                        Notification::make()->title('Ya existe una invitacion pendiente para este correo.')->warning()->send();

                        return;
                    }

                    FirmInvitation::createForEmail(
                        $firm,
                        auth()->user(),
                        $email,
                        $data['role'],
                    );

                    Notification::make()
                        ->title('Invitacion creada')
                        ->body("Cuando {$email} inicie sesion con Google se vinculara automaticamente. Despues podra asignarle casos especificos.")
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    public function assignCases(int $userId): void
    {
        // This is called from the view via wire:click
    }
}
