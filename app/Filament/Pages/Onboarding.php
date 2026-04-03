<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Onboarding extends Page
{
    protected string $view = 'filament.pages.onboarding';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->redirect(FirmSettings::getUrl());
    }
}
