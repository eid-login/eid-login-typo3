<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
namespace Ecsec\Eidlogin\Controller;

use Ecsec\Eidlogin\Domain\Model\Message;
use Ecsec\Eidlogin\Domain\Model\SiteInfo;
use Ecsec\Eidlogin\Service\EidService;
use Ecsec\Eidlogin\Service\MessageService;
use Ecsec\Eidlogin\Service\SettingsService;
use Ecsec\Eidlogin\Service\SiteInfoService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

// TODO 11
// use Psr\Http\Message\ResponseInterface;
// return $this->htmlResponse();

/**
 * Class FrontendController
 */
class FrontendController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const URL_FAQ = 'https://eid.services/eidlogin/typo3/userdocs';

    /** @var EidService */
    private $eidService;
    /** @var MessageService */
    private $messageService;
    /** @var SettingsService */
    private $settingsService;
    /** @var SiteInfoService */
    private $siteInfoService;
    /** @var ConfigurationManager */
    private $configManager;
    /** @var LocalizationUtility */
    private $l10nUtil;
    /** @var SiteInfo */
    private $siteInfo;
    /** @var AbstractFormProtection */
    private $formProtection;
    /** @var Context */
    private $context;

    /**
     * @param EidService $eidService
     * @param MessageService $messageService
     * @param SettingsService $settingsService
     * @param SiteInfoService $siteInfoService
     * @param ConfigurationManager $configManager
     * @param LocalizationUtility $l10nUtil
     * @param Context $context
     */
    public function __construct(
        EidService $eidService,
        MessageService $messageService,
        SettingsService $settingsService,
        SiteInfoService $siteInfoService,
        ConfigurationManager $configManager,
        LocalizationUtility $l10nUtil,
        Context $context
    ) {
        $this->eidService = $eidService;
        $this->messageService = $messageService;
        $this->settingsService = $settingsService;
        $this->siteInfoService = $siteInfoService;
        $this->configManager = $configManager;
        $this->l10nUtil = $l10nUtil;
        $this->formProtection = FormProtectionFactory::get();
        $this->context = $context;
    }

    /**
     * Initializes the controller before invoking an action method.
     */
    protected function initializeAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - initializeAction; enter');
        $this->siteInfo = $this->siteInfoService->getSiteInfoByPageId($GLOBALS['TSFE']->id);
    }

    /**
     * Show the eID Settings in the frontend
     */
    public function showSettingsAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - showSettingsAction');
        // check if user is logged in
        if (is_null($GLOBALS['TSFE']->fe_user->user['uid'])) {
            throw new \Exception('no fe_user logged in');
        }
        $this->processMessage();
        $uid = $GLOBALS['TSFE']->fe_user->user['uid'];
        $this->view->assign('activated', $this->settingsService->getActivated($this->siteInfo->getSite()->getRootPageId()));
        $this->view->assign('eid_present', $this->eidService->checkEid(EidService::TYPE_FE, (int)$uid));
        $this->view->assign('disable_pw_login', $this->settingsService->getDisablePwLogin(EidService::TYPE_FE, $uid));
        $this->view->assign('create_token', $this->formProtection->generateToken('FE_settings', 'createEid'));
        $this->view->assign('delete_token', $this->formProtection->generateToken('FE_settings', 'deleteEid'));
        $langId = $this->context->getAspect('language')->getId();
        $language = $this->siteInfo->getSite()->getLanguageById($langId);
        $langCode = $language->getTwoLetterIsoCode();
        $this->view->assign('url_faq', self::URL_FAQ . '?lang=' . $langCode);
        $this->uriBuilder->reset();
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $this->view->assign('url_redirect', $this->uriBuilder->buildFrontendUri());
        $this->uriBuilder->reset();
        $this->uriBuilder->setTargetPageUid($GLOBALS['TSFE']->id);
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $disablePwLoginUrl = $this->uriBuilder->uriFor('disablePwLogin', [], 'Frontend', 'eidlogin', 'Settings');
        $this->view->assign('url_disable_pw_login', $disablePwLoginUrl);
        $enablePwLoginUrl = $this->uriBuilder->uriFor('enablePwLogin', [], 'Frontend', 'eidlogin', 'Settings');
        $this->view->assign('url_enable_pw_login', $enablePwLoginUrl);
    }

    /**
     * The endpoint to trigger the flow for eID creation.
     */
    public function createEidAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - createEidAction');
        // check if user is logged in
        if (is_null($GLOBALS['TSFE']->fe_user->user['uid'])) {
            throw new \Exception('no fe_user logged in');
        }
        // check csrf token
        if (!$this->request->hasArgument('token')) {
            throw new \Exception('missing csrf token parameter');
        }
        if (!$this->formProtection->validateToken($this->request->getArgument('token'), 'FE_settings', 'createEid')) {
            throw new \Exception('invalid csrf token parameter');
        }
        // determine redirect url
        if (!$this->request->hasArgument('redirect')) {
            throw new \Exception('missing redirect url parameter');
        }
        $redirectUrl = $this->request->getArgument('redirect');
        // set the uid of the current fe_user
        $this->eidService->setUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $redirectUrl = $this->eidService->startEidFlow(
            EidService::TYPE_FE,
            EidService::FLOW_CREATE,
            $this->siteInfo,
            $redirectUrl
        );
        $this->logger->debug('eidlogin - FrontendController - createEidAction; redirect to ' . $redirectUrl);
        $this->redirectToURI($redirectUrl, $delay = 0, $statusCode = 307);
    }

    /**
     * Delete the eID and it's settings for the current user.
     */
    public function deleteEidAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - deleteEidAction');
        // check if user is logged in
        if (is_null($GLOBALS['TSFE']->fe_user->user['uid'])) {
            throw new \Exception('no fe_user logged in');
        }
        // check csrf token
        if (!$this->request->hasArgument('token')) {
            throw new \Exception('missing csrf token parameter');
        }
        if (!$this->formProtection->validateToken($this->request->getArgument('token'), 'FE_settings', 'deleteEid')) {
            throw new \Exception('invalid csrf token parameter');
        }
        // determine redirect url
        if (!$this->request->hasArgument('redirect')) {
            throw new \Exception('missing redirect url parameter');
        }
        $redirectUrl = $this->request->getArgument('redirect');
        // get locale for l10n
        $langId = $this->context->getAspect('language')->getId();
        $language = $this->siteInfo->getSite()->getLanguageById($langId);
        $langCode = $language->getTwoLetterIsoCode();
        // delete eid and add msgid to redirect
        $msgId = $this->eidService->deleteEid($GLOBALS['TSFE']->fe_user->user['uid'], $langCode);
        $redirectUrl = $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
        $this->logger->debug('eidlogin - FrontendController - deleteEidAction; redirect to ' . $redirectUrl);
        $this->redirectToURI($redirectUrl, $delay = 0, $statusCode = 307);
    }

    /**
     * Show the eID-Login in the frontend
     */
    public function showLoginAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - showLoginAction');
        $this->processMessage();
        $langId = $this->context->getAspect('language')->getId();
        $language = $this->siteInfo->getSite()->getLanguageById($langId);
        $langCode = $language->getTwoLetterIsoCode();
        $this->uriBuilder->reset();
        $this->uriBuilder->setLanguage((string)$langId);
        $this->uriBuilder->setTargetPageUid($GLOBALS['TSFE']->id);
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $this->view->assign('redirect_url', $this->uriBuilder->buildFrontendUri());
        $this->view->assign('url_faq', self::URL_FAQ . '?lang=' . $langCode);
        $this->view->assign('activated', $this->settingsService->getActivated($this->siteInfo->getSite()->getRootPageId()));
    }

    /**
     * Start the eID-Login in the frontend
     */
    public function startLoginAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - startLoginAction; enter');
        // determine redirect url
        if (!$this->request->hasArgument('redirect')) {
            throw new \Exception('missing redirect url parameter');
        }
        $redirectUrl = $this->request->getArgument('redirect');
        // only start flow if user is not logged in
        if (!$GLOBALS['TSFE']->fe_user->user instanceof FrontendUserAuthentication) {
            $this->logger->debug('eidlogin - FrontendController - startLoginAction; redirectUrl: ' . $redirectUrl);
            $redirectUrl = $this->eidService->startEidFlow(
                EidService::TYPE_FE,
                EidService::FLOW_LOGIN,
                $this->siteInfo,
                $redirectUrl
            );
        }
        $this->logger->debug('eidlogin - FrontendController - startLoginAction; redirect to ' . $redirectUrl);
        $this->redirectToURI($redirectUrl, $delay = 0, $statusCode = 307);
    }

    /**
     * Logout
     */
    public function logoutAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - logoutAction');
        // do the logout
        $_POST['logintype'] = 'logout';
        $GLOBALS['TSFE']->fe_user->start();
        $this->uriBuilder->reset();
        $this->uriBuilder->setLanguage((string)$this->context->getAspect('language')->getId());
        $this->uriBuilder->setTargetPageUid($GLOBALS['TSFE']->id);
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $redirectUrl = $this->uriBuilder->buildFrontendUri();
        if ($_GET[MessageService::PARAM_MSGID]!='') {
            $redirectUrl = preg_replace('/&cHash=.*$/', '', $redirectUrl);
            $redirectUrl .= '?' . MessageService::PARAM_MSGID . '=' . $_GET[MessageService::PARAM_MSGID];
        }
        $this->logger->debug('eidlogin - FrontendController - logoutAction; redirect to ' . $redirectUrl);
        $this->redirectToURI($redirectUrl, $delay = 0, $statusCode = 307);
    }

    /**
     * Disable the password based login for current user.
     */
    public function disablePwLoginAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - disablePwLogin; enter');
        // check if user is logged in
        if (is_null($GLOBALS['TSFE']->fe_user->user['uid'])) {
            throw new \Exception('no fe_user logged in');
        }
        $set = $this->settingsService->setDisablePwLogin(EidService::TYPE_FE, $GLOBALS['TSFE']->fe_user->user['uid'], 1);
        if ($set) {
            $msg = $this->l10nUtil->translate('fe_msg_scs_setting_saved', 'eidlogin');
            $this->addFlashMessage($msg, 'eID-Login', AbstractMessage::OK, true);
        } else {
            $msg = $this->l10nUtil->translate('fe_msg_err_setting_email_missing', 'eidlogin');
            $this->addFlashMessage($msg, 'eID-Login', AbstractMessage::ERROR, true);
        }
        $this->uriBuilder->reset();
        $this->uriBuilder->setLanguage((string)$this->context->getAspect('language')->getId());
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $redirectUrl = $this->uriBuilder->uriFor('showSettings', [], 'Frontend', 'eidlogin', 'Settings');
        $this->logger->debug('eidlogin - FrontendController - disablePwLoginAction; redirect to ' . $redirectUrl);
        $this->redirectToURI($redirectUrl, $delay = 0, $statusCode = 307);
    }

    /**
     * Enable the password based login for current user.
     */
    public function enablePwLoginAction(): void
    {
        $this->logger->debug('eidlogin - FrontendController - enablePwLogin; enter');
        // check if user is logged in
        if (is_null($GLOBALS['TSFE']->fe_user->user['uid'])) {
            throw new \Exception('no fe_user logged in');
        }
        $this->settingsService->setDisablePwLogin(EidService::TYPE_FE, $GLOBALS['TSFE']->fe_user->user['uid'], 0);
        $msg = $this->l10nUtil->translate('fe_msg_scs_setting_saved', 'eidlogin');
        $this->addFlashMessage($msg, 'eID-Login', AbstractMessage::OK, true);
        $this->uriBuilder->reset();
        $this->uriBuilder->setLanguage((string)$this->context->getAspect('language')->getId());
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $redirectUrl = $this->uriBuilder->uriFor('showSettings', [], 'Frontend', 'eidlogin', 'Settings');
        $this->redirectToURI($redirectUrl, $delay = 0, $statusCode = 307);
    }

    /**
     * If query param is set, read a message which may be set in the database.
     * Set it as flash message to get them rendered and delete the message.
     */
    private function processMessage(): void
    {
        if (!is_null($_REQUEST[MessageService::PARAM_MSGID])) {
            $msgId = $_REQUEST[MessageService::PARAM_MSGID];
            $msg = $this->messageService->getMessage($msgId);
            $this->logger->debug('eidlogin - FrontendController - processMessage; msg: ' . print_r($msg, true));
            if ($msg instanceof Message) {
                $this->addFlashMessage($msg->getValue(), 'eID-Login', $msg->getSeverity(), false);
                $this->messageService->deleteMessage($msg);
            }
        }
    }
}
