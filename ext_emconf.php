<?php

// general config of the extension to be read my extension manager / repository
$EM_CONF[$_EXTKEY] = [
    'title' => 'eID-Login',
    'description' => 'Integration of mobile electronic identities at a substantial security level',
    'category' => 'plugin',
    'author' => 'ecsec GmbH',
    'author_company' => 'ecsec GmbH',
    'author_email' => 'eID-Login@ecsec.de',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'scheduler' => '10.4.21-10.99',
            'typo3' => '10.4.21-10.99',
            'php' => '7.4.8-7.4.99',
        ]
    ],
    'autoload' => [
        'psr-4' => [
            'Ecsec\\Eidlogin\\' => 'Classes',
            'Ecsec\\Eidlogin\\Dep\\' => 'dep'
        ]
    ],
];
