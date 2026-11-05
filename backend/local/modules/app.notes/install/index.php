<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\ModuleManager;

class app_notes extends CModule
{
    public function __construct()
    {
        include __DIR__ . '/version.php';
        /** @var array{VERSION: string, VERSION_DATE: string} $arModuleVersion */
        $this->MODULE_ID = 'app.notes';
        $this->MODULE_NAME = 'Notes';
        $this->MODULE_DESCRIPTION = 'REST API for notes';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    public function DoInstall(): void
    {
        $this->runSql('install.sql');
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall(): void
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->runSql('uninstall.sql');
    }

    private function runSql(string $file): void
    {
        /** @var Connection $connection */
        $connection = Application::getConnection();
        /** @var list<string> $errors */
        $errors = $connection->executeSqlBatch((string) file_get_contents(__DIR__ . '/db/mysql/' . $file));
        if ($errors !== []) {
            throw new RuntimeException(implode('; ', $errors));
        }
    }
}
