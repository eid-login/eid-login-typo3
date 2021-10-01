<?php

// general config of the extension to be read my extension manager / repository
$EM_CONF[$_EXTKEY] = [
    'title' => 'eID-Login',
    'description' => 'The eID-Login extension for TYPO3 allows to use the German eID-card and similar electronic identity documents for secure and privacy-friendly Website-User login. For this purpose, a so-called eID-Client, such as the AusweisApp2 or the Open eCard App and a eID-Service are required. In the default configuration a suitable eID-Service is provided without any additional costs.',
    'category' => 'plugin',
    'author' => 'ecsec GmbH',
    'author_company' => 'ecsec GmbH',
    'author_email' => 'eID-Login@ecsec.de',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'scheduler' => '10.4.21-11.4.99',
            'typo3' => '10.4.21-11.4.99',
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
