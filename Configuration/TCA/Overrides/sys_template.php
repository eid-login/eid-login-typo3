<?php

// prevent script from being called directly
//defined('TYPO3') || die('Access denied.'); // needed for 11
defined('TYPO3_MODE') or die();

// Make extensions TypoScript sys_template available for include in templates
//
call_user_func(function () {
    $extensionKey = 'eidlogin';

    /**
     * Default TypoScript
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript',
        'eID-Login SAML Template'
    );
});
