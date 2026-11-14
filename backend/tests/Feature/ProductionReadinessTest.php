<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private array $originalDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDatabase = config('database');
    }

    protected function tearDown(): void
    {
        config(['database' => $this->originalDatabase]);
        parent::tearDown();
    }

    private function productionConfiguration(): void
    {
        config([
            'app.env' => 'production', 'app.debug' => false, 'app.url' => 'https://clinic.example.org',
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)), 'app.cipher' => 'AES-256-CBC',
            'app.trusted_proxies' => [],
            'session.secure' => true, 'session.encrypt' => true, 'session.http_only' => true,
            'session.same_site' => 'lax', 'session.driver' => 'database', 'cache.default' => 'database',
            'logging.channels.single.level' => 'warning',
            'database.default' => 'mysql', 'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'clinic_production', 'database.connections.mysql.username' => 'clinic_user',
            'database.connections.mysql.password' => 'Not-a-real-database-secret', 'database.connections.mysql.charset' => 'utf8mb4',
        ]);
    }

    public function test_configuration_check_accepts_secure_values_without_connecting_or_printing_secrets(): void
    {
        $this->productionConfiguration();
        $this->artisan('project:check-production', ['--config-only' => true])
            ->expectsOutputToContain('Configuration checks passed')
            ->doesntExpectOutputToContain('Not-a-real-database-secret')
            ->doesntExpectOutputToContain(base64_encode(str_repeat('k', 32)))
            ->assertSuccessful();
    }

    public static function unsafeConfiguration(): array
    {
        return [
            ['app.env', 'local'], ['app.debug', true], ['app.url', 'http://clinic.example.org'],
            ['app.url', 'https://localhost'], ['app.url', 'https://your-domain.example'],
            ['app.url', 'https://clinic.example.org/subdirectory'], ['app.url', 'https://user@clinic.example.org'],
            ['app.key', 'short'], ['session.secure', false], ['session.encrypt', false],
            ['session.http_only', false], ['session.same_site', 'none'], ['session.driver', 'array'],
            ['cache.default', 'array'], ['logging.channels.single.level', 'debug'],
            ['database.connections.mysql.database', 'doctor_profile_test'], ['database.connections.mysql.username', 'root'],
            ['database.connections.mysql.password', ''], ['database.connections.mysql.charset', 'utf8'],
            ['app.trusted_proxies', ['*']],
        ];
    }

    #[DataProvider('unsafeConfiguration')]
    public function test_configuration_check_fails_for_unsafe_settings(string $key, mixed $value): void
    {
        $this->productionConfiguration();
        config([$key => $value]);
        $this->artisan('project:check-production', ['--config-only' => true])->expectsOutputToContain('production check(s) failed')->assertFailed();
    }

    public function test_full_check_does_not_turn_a_development_installation_into_production_or_create_accounts(): void
    {
        $originalEnvironment = config('app.env');
        $this->artisan('project:check-production')->expectsOutputToContain('production check(s) failed')->assertFailed();
        $this->assertSame($originalEnvironment, config('app.env'));
        $this->assertSame(0, User::count());
    }

    public function test_public_and_admin_pages_include_security_headers_without_changing_private_cache_behavior(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')->assertHeader('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'self'")
            ->assertHeaderMissing('Strict-Transport-Security');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_hsts_is_enabled_only_for_secure_production_requests_and_hosts_are_restricted(): void
    {
        config(['app.url' => 'https://clinic.example.org']);
        $this->app['env'] = 'production';
        try {
            $this->get('https://clinic.example.org/')->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000');
            $this->get('http://clinic.example.org/')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
            $this->get('https://untrusted.example.org/')->assertStatus(400);
        } finally {
            Request::setTrustedHosts([]);
            $this->app['env'] = 'testing';
        }
    }
}
