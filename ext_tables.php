<?php

// prevent script from being called directly
//defined('TYPO3') || die('Access denied.'); // needed for 11
defined('TYPO3_MODE') || die('Access denied.');

// encapsulate all locally defined variables
call_user_func(
    function () {
        // register backend module for configuration of the eidlogin extension
        //
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'eidlogin',
            'system',
            'tx_eidlogin',
            'top',
            [
                \Ecsec\Eidlogin\Controller\BackendController::class => 'index, showSettings, fetchIdpMeta, saveSettings, toggleActivated, resetSettings, prepareRollover, executeRollover',
            ],
            [
                // only admins can use module
                'access' => 'admin',
                // the icon to show, defined and registered in ext_localconf.php
                'iconIdentifier' => 'tx-eidlogin-npa',
                // holding strings to display in backend navigation
                'labels' => 'LLL:EXT:eidlogin/Resources/Private/Language/locallang.xlf',
            ]
        );
    }
);
