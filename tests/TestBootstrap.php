<?php declare(strict_types=1);

$pluginRoot = dirname(__DIR__);

require $pluginRoot . '/vendor/autoload.php';

$shopwareAutoloadCandidates = [
    $pluginRoot . '/../../../vendor/autoload.php',
    '/opt/shopware/vendor/autoload.php',
];

foreach ($shopwareAutoloadCandidates as $autoloadFile) {
    if (!is_file($autoloadFile)) {
        continue;
    }

    require $autoloadFile;
    break;
}
