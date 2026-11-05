<?php

declare(strict_types=1);

// Installs the app.notes module; project settings live in local/.settings_extra.php.

use Bitrix\Main\ModuleManager;

$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!ModuleManager::isModuleInstalled('app.notes')) {
    CModule::CreateModuleObject('app.notes')->DoInstall();
    echo "Module app.notes installed.\n";
}
