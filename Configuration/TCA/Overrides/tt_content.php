<?php

// register frontend plugins for usage in the backend
//
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    // key of the extension
    'eidlogin',
     // unique name of the plugin
    'Login',
    // text to be shown in backend
    'eID-Login Link',
    // an icon to show in the backend
    'tx-eidlogin-npa'
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    // key of the extension
    'eidlogin',
     // unique name of the plugin
    'Settings',
    // text to be shown in backend
    'eID-Login Settings',
    // an icon to show in the backend
    'tx-eidlogin-npa'
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    // key of the extension
    'eidlogin',
     // unique name of the plugin
    'Saml',
    // text to be shown in backend
    'eID-Login SAML',
    // an icon to show in the backend
    'tx-eidlogin-npa'
);
