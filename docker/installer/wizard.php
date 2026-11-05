<?php

declare(strict_types=1);

/*
 * Drives the stock Bitrix installation wizard over HTTP without a browser.
 *
 * The wizard keeps no state between steps: every POST with CurrentStepID runs that
 * step's handler. The online trial registration step is skipped; the "DEMO" key is
 * written exactly as the wizard does when registration is unavailable, so no personal
 * data leaves the machine. Only the main module is installed.
 */

const DOC_ROOT = '/var/www/html';
const SECRETS_DIR = '/run/notes-secrets';

final class WizardClient
{
    private readonly string $cookieFile;

    public function __construct(private readonly string $baseUrl)
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'wizard');
    }

    /**
     * @param array<string, string> $vars wizard variables (sent with the __wiz_ prefix)
     */
    public function post(string $step, array $vars, ?string $nextStep = null): string
    {
        $fields = ['CurrentStepID' => $step];
        if ($nextStep !== null) {
            $fields += ['StepNext' => 'Y', 'NextStepID' => $nextStep];
        }
        foreach ($vars as $name => $value) {
            $fields['__wiz_' . $name] = $value;
        }

        $curl = curl_init($this->baseUrl . '/index.php');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_TIMEOUT => 600,
        ]);
        $body = curl_exec($curl);
        if ($body === false) {
            throw new RuntimeException("Step {$step}: " . curl_error($curl));
        }

        if (preg_match('~inst-note-block-icon"></div><div class="inst-note-block-text">(.*?)</div>~s', $body, $error)) {
            throw new RuntimeException("Step {$step}: " . strip_tags(str_replace('<br />', '; ', $error[1])));
        }

        return $body;
    }
}

function secret(string $name): string
{
    return trim((string) file_get_contents(SECRETS_DIR . '/' . $name));
}

function writeInstallConfig(): void
{
    $modules = array_filter(
        array_map('basename', glob(DOC_ROOT . '/bitrix/modules/*', GLOB_ONLYDIR)),
        static fn(string $module): bool => $module !== 'main',
    );
    file_put_contents(DOC_ROOT . '/install.config', sprintf(
        '<?xml version="1.0" encoding="UTF-8"?><config><LANGUAGE_ID>ru</LANGUAGE_ID>'
        . '<INSTALL_CHARSET>utf-8</INSTALL_CHARSET><skipInstallModules>%s</skipInstallModules></config>',
        implode(',', $modules),
    ));
}

function installModules(WizardClient $wizard): void
{
    $vars = ['nextStep' => 'main', 'nextStepStage' => 'database'];
    while ($vars['nextStep'] !== '__finish') {
        echo "  {$vars['nextStep']} ({$vars['nextStepStage']})\n";
        $body = $wizard->post('create_modules', $vars);
        if (!preg_match("~'nextStep': '([^']*)', 'nextStepStage': '([^']*)'~", $body, $next) || str_contains($body, "SetStatus('0'")) {
            throw new RuntimeException('Module installation failed: ' . strip_tags($body));
        }
        $vars = ['nextStep' => $next[1], 'nextStepStage' => $next[2]];
    }
    $wizard->post('create_modules', ['nextStep' => '__finish']);
}

$wizard = new WizardClient($argv[1]);

file_put_contents(DOC_ROOT . '/bitrix/license_key.php', '<? $LICENSE_KEY = "DEMO"; ?>');
writeInstallConfig();

echo "Creating the database configuration...\n";
$wizard->post('create_database', [
    'dbType' => 'mysql',
    'host' => getenv('DB_HOST'),
    'database' => getenv('DB_NAME'),
    'user' => getenv('DB_USER'),
    'password' => secret('db_password'),
    'create_database' => 'N',
    'create_user' => 'N',
    'file_access_perms' => '0644',
    'folder_access_perms' => '0755',
], 'create_modules');
if (!is_file(DOC_ROOT . '/bitrix/.settings.php')) {
    throw new RuntimeException('The wizard did not create bitrix/.settings.php');
}

echo "Installing modules...\n";
installModules($wizard);

// The trial update check contacts the update server; it is skipped for trial installs.
$wizard->post('update_modules', ['nextStep' => 'check', 'lic_key_variant' => 'N']);
$wizard->post('update_modules', ['nextStep' => '__finish']);

echo "Creating the administrator...\n";
$password = secret('admin_password');
$wizard->post('create_admin', [
    'login' => 'admin',
    'admin_password' => $password,
    'admin_password_confirm' => $password,
    'email' => 'admin@example.com',
    'user_name' => 'Notes',
    'user_surname' => 'Administrator',
], 'select_wizard');

echo "Bitrix is installed.\n";
