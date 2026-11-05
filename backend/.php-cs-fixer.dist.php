<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/local', __DIR__ . '/tests'])
    ->ignoreDotFiles(false);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer.cache')
    ->setRules([
        '@PER-CS3x0' => true,
        '@PER-CS3x0:risky' => true,
        '@PHP8x3Migration' => true,
        '@PHP8x3Migration:risky' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        'strict_comparison' => true,
        'strict_param' => true,
    ])
    ->setFinder($finder);
