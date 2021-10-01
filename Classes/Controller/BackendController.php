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

use Ecsec\Eidlogin\Domain\Repository\SchedulerTaskRepository;
use Ecsec\Eidlogin\Service\EidService;
use Ecsec\Eidlogin\Service\SamlService;
use Ecsec\Eidlogin\Service\SettingsService;
use Ecsec\Eidlogin\Service\SiteInfoService;
use Ecsec\Eidlogin\Service\SslService;
use Ecsec\Eidlogin\Util\Typo3VersionUtil;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class BackendController
 */
class BackendController extends ActionController implements \Psr\Log\LoggerAwareInterface
{
    use LoggerAwareTrait;

    // change defaultViewObject results in an TYPO3 style be page
    /** @var string */
    protected $defaultViewObjectName = BackendTemplateView::class;
    /** @var SchedulerTaskRepository */
    private $schedulerTaskRepo;
    /** @var EidService */
    private $eidService;
    /** @var SamlService */
    private $samlService;
    /** @var SettingsService */
    private $settingsService;
    /** @var SiteInfoService */
    private $siteInfoService;
    /** @var SslService */
    private $sslService;
    /** @var LocalizationUtility */
    private $l10nUtil;

    /**
     * @param SchedulerTaskRepository $schedulerTaskRepo
     * @param EidService $eidService
     * @param SamlService $samlService
     * @param SettingsService $settingsService
     * @param SiteInfoService $siteInfoService
     * @param SslService $sslService
     * @param LocalizationUtility $l10nUtil
     */
    public function __construct(
        SchedulerTaskRepository $schedulerTaskRepo,
        EidService $eidService,
        SamlService $samlService,
        SettingsService $settingsService,
        SiteInfoService $siteInfoService,
        SslService $sslService,
        LocalizationUtility $l10nUtil
    ) {
        $this->schedulerTaskRepo = $schedulerTaskRepo;
        $this->eidService = $eidService;
        $this->samlService = $samlService;
        $this->settingsService = $settingsService;
        $this->siteInfoService = $siteInfoService;
        $this->sslService = $sslService;
        $this->l10nUtil = $l10nUtil;
    }

    /**
     * Add site selector to DocHeader.
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view): void
    {
        $this->logger->debug('eidlogin - BackendController - initializeView; enter');
        parent::initializeView($view);
        // no module template, no need for setup
        $moduleTemplate = $this->view->getModuleTemplate();
        if (is_null($moduleTemplate)) {
            return;
        }
        //no configured sites, no need for setup
        if (!$this->siteInfoService->configuredSitePresent()) {
            return;
        }
        $menuRegistry = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $menu = $menuRegistry->makeMenu();
        $menu->setIdentifier('EidLoginSiteSelector');
        // Default item to show in the menu
        $menuItem = $menu->makeMenuItem()
            ->setTitle($this->l10nUtil->translate('be_select_site', 'eidlogin'))
            ->setHref($this->uriBuilder->reset()->uriFor('index', [], 'Backend'))
            ->setActive(false);
        if ($this->actionMethodName == 'indexAction') {
            $menuItem->setActive(true);
        }
        $menu->addMenuItem($menuItem);
        $siteInfos = $this->siteInfoService->getSiteInfos();
        foreach ($siteInfos as $siteInfo) {
            // only add sites which are setup  to the menu
            if (!$siteInfo->getSetupLogin() ||
                !$siteInfo->getSetupSettings() ||
                count($siteInfo->getUserPageIds())<=0 ||
                is_null($siteInfo->getSamlPageId())
            ) {
                continue;
            }
            $site = $siteInfo->getSite();
            $args = [
                'siteIdentifier' => urlencode($site->getIdentifier()),
                'siteRootPageId' => $siteInfo->getSite()->getRootPageId(),
                'samlPageId' => $siteInfo->getSamlPageId()
            ];
            $href = $this->uriBuilder->reset()->uriFor('showSettings', $args, 'Backend');
            $menuItem = $menu->makeMenuItem()
                ->setTitle($site->getIdentifier())
                ->setHref($href)
                ->setActive(false);
            if ($this->actionMethodName == 'showSettingsAction') {
                if (!$this->request->hasArgument('siteIdentifier')) {
                    throw new \Exception('missing siteIdentifier parameter');
                }
                if ($this->request->getArgument('siteIdentifier')===$site->getIdentifier()) {
                    $menuItem->setActive(true);
                }
            }
            $menu->addMenuItem($menuItem);
        }
        // add menu only if it contains site related items
        if (count($menu->getMenuItems())>1) {
            $menuRegistry->addMenu($menu);
        }
    }

    /**
     * Initializes the controller before invoking an action method.
     */
    protected function initializeAction(): void
    {
        $this->logger->debug('eidlogin - BackendController - initializeAction; enter');
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            throw new \Exception('BackendController is only accessible for admin users');
        }
    }

    /**
     * Default action to show in the backend module
     */
    public function indexAction(): void
    {
        $this->logger->debug('eidlogin - BackendController - indexAction; enter');
        // check if defaultMailFromAddress is present
        $defaultMailFromAddressPresent = strlen($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']) != 0 ? 1 : 0;
        $this->view->assign('default_mail_from_address_present', $defaultMailFromAddressPresent);
        // check if needed tasks are present
        $cleanDbTaskPresent = $this->schedulerTaskRepo->checkForCommandTask('eidlogin:cleandb');
        $certificateTaskPresent = $this->schedulerTaskRepo->checkForCommandTask('eidlogin:certificate');
        $tasksPresent = false;
        if ($cleanDbTaskPresent && $certificateTaskPresent) {
            $tasksPresent = true;
        }
        $this->view->assign('tasks_present', $tasksPresent);
        // get siteinfos
        $siteInfos = $this->siteInfoService->getSiteInfos();
        $this->view->assign('site_infos', $siteInfos);
        $siteLinks = [];
        foreach ($siteInfos as $siteInfo) {
            $site = $siteInfo->getSite();
            $args = [
                'siteIdentifier' => urlencode($site->getIdentifier()),
                'siteRootPageId' => $siteInfo->getSite()->getRootPageId(),
                'samlPageId' => $siteInfo->getSamlPageId()
            ];
            $siteLinks[$siteInfo->getSite()->getRootPageId()] = $this->uriBuilder->reset()->uriFor('showSettings', $args, 'Backend');
        }
        $this->view->assign('site_links', $siteLinks);
        // get language
        $lang = $GLOBALS['BE_USER']->uc['lang'];
        // skid url
        if ($lang === 'de') {
            $this->view->assign('skid_url', 'https://skidentity.de');
        } else {
            $this->view->assign('skid_url', 'https://skidentity.com');
        }
    }

    /**
     * Show the eID-Login settings as rendered gui for a specific site
     */
    public function showSettingsAction(): void
    {
        $this->logger->debug('eidlogin - BackendController - showSettingsAction; enter');
        // arguments
        if (!$this->request->hasArgument('siteIdentifier')) {
            throw new \Exception('missing siteIdentifier parameter');
        }
        $siteIdentifier = $this->request->getArgument('siteIdentifier');
        $this->view->assign('site_identifier', $siteIdentifier);
        if (!$this->request->hasArgument('siteRootPageId')) {
            throw new \Exception('missing siteRootPageId parameter');
        }
        $siteRootPageId = (int)($this->request->getArgument('siteRootPageId'));
        $this->view->assign('site_root_page_id', $siteRootPageId);
        if (!$this->request->hasArgument('samlPageId')) {
            throw new \Exception('missing samlPageId parameter');
        }
        $samlPageId = (int)($this->request->getArgument('samlPageId'));
        $this->view->assign('saml_page_id', $samlPageId);
        // certificate
        try {
            $actDates = $this->sslService->getActDates($siteRootPageId);
            $now = new \DateTimeImmutable();
            $remainingVaildIntervall = $actDates[SslService::DATES_VALID_TO]->diff($now);
            $validDays = $remainingVaildIntervall->days;
            $this->view->assign('act-cert_validdays', $validDays);
            $actCertPresent = $this->sslService->checkActCertPresent($siteRootPageId);
            $this->view->assign('act_cert_present', $actCertPresent);
            $this->view->assign('act-cert', $this->sslService->getCertAct($siteRootPageId, true));
            $this->view->assign('act-cert-enc', $this->sslService->getCertActEnc($siteRootPageId, true));
            $newCertPresent = $this->sslService->checkNewCertPresent($siteRootPageId);
            $this->view->assign('new_cert_present', $newCertPresent);
            if ($newCertPresent) {
                $this->view->assign('new-cert', $this->sslService->getCertNew($siteRootPageId, true));
                $this->view->assign('new-cert-enc', $this->sslService->getCertNewEnc($siteRootPageId, true));
            }
        } catch (\Exception $e) {
        }
        // settings
        $settingsPresent = $this->settingsService->settingsPresent($siteRootPageId);
        $this->view->assign('settings_present', $settingsPresent);
        $settings = [];
        if ($settingsPresent) {
            $settings = $this->settingsService->getSettings($siteRootPageId);
        }
        $this->view->assign('settings', $settings);
        // setup urlBuilder
        $site = $this->siteInfoService->getSiteInfoByPageId($siteRootPageId)->getSite();
        $this->uriBuilder->reset();
        $this->uriBuilder->setTargetPageUid($samlPageId);
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $this->uriBuilder->setLanguage((string)$site->getDefaultLanguage()->getLanguageId());
        // acs urls
        $this->uriBuilder->uriFor('acsRedirect', [], 'Saml', 'eidlogin', 'Saml');
        $spAcsRedirectUrl = $this->uriBuilder->buildFrontendUri();
        $spAcsRedirectUrl = preg_replace('/&cHash=.*$/', '', $spAcsRedirectUrl);
        $this->view->assign('sp_acs_redirect_url', $spAcsRedirectUrl);
        $this->uriBuilder->uriFor('acsPost', [], 'Saml', 'eidlogin', 'Saml');
        $spAcsPostUrl = $this->uriBuilder->buildFrontendUri();
        $spAcsPostUrl = preg_replace('/&cHash=.*$/', '', $spAcsPostUrl);
        $this->view->assign('sp_acs_post_url', $spAcsPostUrl);
        // metadata url
        $this->uriBuilder->uriFor('meta', [], 'Saml', 'eidlogin', 'Saml');
        $spMetaUrl = $this->uriBuilder->buildFrontendUri();
        $spMetaUrl = preg_replace('/&cHash=.*$/', '', $spMetaUrl);
        $this->view->assign('sp_meta_url', $spMetaUrl);
        // base url without trailing slash
        $baseUrl = (string)$site->getBase();
        $baseUrl = rtrim($baseUrl, '/');
        $this->view->assign('base_url', $baseUrl);
        // get language
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $lang = $GLOBALS['BE_USER']->uc['lang'];
        // skid url
        if ($lang === 'de') {
            $this->view->assign('skid_url', 'https://skidentity.de');
        } else {
            $this->view->assign('skid_url', 'https://skidentity.com');
        }
        // language must be set after everything else
        $langStr = $lang;
        if (strlen($lang)!=0) {
            $langStr .= '.';
        }
        $inlineLanguageLabelFile = 'EXT:eidlogin/Resources/Private/Language/' . $langStr . 'locallang.xlf';
        $pageRenderer->addInlineLanguageLabelFile($inlineLanguageLabelFile, 'be_js_');
        $pageRenderer->setLanguage($lang);
    }

    /**
     * Fetch the metadata from a given metadata URL
     */
    public function fetchIdpMetaAction()
    {
        $this->logger->debug('eidlogin - BackendController - showSettingsAction; enter');
        $status = 422;
        $data = [
            'status' => 'error',
        ];
        if ($this->request->hasArgument('url')) {
            $url = $this->request->getArgument('url');
            $url = urldecode(base64_decode($url));
            try {
                $idpMetadata = $this->samlService->fetchIdpSamlMetadata($url);
                $data = [
                    'status' => 'success',
                    'idp_cert_enc' => $idpMetadata['idp_cert_enc'],
                    'idp_cert_sign' => $idpMetadata['idp_cert_sign'],
                    'idp_entity_id' => $idpMetadata['idp_entity_id'],
                    'idp_sso_url' => $idpMetadata['idp_sso_url'],
                ];
                $status = 200;
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        return $this->buildJsonResponse($data, $status);
    }

    /**
     * Save the eID-Login settings
     */
    public function saveSettingsAction()
    {
        $this->logger->debug('eidlogin - BackendController - saveSettingsAction; enter');
        $siteRootPageId = $_POST['site_root_page_id'];
        if (is_null($siteRootPageId)) {
            throw new \Exception('misssing parameter site_root_page_id');
        }
        $errors = $this->settingsService->saveSettings(
            (int)$siteRootPageId,
            $_POST['idp_cert_enc'],
            $_POST['idp_cert_sign'],
            $_POST['idp_entity_id'],
            $_POST['idp_ext_tr03130'],
            $_POST['idp_sso_url'],
            $_POST['sp_enforce_enc'],
            $_POST['sp_entity_id']
        );
        if (count($errors)>0) {
            $data = [
                'status'=>'error',
                'errors'=> $errors
            ];
        } else {
            $msg = $this->l10nUtil->translate('be_msg_scs_settings_saved', 'eidlogin');
            // delete existing eID-Connections if requested
            if ('true'===$_POST['eid_delete']) {
                $this->logger->info('deleting ids');
                $siteInfo = $this->siteInfoService->getSiteInfoByPageId($siteRootPageId);
                $this->eidService->deleteEids($siteInfo);
                $msg .= $this->l10nUtil->translate('be_msg_scs_eids_deleted', 'eidlogin');
            }
            // setup ssl stuff for saml sign and encrypt if needed
            if (!$this->sslService->checkActCertPresent($siteRootPageId)) {
                $this->logger->info('creating certificates');
                $this->sslService->createNewCert($siteRootPageId);
            }
            $data = [
                'status' => 'success',
                'message' => $msg
            ];
        }

        return $this->buildJsonResponse($data);
    }

    /**
     * Toogle the activated state of the eID-Login
     */
    public function toggleActivatedAction()
    {
        $this->logger->debug('eidlogin - BackendController - toggleActivatedAction; enter');
        if (is_null($_GET['site_root_page_id'])) {
            throw new \Exception('misssing parameter site_root_page_id');
        }
        $siteRootPageId = $_GET['site_root_page_id'];
        $this->settingsService->toggleActivated($siteRootPageId);
        $msg = $this->l10nUtil->translate('be_msg_scs_deactivated', 'eidlogin');
        if ($this->settingsService->getActivated($siteRootPageId)=='1') {
            $msg = $this->l10nUtil->translate('be_msg_scs_activated', 'eidlogin');
        }
        $data = [
            'status' => 'success',
            'message' => $msg
        ];

        return $this->buildJsonResponse($data);
    }

    /**
     * Reset the settings
     */
    public function resetSettingsAction()
    {
        $this->logger->debug('eidlogin - BackendController - resetSettingsAction; enter');
        if (is_null($_GET['site_root_page_id'])) {
            throw new \Exception('misssing parameter site_root_page_id');
        }
        $siteRootPageId = $_GET['site_root_page_id'];
        $this->settingsService->deleteSettings($siteRootPageId);
        $siteInfo = $this->siteInfoService->getSiteInfoByPageId($siteRootPageId);
        $this->eidService->deleteEids($siteInfo);
        $msg = $this->l10nUtil->translate('be_msg_scs_settings_reset', 'eidlogin');
        $data = [
            'status' => 'success',
            'message' => $msg
        ];

        return $this->buildJsonResponse($data);
    }

    /**
    * Prepare a SAML certificate rollover.
    */
    public function prepareRolloverAction()
    {
        if (is_null($_GET['site_root_page_id'])) {
            throw new \Exception('misssing parameter site_root_page_id');
        }
        $siteRootPageId = $_GET['site_root_page_id'];
        $data = [];
        try {
            $this->sslService->createNewCert($siteRootPageId);
            $newCert = $this->sslService->getCertNew($siteRootPageId, true);
            $newCertEnc = $this->sslService->getCertNewEnc($siteRootPageId, true);
            $data = [
                'status' => 'success',
                'cert_new' => $newCert,
                'cert_new_enc' => $newCertEnc,
                'message' => $this->l10nUtil->translate('be_js_msg_scs_preprollover', 'eidlogin')
            ];
        } catch (\Exception $e) {
            $msg = $this->l10nUtil->translate('be_js_msg_err_preprollover', 'eidlogin');
            $msg .= ', ' . $e->getMessage();
            $this->logger->error($msg);
            $data = [
                'status' => 'error',
            ];
        }

        return $this->buildJsonResponse($data);
    }

    /**
     * Execute a SAML certificate rollover.
     */
    public function executeRolloverAction()
    {
        if (is_null($_GET['site_root_page_id'])) {
            throw new \Exception('misssing parameter site_root_page_id');
        }
        $siteRootPageId = $_GET['site_root_page_id'];
        $data = [];
        try {
            $this->sslService->rollover($siteRootPageId);
            $actCert = $this->sslService->getCertAct($siteRootPageId, true);
            $actCertEnc = $this->sslService->getCertActEnc($siteRootPageId, true);
            $data = [
                'status' => 'success',
                'cert_act' => $actCert,
                'cert_act_enc' => $actCertEnc,
                'message' => $this->l10nUtil->translate('be_js_msg_scs_execrollover', 'eidlogin')
            ];
        } catch (\Exception $e) {
            $msg = $this->l10nUtil->translate('be_js_msg_err_execrollover', 'eidlogin');
            $msg .= ', ' . $e->getMessage();
            $this->logger->error($msg);
            $data = [
                'status' => 'error',
            ];
        }

        return $this->buildJsonResponse($data);
    }

    /**
     * Logic to build a json response
     *
     * @param string[] $data The data to return as json
     * @param int $status The status to set for the response, 200 is default
     * @return mixed $response mixed to support TYPO3 10 and 11
     * @throws \Exception If json conversion fails
     */
    private function buildJsonResponse(array $data=null, int $status=200)
    {
        $json = json_encode($data);
        if (!$json) {
            throw new \Exception('json_encode failed');
        }
        if (Typo3VersionUtil::isVersion10()) {
            $this->response->setHeader('Content-Type', 'application/json');
            $this->response->setStatus($status);

            return $json;
        }
        $response = $this->responseFactory
                ->createResponse()
                ->withStatus($status)
                ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write($json);

        return $response;
    }
}
