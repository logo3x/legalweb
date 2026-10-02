<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_never_created_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->seed(DatabaseSeeder::class);

        $this->assertFalse(User::where('email', 'admin@legalweb.co')->exists());
        $this->assertSame(0, User::count());
    }
}
