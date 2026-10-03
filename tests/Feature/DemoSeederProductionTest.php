<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_never_created_in_production(): void
    {
        $this->app['env'] = 'production';

        // Igual que el boton Seed de setup.php.
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Firm::count());
    }

    public function test_demo_data_is_clearly_marked_as_example(): void
    {
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class])
            ->expectsOutputToContain('DATOS DE EJEMPLO')
            ->assertSuccessful();

        $this->assertSame([DatabaseSeeder::DEMO_FIRM_NAME], Firm::pluck('name')->all());

        $users = User::all();
        $this->assertCount(3, $users);

        foreach ($users as $user) {
            $this->assertStringEndsWith('@example.com', $user->email);
            $this->assertStringContainsString('(Demo)', $user->name);
            $this->assertFalse(Hash::check('password', $user->password), 'Sin contrasena fija conocida');
        }

        $this->assertFalse(Client::withoutGlobalScopes()->where('is_demo', false)->exists());
        $this->assertFalse(LegalCase::withoutGlobalScopes()->where('is_demo', false)->exists());
    }
}
