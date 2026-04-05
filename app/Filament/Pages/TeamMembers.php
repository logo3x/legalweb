<?php

namespace App\Filament\Pages;

use App\Models\CasePermission;
use App\Models\FirmInvitation;
use App\Models\LegalCase;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
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
            ->with('invitedBy')
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
        $firmCases = LegalCase::where('firm_id', auth()->user()->firm_id)
            ->where('is_demo', false)
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => "{$c->case_number} - {$c->title}"])
            ->toArray();

        return [
            Action::make('invite')
                ->label('Invitar Colaborador')
                ->icon('heroicon-o-user-plus')
                ->modalWidth('2xl')
                ->form([
                    TextInput::make('email')
                        ->label('Correo de Google del colaborador')
                        ->email()
                        ->required()
                        ->placeholder('colaborador@gmail.com'),
                    Select::make('role')
                        ->label('Rol')
                        ->options([
                            'abogado' => 'Abogado',
                            'asistente' => 'Asistente',
                        ])
                        ->required()
                        ->default('abogado'),
                    Select::make('case_ids')
                        ->label('Casos a compartir')
                        ->options($firmCases)
                        ->multiple()
                        ->searchable()
                        ->preload(),
                    CheckboxList::make('case_permissions')
                        ->label('Permisos sobre los casos')
                        ->options(CasePermission::CASE_PERMISSIONS)
                        ->columns(2)
                        ->default(array_keys(CasePermission::CASE_PERMISSIONS))
                        ->helperText('Estos permisos aplican a todos los casos seleccionados arriba'),
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

                    $invitation = FirmInvitation::createForEmail(
                        $firm,
                        auth()->user(),
                        $email,
                        $data['role'],
                        $data['case_permissions'] ?? [],
                    );

                    // Guardar casos pre-asignados en la invitacion
                    $invitation->update([
                        'permissions' => [
                            'case_ids' => $data['case_ids'] ?? [],
                            'case_permissions' => $data['case_permissions'] ?? [],
                        ],
                    ]);

                    Notification::make()
                        ->title('Invitacion enviada')
                        ->body("Cuando {$email} inicie sesion con Google se vinculara automaticamente con acceso a ".count($data['case_ids'] ?? []).' caso(s).')
                        ->success()
                        ->send();
                }),
        ];
    }
}
