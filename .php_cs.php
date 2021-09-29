<?php
declare(strict_types=1);
require './vendor/autoload.php';

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()
    ->notPath('dep')
    ->notPath('vendor')
    ->notPath('public')
    ->in(__DIR__);

return $config;
