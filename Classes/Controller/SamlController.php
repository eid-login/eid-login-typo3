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

use Ecsec\Eidlogin\Domain\Model\SiteInfo;
use Ecsec\Eidlogin\Service\EidService;
use Ecsec\Eidlogin\Service\SamlService;
use Ecsec\Eidlogin\Service\SiteInfoService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

// TODO for TYPO3 11
// use Psr\Http\Message\ResponseInterface;
// return $this->htmlResponse();

/**
 * Class SamlController
 */
class SamlController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EidService */
    private $eidService;
    /** @var SamlService */
    private $samlService;
    /** @var SiteInfoService */
    private $siteInfoService;
    /** @var LocalizationUtility */
    private $l10nUtil;
    /** @var SiteInfo */
    private $siteInfo;
    /** @var string */
    private $locale;

    /**
     * @param EidService $eidService
     * @param SamlService $samlService
     * @param SiteInfoService $siteInfoService
     */
    public function __construct(
        EidService $eidService,
        SamlService $samlService,
        SiteInfoService $siteInfoService,
        LocalizationUtility $l10nUtil
    ) {
        $this->eidService = $eidService;
        $this->samlService = $samlService;
        $this->siteInfoService = $siteInfoService;
        $this->l10nUtil = $l10nUtil;
        $this->locale = 'en';
    }

    /**
     * Initializes the controller before invoking an action method.
     */
    protected function initializeAction(): void
    {
        $this->logger->debug('eidlogin - SamlController - initializeAction; enter');
        $this->siteInfo = $this->siteInfoService->getSiteInfoByPageId($GLOBALS['TSFE']->id);
        $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        if ($locale === 'de') {
            $this->locale = $locale;
        }
    }

    /**
     * The endpoint giving out SP saml metdata.
     */
    public function metaAction(): string
    {
        $this->logger->debug('eidlogin - SamlController - metaAction; enter');
        try {
            $meta = $this->samlService->getSpSamlMetadata(
                $this->siteInfo->getSite()->getRootPageId(),
                $this->siteInfo->getSamlPageId(),
                $this->siteInfo->getSite()->getDefaultLanguage()->getLanguageId()
            );
            // metadata may be fetched by another domain, when configuring in the wizard
            header('Access-Control-Allow-Origin: *', false);
            // yes, we deliver xml
            header('Content-Type: application/xml');
            return $meta;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->response->setStatus(404);

            return '';
        }
    }

    /**
     * This will return a redirect to the to the eID-Server configured.
     * It is supposed to lead an eID-Client to the eID-Server to fetch the TcToken.
     */
    public function tcTokenAction(): void
    {
        $this->logger->debug('eidlogin - SamlController - tcTokenAction; enter');
        if ($this->request->hasArgument('reqid')) {
            $reqid = $this->request->getArgument('reqid');
            $this->logger->debug('eidlogin - FrontendController - tcTokenAction; reqid: ' . $reqid);
            $reqid = urldecode($reqid);
            $redirectUrl = $this->eidService->createAuthnReqUrl($reqid, $this->siteInfo);
            $this->logger->debug('eidlogin - SamlController - tcTokenAction; redirect to ' . $redirectUrl);
            header('Location: ' . $redirectUrl);
            die();
        }
        throw new \Exception('missing reqid argument!');
    }

    /**
     * The endpoint acting as assertion consumer service for POST binding.
     */
    public function acsPostAction(): void
    {
        $this->logger->debug('eidlogin - SamlController - acsPostAction; enter');
        $redirectUrl = $this->eidService->processSamlResponse($this->siteInfo, $this->locale);
        $this->logger->debug('eidlogin - SamlController - acsPostAction; redirect to ' . $redirectUrl);
        header('Location: ' . $redirectUrl);
        die();
    }

    /**
     * The endpoint acting as assertion consumer service for Redirect Binding as used with TR-03130
     */
    public function acsRedirectAction(): void
    {
        $this->logger->debug('eidlogin - SamlController - acsRedirectAction; enter');
        $redirectUrl = $this->eidService->processSamlResponse($this->siteInfo, $this->locale);
        $this->logger->debug('eidlogin - SamlController - acsRedirectAction; redirect to ' . $redirectUrl);
        header('Location: ' . $redirectUrl);
        die();
    }

    /**
     * This action should resume after an TR-03130 SAML Flow.
     * The SAML Response must has been delivered by an TR-03130 eID-Client before.
     */
    public function resumeAction(): void
    {
        $this->logger->debug('eidlogin - SamlController - resumeAction; enter');
        $redirectUrl = '/';
        if ($this->request->hasArgument('rspid')) {
            $redirectUrl = $this->eidService->processSamlResponseData($this->request->getArgument('rspid'), null, $this->locale);
        }
        $this->logger->debug('eidlogin - SamlController - resumeAction; redirect to ' . $redirectUrl);
        header('Location: ' . $redirectUrl);
        die();
    }
}
