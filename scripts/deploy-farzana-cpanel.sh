#!/usr/bin/env bash
set -Eeuo pipefail

docroot=/disk2/farzana/public_html
app_root=/disk2/farzana/doctor-app
expected_host=drfarzanashathi.com
source_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"

fail() {
    printf 'Deployment stopped: %s\n' "$*" >&2
    exit 1
}

[[ "${1:-}" == '' || "${1:-}" == '--check' ]] || fail 'Only --check is supported.'
[[ "$source_root" == "$(cd "$docroot" && pwd -P)" ]] || fail 'Run the script from the Git repository in /disk2/farzana/public_html.'
[[ "$(git -C "$source_root" rev-parse --show-toplevel)" == "$source_root" ]] || fail 'The source directory is not the repository root.'
[[ -f "$app_root/artisan" ]] || fail "Laravel artisan is missing in $app_root."
[[ -f "$app_root/.env" ]] || fail "The existing Laravel .env is missing in $app_root."
[[ -f "$app_root/vendor/autoload.php" ]] || fail 'Install the application Composer dependencies first.'

php_bin=''
for candidate in /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php; do
    if [[ -x "$candidate" ]]; then
        php_bin="$candidate"
        break
    fi
done
if [[ -z "$php_bin" ]]; then
    php_bin="$(command -v php || true)"
fi
[[ -n "$php_bin" ]] || fail 'PHP was not found.'
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || fail 'PHP 8.3 or newer is required.'

app_info="$("$php_bin" -r '
require $argv[1]."/vendor/autoload.php";
$settings = Dotenv\Dotenv::parse(file_get_contents($argv[1]."/.env"));
if (strtolower((string) parse_url($settings["APP_URL"] ?? "", PHP_URL_HOST)) !== "drfarzanashathi.com") {
    fwrite(STDERR, "Set APP_URL=https://drfarzanashathi.com in doctor-app/.env before deployment.\n");
    exit(1);
}
$app = require $argv[1]."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo strtolower((string) parse_url(config("app.url"), PHP_URL_HOST)), "\n", $app->publicPath();
' "$app_root")"
app_host="${app_info%%$'\n'*}"
runtime_public="${app_info#*$'\n'}"
runtime_public="${runtime_public%/}"
[[ "$app_host" == "$expected_host" ]] || fail "The Laravel APP_URL hostname is $app_host; expected $expected_host."
[[ "$runtime_public" == "$docroot" || "$runtime_public" == "$app_root/public" ]] || fail "Unexpected Laravel public path: $runtime_public."

core_paths=(backend/app backend/config backend/database backend/resources backend/routes)
asset_paths=(backend/public/site backend/public/css backend/public/js backend/public/vendor)
for tracked_path in "${core_paths[@]}" "${asset_paths[@]}"; do
    git -C "$source_root" cat-file -e "HEAD:$tracked_path" || fail "Missing repository directory: $tracked_path."
done

printf 'Git source: %s\nLaravel runtime: %s\nServed assets: %s\nLaravel public path: %s\n' "$source_root" "$app_root" "$docroot" "$runtime_public"
if [[ "${1:-}" == '--check' ]]; then
    printf 'Preflight passed. No files or database records were changed.\n'
    exit 0
fi

umask 077
mkdir -p /disk2/farzana/site-backups
backup_root="$(mktemp -d "/disk2/farzana/site-backups/deploy-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXX")"
trap 'printf "Deployment stopped at line %s. Existing source backup: %s\n" "$LINENO" "${backup_root:-not yet created}" >&2' ERR

tar -czf "$backup_root/application-source.tar.gz" -C "$app_root" app config database resources routes
printf 'Source backup: %s\n' "$backup_root"

# Git archive reads committed files even when earlier hosting setup moved them out of the working tree.
git -C "$source_root" archive HEAD "${core_paths[@]}" |
    tar -x --strip-components=1 --no-same-owner --same-permissions -C "$app_root"

deploy_assets() {
    local destination="$1"
    local label="$2"
    local existing=()
    local directory
    for directory in site css js vendor; do
        if [[ -d "$destination/$directory" ]]; then
            existing+=("$directory")
        fi
    done
    if (( ${#existing[@]} > 0 )); then
        tar -czf "$backup_root/$label-assets.tar.gz" -C "$destination" "${existing[@]}"
    fi
    mkdir -p "$destination"
    git -C "$source_root" archive HEAD "${asset_paths[@]}" |
        tar -x --strip-components=2 --no-same-owner --same-permissions -C "$destination"
}

deploy_assets "$docroot" served
if [[ "$runtime_public" != "$docroot" && ! "$runtime_public" -ef "$docroot" ]]; then
    deploy_assets "$runtime_public" runtime
fi

# Keep the hosting bootstrap, index.php, .htaccess, .env, vendor and uploaded storage in place.
cd "$app_root"
"$php_bin" artisan optimize:clear --no-interaction
"$php_bin" artisan migrate --force --no-interaction
"$php_bin" artisan website:match-demo --no-interaction
"$php_bin" artisan optimize --no-interaction

printf 'Deployment commands completed for https://%s/\nSource commit: %s\nBackup: %s\n' "$expected_host" "$(git -C "$source_root" rev-parse --short HEAD)" "$backup_root"
