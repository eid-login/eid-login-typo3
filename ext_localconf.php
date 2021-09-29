<?php

// prevent script from being called directly
//defined('TYPO3') || die('Access denied.'); // needed for 11
defined('TYPO3_MODE') || die('Access denied.');

// encapsulate vars
call_user_func(
    function () {
        // configuration of frontend plugins for usage in the frontend
        //
        // the eid login link
        $loginActions = [
        \Ecsec\Eidlogin\Controller\FrontendController::class => 'showLogin, startLogin, logout',
    ];
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            // key of the extension
            'eidlogin',
            // unique name of the frontend plugin
            'Login',
            // allowed actions
            $loginActions,
            // non-cacheable actions
            $loginActions
        );
        // the eid settings
        $settingsActions = [
        \Ecsec\Eidlogin\Controller\FrontendController::class=> 'showSettings, createEid, deleteEid, disablePwLogin, enablePwLogin',
    ];
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            // key of the extension
            'eidlogin',
            // unique name of the frontend plugin
            'Settings',
            // allowed actions
            $settingsActions,
            // non-cacheable actions
            $settingsActions
        );
        // the technical saml actions, meta must be first to have it used as default controller
        $samlActions = [
        \Ecsec\Eidlogin\Controller\SamlController::class=> 'meta, tcToken, acsPost, acsRedirect, resume',
    ];
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            // key of the extension
            'eidlogin',
            // unique name of the frontend plugin
            'Saml',
            // allowed actions
            $samlActions,
            // non-cacheable actions
            $samlActions
        );
        // register the eid / npa icon for general usage
        //
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Imaging\IconRegistry::class
        );
        $iconRegistry->registerIcon(
            // identifier
            'tx-eidlogin-npa',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:eidlogin/Resources/Public/Icons/Extension.png']
        );
        // add our eid based frontend user auth service
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService('eidlogin', 'auth', \Ecsec\Eidlogin\Service\EidFeAuthService::class, [
            'title' => 'eID based authentication Service for fe_users',
            'description' => 'eID based authentication Service for fe_users',
            'subtype' => 'getUserFE,authUserFE',
            'available' => true,
            'priority' => 40,
            'quality' => 40,
            'os' => '',
            'exec' => '',
            'className' => \Ecsec\Eidlogin\Service\EidFeAuthService::class
        ]);
    }
);
