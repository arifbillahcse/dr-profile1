<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('project:check-production {--config-only : Check configuration and PHP without connecting to the database}')]
#[Description('Read-only production configuration, schema, administrator and media checks; never prints credentials')]
class CheckProduction extends Command
{
    private array $checks = [];

    public function handle(): int
    {
        $this->checks = [];
        $url = parse_url((string) config('app.url'));
        $host = strtolower($url['host'] ?? '');
        $validUrl = ($url['scheme'] ?? '') === 'https' && $host !== '' && ! isset($url['user']) && ! isset($url['pass'])
            && ! isset($url['query']) && ! isset($url['fragment']) && in_array($url['path'] ?? '', ['', '/'], true)
            && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && ! preg_match('/\.(example|test|invalid|localhost|local)$/D', $host);
        $this->check('APP_ENV is production', config('app.env') === 'production');
        $this->check('Debug output is disabled', config('app.debug') === false);
        $this->check('APP_URL is a real HTTPS origin without a path', $validUrl);
        $key = (string) config('app.key');
        $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $this->check('Application encryption key is valid', is_string($key) && Encrypter::supported($key, config('app.cipher')));
        $this->check('Session cookies require HTTPS', config('session.secure') === true);
        $this->check('Sessions are encrypted and cookies are HTTP-only', config('session.encrypt') === true && config('session.http_only') === true);
        $this->check('Session SameSite is lax or strict', in_array(config('session.same_site'), ['lax', 'strict'], true));
        $this->check('Session storage is database or Redis', in_array(config('session.driver'), ['database', 'redis'], true));
        $this->check('Rate-limit cache is database or Redis', in_array(config('cache.default'), ['database', 'redis'], true));
        $this->check('Production logging is not debug-level', in_array(config('logging.channels.single.level'), ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], true));
        $connection = config('database.default');
        $db = config('database.connections.'.$connection, []);
        $this->check('MySQL-compatible production database configured', in_array($db['driver'] ?? '', ['mysql', 'mariadb'], true) && filled($db['database'] ?? null) && ! str_ends_with((string) ($db['database'] ?? ''), '_test'));
        $this->check('Dedicated database user and password configured', filled($db['username'] ?? null) && ($db['username'] ?? '') !== 'root' && filled($db['password'] ?? null));
        $this->check('Database uses utf8mb4', ($db['charset'] ?? '') === 'utf8mb4');
        $this->check('Trusted proxies are explicitly scoped', ! in_array('*', config('app.trusted_proxies', []), true));
        $this->check('PHP 8.3 or later', version_compare(PHP_VERSION, '8.3.0', '>='));
        foreach (['curl', 'dom', 'fileinfo', 'gd', 'mbstring', 'openssl', 'pdo_mysql', 'xml'] as $extension) {
            $this->check('PHP extension: '.$extension, extension_loaded($extension));
        }
        $this->check('GD supports WebP', function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false));

        if (! $this->option('config-only')) {
            $this->inspectInstallation();
        }
        $this->table(['Check', 'Result'], array_map(fn (array $check): array => [$check[0], $check[1] ? 'PASS' : 'FAIL'], $this->checks));
        $failed = count(array_filter($this->checks, fn (array $check): bool => ! $check[1]));
        if ($failed > 0) {
            $this->error($failed.' production check(s) failed. Review the deployment instructions. No settings were changed.');

            return self::FAILURE;
        }
        $this->info($this->option('config-only') ? 'Configuration checks passed. Database, media, web server and HTTPS still need verification.' : 'Application checks passed. Verify the public document root, HTTPS, backups and live browser flows before launch.');

        return self::SUCCESS;
    }

    private function inspectInstallation(): void
    {
        foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/public'] as $directory) {
            $this->check('Writable directory: '.$directory, is_dir(base_path($directory)) && is_writable(base_path($directory)));
        }
        try {
            DB::connection()->getPdo();
            $tables = array_unique(['migrations', 'users', 'sessions', 'cache', 'cache_locks', 'website_imports', ...array_map(fn (string $class): string => (new $class)->getTable(), array_values(config('content.modules')))]);
            $schemaReady = true;
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    $schemaReady = false;
                }
            }
            $this->check('Database connection and required tables', $schemaReady);
            $migrations = array_keys(app('migrator')->getMigrationFiles(database_path('migrations')));
            $ran = DB::table('migrations')->pluck('migration')->all();
            $this->check('All application migrations have run', array_diff($migrations, $ran) === []);
            $this->check('An administrator account exists', User::where('is_admin', true)->exists());
            $this->check('Development administrator email is absent', ! User::where('email', 'admin@doctor-profile.test')->exists());
            $missingMedia = false;
            foreach (MediaAsset::where('disk', 'public')->cursor() as $media) {
                if (! preg_match('~^uploads/[0-9]{4}/[0-9]{2}/[a-f0-9-]{36}\.webp$~D', $media->path) || ! Storage::disk('public')->exists($media->path)) {
                    $missingMedia = true;
                    break;
                }
            }
            $this->check('Managed public image files are present', ! $missingMedia);
        } catch (Throwable) {
            $this->check('Database and media inspection completed (see hosting configuration)', false);
        }
    }

    private function check(string $label, bool $passed): void
    {
        $this->checks[] = [$label, $passed];
    }
}
