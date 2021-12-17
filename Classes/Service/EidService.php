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
namespace Ecsec\Eidlogin\Service;

use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Auth;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Utils;
use Ecsec\Eidlogin\Domain\Model\Attribute;
use Ecsec\Eidlogin\Domain\Model\ContinueData;
use Ecsec\Eidlogin\Domain\Model\Eid;
use Ecsec\Eidlogin\Domain\Model\ResponseData;
use Ecsec\Eidlogin\Domain\Model\SiteInfo;
use Ecsec\Eidlogin\Domain\Repository\AttributeRepository;
use Ecsec\Eidlogin\Domain\Repository\ContinueDataRepository;
use Ecsec\Eidlogin\Domain\Repository\EidRepository;
use Ecsec\Eidlogin\Domain\Repository\FrontendUserRepository;
use Ecsec\Eidlogin\Domain\Repository\ResponseDataRepository;
use Ecsec\Eidlogin\Util\Typo3VersionUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class EidService
 */
class EidService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var MessageService */
    private $messageService;
    /** @var SamlService */
    private $samlService;
    /** @var SettingsService */
    private $settingsService;
    /** @var AttributeRepository */
    private $attributeRepository;
    /** @var EidRepository */
    private $eidRepository;
    /** @var FrontendUserRepository */
    private $frontendUserRepository;
    /** @var ContinueDataRepository */
    private $continueDataRepository;
    /** @var ResponseDataRepository */
    private $responseDataRepository;
    /** @var PersistenceManager */
    private $persistenceManager;
    /** @var LocalizationUtility */
    private $l10nUtil;
    /** @var UriBuilder */
    private $uriBuilder;
    /** @var FrontendUserAuthentication */
    private $feUserAuth;
    /** @var ?int */
    private $uid;

    /** @var string */
    public const COOKIE_SAML = 'TYPO3_eidlogin_saml';
    /** @var string */
    public const KEY_COOKIE = 'TYPO3_eidlogin_cookie';
    /** @var string */
    public const KEY_NAMEID = 'eidlogin_nameid';
    /** @var string */
    public const KEY_FLOW = 'eidlogin_flow';
    /** @var string */
    public const KEY_REDIRECT = 'eidlogin_redirect';
    /** @var string */
    public const KEY_TYPE = 'eidlogin_type';
    /** @var string */
    public const KEY_UID = 'eidlogin_uid';
    /** @var string */
    public const KEY_USER_PIDS = 'eidlogin_user_pids';
    /** @var string */
    public const KEY_NO_PW_LOGIN = 'eidlogin_no_pw_login';
    /** @var string */
    public const FLOW_CREATE = 'createeid';
    /** @var string */
    public const FLOW_LOGIN = 'logineid';
    /** @var string */
    public const TYPE_FE = 'fe';
    /** @var string */
    public const TYPE_BE = 'be';

    /**
     * @param MessageService $messageService
     * @param SamlService $samlService
     * @param SettingsService $settingsService
     * @param AttributeRepository $attributeRepository
     * @param EidRepository $eidRepository
     * @param FrontendUserRepository $frontendUserRepository
     * @param ContinueDataRepository $continueDataRepository
     * @param ResponseDataRepository $responseDataRepository
     * @param PersistenceManager $persistenceManager
     * @param LocalizationUtility $l10nUtil
     * @param UriBuilder $uriBuilder
     * @param FrontendUserAuthentication $feUserAuth
     */
    public function __construct(
        MessageService $messageService,
        SamlService $samlService,
        SettingsService $settingsService,
        AttributeRepository $attributeRepository,
        EidRepository $eidRepository,
        FrontendUserRepository $frontendUserRepository,
        ContinueDataRepository $continueDataRepository,
        ResponseDataRepository $responseDataRepository,
        PersistenceManager $persistenceManager,
        LocalizationUtility $l10nUtil,
        UriBuilder $uriBuilder,
        FrontendUserAuthentication $feUserAuth
    ) {
        $this->messageService = $messageService;
        $this->samlService = $samlService;
        $this->settingsService = $settingsService;
        $this->attributeRepository = $attributeRepository;
        $this->eidRepository = $eidRepository;
        $this->frontendUserRepository = $frontendUserRepository;
        $this->continueDataRepository = $continueDataRepository;
        $this->responseDataRepository = $responseDataRepository;
        $this->persistenceManager = $persistenceManager;
        $this->l10nUtil = $l10nUtil;
        $this->uriBuilder = $uriBuilder;
        $this->feUserAuth = $feUserAuth;
        $this->uid = null;
    }

    /**
     * Set the users uid
     *
     * @param int $uid
     */
    public function setUid(int $uid=null): void
    {
        $this->uid = $uid;
    }

    /**
     * Get the users uid
     *
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Test if the user has an eid already.
     *
     * @param string $type The type of user to check
     * @param ?int $uid The uid of the user, for which to test. If null is given the current user is used.
     *
     * @throws \Exception If the user to work which could not be determined
     * @return bool True if an eid exists
     */
    public function checkEid(string $type=self::TYPE_FE, int $uid=null): bool
    {
        if ($type !== self::TYPE_FE && $type !== self::TYPE_BE) {
            throw new \Exception('invalid type');
        }
        if (is_null($uid)) {
            if (is_null($this->uid)) {
                throw new \Exception('$uid of EidService is null, could not determine user for which to checkEid!');
            }
            $uid = $this->uid;
        }
        if ($type===self::TYPE_FE) {
            $count = $this->eidRepository->countByFeuid($uid);
            return (int)$count === 1;
        }
        $count = $this->eidRepository->countByBeuid($uid);

        return (int)$count === 1;
    }

    /**
     * Start a eID Flow
     *
     * @param string $type the desired type of action, fe or be
     * @param string $flow the flow we are starting
     * @param string $redirectUrl the url to redirect to after the flow
     * @param SiteInfo $siteInfo The siteInfo of the current site, only needed for TYPE_FE (optional)
     *
     * @return string $url The url to go next
     *
     * @throws \Exception If invalid params are given
     */
    public function startEidFlow(
        string $type='',
        string $flow='',
        string $redirectUrl='/',
        SiteInfo $siteInfo
    ) {
        if (!$this->settingsService->getActivated($siteInfo->getSite()->getRootPageId())) {
            throw new \Exception('eID-Login not active');
        }
        if ($type !== self::TYPE_FE && $type !== self::TYPE_BE) {
            throw new \Exception('invalid type');
        }
        if ($flow !== self::FLOW_CREATE && $flow !== self::FLOW_LOGIN) {
            throw new \Exception('invalid flow');
        }
        if ($type == self::TYPE_FE && is_null($siteInfo)) {
            throw new \Exception('type ' . self::TYPE_FE . ' needs $siteInfo to be set');
        }
        if ($redirectUrl == '') {
            throw new \Exception('empty redirectUrl');
        }
        if (is_null($this->uid) && $flow === self::FLOW_CREATE) {
            throw new \Exception('$uid of EidService is null, could not determine user for which to start create SAML Flow!');
        }
        // the requestId
        $reqId = 'eidlogin_' . bin2hex(random_bytes(12));
        // the cookieId
        $cookieId = 'eidlogin_' . bin2hex(random_bytes(12));
        setcookie(self::COOKIE_SAML, $cookieId, [
            'expires' => time()+60*5,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        // data we need for continue when returning
        $continue = [
            self::KEY_TYPE => $type,
            self::KEY_FLOW => $flow,
            self::KEY_UID => $this->uid,
            self::KEY_USER_PIDS => is_null($siteInfo) ? null : $siteInfo->getUserPageIds(),
            self::KEY_COOKIE => $cookieId,
            self::KEY_REDIRECT => $redirectUrl
        ];
        // save continue data
        $continueData = new ContinueData();
        $continueData->setReqid($reqId);
        $continueData->setValue(json_encode($continue));
        $continueData->setTime(time());
        $this->persistenceManager->add($continueData);
        $this->persistenceManager->persistAll();
        // create url for redirect
        $url = null;
        // if we have an TR-03130 flow we need another redirect step,
        // to let the eID-Client fetch the tc token from us via redirect to the eID-Server
        if ($this->samlService->checkForTr03130($siteInfo->getSite()->getRootPageId())) {
            $this->uriBuilder->reset();
            $args = ['reqid' => urlencode($reqId)];
            $this->uriBuilder->setLanguage((string)$siteInfo->getSite()->getDefaultLanguage()->getLanguageId());
            $this->uriBuilder->setTargetPageUid($siteInfo->getSamlPageId());
            $this->uriBuilder->setCreateAbsoluteUri(true);
            $url = $this->uriBuilder->uriFor('tcToken', $args, 'Saml', 'eidlogin', 'Saml');
            $url = preg_replace('/&cHash=.*$/', '', $url);
            $url = 'http://127.0.0.1:24727/eID-Client?tcTokenURL=' . urlencode($url);
        } else {
            $url = $this->createAuthnReqUrl($reqId, $siteInfo);
        }

        return $url;
    }

    /**
     * Create an url from an SAML authnRequest
     *
     * @param string $id The id for the authnRequest
     * @param SiteInfo $siteInfo The siteInfo of the current site
     *
     * @return string|null The url
     */
    public function createAuthnReqUrl(string $id, SiteInfo $siteInfo): ?string
    {
        $settings = $this->samlService->getSamlSettings(
            $siteInfo->getSite()->getRootPageId(),
            $siteInfo->getSamlPageId(),
            $siteInfo->getSite()->getDefaultLanguage()->getLanguageId()
        );
        $auth = new Auth($settings);
        $url = $auth->login(null, [], false, false, true, true, null, $id);
        if (!is_null($url)) {
            $this->logger->debug($auth->getLastRequestXML());
        } else {
            throw new \Exception('auth->login returned null as return value!');
        }

        return $url;
    }

    /**
     * Process the Saml response coming with the request.
     *
     * @param SiteInfo $siteInfo The siteInfo of the current site
     * @param string $locale The locale to use for l10n
     *
     * @return string The redirect where to go next
     */
    public function processSamlResponse(SiteInfo $siteInfo, string $locale='en')
    {
        // defaults
        $samlSettings = $this->samlService->getSamlSettings(
            $siteInfo->getSite()->getRootPageId(),
            $siteInfo->getSamlPageId(),
            $siteInfo->getSite()->getDefaultLanguage()->getLanguageId()
        );
        $redirectUrl = $siteInfo->getSite()->getBase()->__toString();
        $response = null;
        $responseAsXML = null;
        $continueData = null;
        // setup SAML Toolkit
        $auth = new Auth($samlSettings);
        try {
            // create response, check InResponseTo
            $response = $auth->createResponse();
            $responseAsXML = $response->getXMLDocument();
            $inResponseTo = null;
            if ($responseAsXML->documentElement->hasAttribute('InResponseTo')) {
                $inResponseTo = $responseAsXML->documentElement->getAttribute('InResponseTo');
            } else {
                throw new \Exception('missing inResponseTo Attribute in SAML Response');
            }
            // check for valid algorithms
            if ($this->samlService->checkForTr03130($siteInfo->getSite()->getRootPageId())) {
                $responseAsXMLenc = $response->getXMLDocument(true);
                $encMethodList = Utils::query($responseAsXMLenc, '/samlp:Response/saml:EncryptedAssertion/xenc:EncryptedData/xenc:EncryptionMethod');
                // check we have the DOMElement methods avail
                foreach ($encMethodList as $encMethod) {
                    if (!method_exists($encMethod, 'hasAttribute')) {
                        throw new \Exception('Missing hasAttribute Method on object' . print_r($encMethod, true));
                    }
                    if (!method_exists($encMethod, 'getAttribute')) {
                        throw new \Exception('Missing getAttribute Method on object' . print_r($encMethod, true));
                    }
                }
                if (count($encMethodList)!=1) {
                    throw new \Exception('Expected one EncryptionMethod Node as child of EncryptedData but found ' . count($encMethodList));
                }
                if (!$encMethodList[0]->hasAttribute('Algorithm')) {
                    throw new \Exception('Found a EncryptionMethod Node for EncryptedData but missing Algorithm Attribute');
                }
                if (!in_array($encMethodList[0]->getAttribute('Algorithm'), $samlSettings['alg']['encryption']['data'])) {
                    throw new \Exception('Found a EncryptionMethod Node for Encrypted Data with invalid Algorithm Attribute: ' . $encMethodList[0]->getAttribute('Algorithm'));
                }
                $encMethodList = Utils::query($responseAsXMLenc, '/samlp:Response/saml:EncryptedAssertion/xenc:EncryptedData/ds:KeyInfo/xenc:EncryptedKey/xenc:EncryptionMethod');
                if (count($encMethodList)!=1) {
                    throw new \Exception('Expected one EncryptionMethod Node as child of EncryptedKey but found ' . count($encMethodList));
                }
                if (!$encMethodList[0]->hasAttribute('Algorithm')) {
                    throw new \Exception('Found a EncryptionMethod Node for EncryptedKey but missing Algorithm Attribute');
                }
                if (!in_array($encMethodList[0]->getAttribute('Algorithm'), $samlSettings['alg']['encryption']['key'])) {
                    throw new \Exception('Found a EncryptionMethod Node for EncryptedKey with invalid Algorithm Attribute: ' . $encMethodList[0]->getAttribute('Algorithm'));
                }
            } else {
                $responseAsXMLenc = $response->getXMLDocument();
                $signMethodList = Utils::query($responseAsXMLenc, '/samlp:Response/saml:Assertion/ds:Signature/ds:SignedInfo/ds:SignatureMethod');
                if (count($signMethodList) === 1) {
                    if (!$signMethodList[0]->hasAttribute('Algorithm')) {
                        throw new \Exception('Found a SignatureMethodNode but missing Algorithm Attribute');
                    }
                    if (!in_array($signMethodList[0]->getAttribute('Algorithm'), $samlSettings['alg']['signing'])) {
                        throw new \Exception('Found a SignatureMethodNode with invalid Algorithm Attribute: ' . $signMethodList[0]->getAttribute('Algorithm'));
                    }
                } elseif (count($signMethodList) > 1) {
                    throw new \Exception('Expected max one SignatureMethod Node but found ' . count($signMethodList));
                }
            }
            // load continue data and delete it from db
            $continueData = $this->continueDataRepository->findByReqid($inResponseTo);
            $this->continueDataRepository->remove($continueData);
            $this->persistenceManager->persistAll();
            // check the continue data is not older than 5 min
            $time = $continueData->getTime();
            $limit = time()-300;
            if ($time < $limit) {
                throw new \Exception('eid continue data found for inResponseTo: ' . $inResponseTo . ' is expired');
            }
        } catch (\Exception $e) {
            $this->logger->error('error creating SAML Response for user ' . $this->uid . ': ' . $e->getMessage());

            return $redirectUrl;
        }
        // process the response and gather it`s data
        $errors = [];
        $eid = null;
        $attributesAsXML = [];
        try {
            $auth->processCreatedResponse($response);
            // fetch errors
            $errors = $auth->getErrors();
            if (count($errors) === 0) {
                // fetch eid
                if ($this->samlService->checkForTr03130($siteInfo->getSite()->getRootPageId())) {
                    // we must verify the external signature
                    if (!array_key_exists('SigAlg', $_GET)) {
                        throw new \Exception('Missing SigAlg param');
                    }
                    if (!in_array(GeneralUtility::_GET('SigAlg'), $samlSettings['alg']['signing'])) {
                        throw new \Exception('Invalid SigAlg param ' . filter_var($_REQUEST['SigAlg'], FILTER_SANITIZE_SPECIAL_CHARS));
                    }
                    Utils::validateBinarySign('SAMLResponse', $_GET, $samlSettings['idp']);
                    $attributes = $response->getAttributes();
                    if (array_key_exists('RestrictedID', $attributes) && count($attributes['RestrictedID'])===1) {
                        $eid = $attributes['RestrictedID'][0];
                    }
                } else {
                    $eid = $response->getNameId();
                }
                // fetch attributes
                $attributesAsXML = $response->getAttributesAsXML();
            }
        } catch (\Exception $e) {
            $this->logger->info('error processing SAML Response for user ' . $this->uid . ': ' . $e->getMessage());
            $errors[] = $e->getMessage();
        }
        // build response data
        $rspId = bin2hex(random_bytes(12));
        $responseDataVal = [
            'isAuthenticated' => $auth->isAuthenticated(),
            'lastErrorException' => is_null($auth->getLastErrorException()) ? '': substr($auth->getLastErrorException()->__toString(), 0, 2048),
            'errors' => $errors,
            'status' => Utils::getStatus($responseAsXML),
            'eid' => $eid,
            'attributes' => $attributesAsXML
        ];
        $continueDataVal = get_object_vars(json_decode($continueData->getValue()));
        $responseDataVal = array_merge($responseDataVal, $continueDataVal);
        // we need another redirect step, to fetch the cookie (would not be sent with a cross-site post request with samesite=Lax)
        // or to return to the browser in a TR-03130 flow
        // for this we save the response data to the db
        $responseDataJson = json_encode($responseDataVal);
        $responseData = new ResponseData();
        $responseData->setRspid($rspId);
        $responseData->setValue($responseDataJson);
        $responseData->setTime(time());
        $this->persistenceManager->add($responseData);
        $this->persistenceManager->persistAll();
        $this->uriBuilder->reset();
        $this->uriBuilder->setTargetPageUid($siteInfo->getSamlPageId());
        $this->uriBuilder->setArguments(['tx_eidlogin_saml'=>['rspid'=>$rspId]]);
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $redirectUrl = $this->uriBuilder->uriFor('resume', [], 'Saml', 'eidlogin', 'Saml');
        $redirectUrl = preg_replace('/&cHash=.*$/', '', $redirectUrl);

        return $redirectUrl;
    }

    /**
     * Process Saml response data.
     *
     * @param string $rspId The id of the response data
     * @param array<string> $responseDataVal The response data itself, optional
     * @param string $locale The locale to use for l10n
     *
     * @return string The redirect where to go next
     */
    public function processSamlResponseData(string $rspId=null, array $responseDataVal=null, string $locale='en')
    {
        // defaults
        $redirect = '/';
        $errMsgCreate = $this->l10nUtil->translate('co_msg_err_eid_creation_fail_general', 'eidlogin', [], $locale);
        $errMsgLogin = $this->l10nUtil->translate('co_msg_err_eid_login_fail_general', 'eidlogin', [], $locale);
        // check for needed responseData
        if ($rspId == null) {
            throw new \Exception('processSamlResponseData - missing rspId');
        }
        if ($responseDataVal == null) {
            try {
                $responseData = $this->responseDataRepository->findByRspid($rspId);
                $this->persistenceManager->remove($responseData);
                $this->persistenceManager->persistAll();
                $responseDataVal = get_object_vars(json_decode($responseData->getValue()));
            } catch (\Exception $e) {
                $this->logger->info('processSamlResponseData - could not find responseData for rspId: ' . $rspId);

                return $redirect;
            }
        }
        // get continue stuff from response data
        $this->uid = $responseDataVal[self::KEY_UID];
        $redirectUrl = $responseDataVal[self::KEY_REDIRECT];
        $type = $responseDataVal[self::KEY_TYPE];
        $flow = $responseDataVal[self::KEY_FLOW];
        $userPids = $responseDataVal[self::KEY_USER_PIDS];
        $cookieIdFromResponseData = $responseDataVal[self::KEY_COOKIE];
        // check type
        if ($type !== self::TYPE_FE && $type !== self::TYPE_BE) {
            throw new \Exception('invalid type');
        }
        // check flow
        if ($flow !== self::FLOW_CREATE && $flow !== self::FLOW_LOGIN) {
            throw new \Exception('invalid flow');
        }
        // check if userPids
        if ($type === self::TYPE_FE && is_null($userPids)) {
            throw new \Exception('invalid userPids given when trying to start SAML flow! Did you set valid Record Storage Page settings for the frontend plugins?');
        }
        // check if correct cookie value is set
        if (!array_key_exists(self::COOKIE_SAML, $_COOKIE)) {
            $this->logger->error('processResponseData could not find needed cookie');
            $msgId = $this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

            return $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
        }
        $cookieIdFromCookie = filter_var($_COOKIE[self::COOKIE_SAML], FILTER_SANITIZE_STRING);
        setcookie(self::COOKIE_SAML, '', [
            'expires' => time()+60*5,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        if ($cookieIdFromCookie != $cookieIdFromResponseData) {
            $this->logger->error('processResponseData could not find correct cookieId in cookie');
            $msgId = $this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

            return $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
        }
        // do we have errors or an unauthenticated saml state?
        if (count($responseDataVal['errors'])!==0 || !$responseDataVal['isAuthenticated']) {
            // make error message more specific
            $msg = '';
            if (is_array($responseDataVal['status'])) {
                $msg = $responseDataVal['status']['msg'];
            } elseif (is_object($responseDataVal['status'])) {
                $msg = $responseDataVal['status']->msg;
            }
            preg_match('/.*cancel.*/', $msg, $res);
            if (count($res)>0) {
                $errMsgCreate = $this->l10nUtil->translate('co_msg_err_eid_creation_abort', 'eidlogin', [], $locale);
                $errMsgLogin = $this->l10nUtil->translate('co_msg_err_eid_login_abort', 'eidlogin', [], $locale);
            }
            $this->logger->info('processResponseData found errors or user not authenticated - errors:' . print_r($responseDataVal['errors'], true) . ', saml status msg: ' . $msg);
            $this->logger->info('processResponseData - lastErrorException:' . $responseDataVal['lastErrorException']);
            $msgId = $this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

            return $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
        }
        // fetch eid
        $eidvalue = $responseDataVal['eid'];
        if (is_null($eidvalue)) {
            $this->logger->error('missing eid in SAML response');
            $msgId = $this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

            return $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
        }
        // user is creating an eID connection from it's settings page
        if ($flow === self::FLOW_CREATE) {
            try {
                if ($this->uid==='') {
                    $this->logger->error('$uid of EidService is null, could not determine user for which to createEid!');
                    throw new \Exception($this->l10nUtil->translate('co_msg_err_eid_creation_fail_general', 'eidlogin', [], $locale));
                }
                // do we already have an eID connection with this uid?
                if ($this->checkEid($type)) {
                    throw new \Exception($this->l10nUtil->translate('co_msg_err_eid_creation_fail_user_already_connected', 'eidlogin', [], $locale));
                }
                // does a connection of the eID to an account of the same type (in the same site) already exist?
                $eidCurrent = [];
                if ($type==self::TYPE_FE) {
                    foreach ($userPids as $userPid) {
                        try {
                            $eidCurrent = $this->eidRepository->findByPidAndEidvalue($userPid, $eidvalue);
                        } catch (\Exception $e) {
                        }
                    }
                }
                if ($type==self::TYPE_BE) {
                    try {
                        $eidCurrent = $this->eidRepository->findByTypeAndEidvalue($type, $eidvalue);
                    } catch (\Exception $e) {
                    }
                }
                if (count($eidCurrent) !== 0) {
                    throw new \Exception($this->l10nUtil->translate('co_msg_err_eid_creation_fail_eid_already_connected', 'eidlogin', [], $locale));
                }
                // ok try to create
                try {
                    // check if the pid of the user is valid for the current site
                    // this should not fail, just to be sure in case of misconfiguration
                    $pid = 0;
                    if ($type==self::TYPE_FE) {
                        $pid = $this->frontendUserRepository->getPidByUid($this->uid);
                        if (!in_array($pid, $userPids)) {
                            throw new \Exception('invalid pid ' . $pid . ' from user ' . $this->uid . '. Valid pids are ' . implode(',', $userPids));
                        }
                    }
                    $eid = new Eid();
                    $eid->setPid($pid);
                    $eid->setEidvalue($eidvalue);
                    if ($type==self::TYPE_FE) {
                        $eid->setFeuid($this->uid);
                    }
                    if ($type==self::TYPE_BE) {
                        $eid->setBeuid($this->uid);
                    }
                    foreach ($responseDataVal['attributes'] as $name => $values) {
                        if (count($values)===0) {
                            continue;
                        }
                        $valueCount = 0;
                        foreach ($values as $value) {
                            $currentName = $name;
                            if ($valueCount > 0) {
                                $currentName .= '_' . (string)$valueCount;
                            }
                            $attribute = new Attribute();
                            $attribute->setEid($eid);
                            $attribute->setName($currentName);
                            $attribute->setValue($value);
                            $this->persistenceManager->add($attribute);
                            $valueCount++;
                        }
                    }
                    $this->persistenceManager->persistAll();
                    $this->logger->info('eid connection of user with uid ' . $this->uid . ' and type ' . $type . ' created successfully');
                } catch (\Exception $e) {
                    $this->logger->error('tried to create eID connection but exception occured: ' . $e->getMessage());
                    throw new \Exception($this->l10nUtil->translate('co_msg_err_eid_creation_fail_general', 'eidlogin'));
                }
                $msgId = $this->setSuccessMsg($this->l10nUtil->translate('co_msg_scs_eid_created', 'eidlogin', [], $locale));
            } catch (\Exception $e) {
                $errMsgCreate = $e->getMessage();
                $msgId = $this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);
            }

            return $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
        }
        // user is logging in via eID connection from the login page
        if ($flow === self::FLOW_LOGIN) {
            if ($type === self::TYPE_FE) {
                // try to get an eid, it must match a pid connected to the current site!
                $eidObject = null;
                foreach ($userPids as $userPid) {
                    try {
                        $res = $this->eidRepository->findByPidAndEidvalue($userPid, $eidvalue);
                        if (count($res) === 1) {
                            $eidObject = $res[0];
                            break;
                        }
                    } catch (\Exception $e) {
                    }
                }
                if (is_null($eidObject)) {
                    $this->logger->info('processSamlResponseData eid not yet connected to a matching useraccount:' . $eidvalue);
                    $errMsgLogin = $this->l10nUtil->translate('co_msg_err_eid_login_fail_eid_not_connected', 'eidlogin', [], $locale);
                    $msgId = $this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

                    return $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
                }
                // setting post vars is needed for the auth process, otherwise our auth EidFeAuthService is not called
                // for 'logintype' value see TYPO3\CMS\Core\Authentication\LoginType::LOGIN, can not import it, no idea why :(
                $_POST['logintype'] = 'login';
                // $_POST['user'] will be avail as $this->login['uname'] in the EidFeAuthService
                $authData = [];
                $authData['eid'] = $eidObject->getEidvalue();
                $authData['pid'] = $eidObject->getPid();
                $_POST['user'] = json_encode($authData);
                $this->feUserAuth->start();
                // auth fail?
                if ($this->feUserAuth->loginFailure) {
                    $this->logger->info('processSamlResponseData: failed auth of user with connected eid:' . $eidvalue);
                    $errMsgLogin = $this->l10nUtil->translate('co_msg_err_eid_login_fail_general', 'eidlogin', [], $locale);
                    $msgId = $this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

                    return $redirectUrl . '?' . MessageService::PARAM_MSGID . '=' . $msgId;
                }
                // we need to set the cookie here for TYPO3 11 as the FrontendUserAuthenticator middleware is using
                // another instance of FrontendUserAuthentication
                if (Typo3VersionUtil::isVersion11() && $this->feUserAuth->setCookie !== null) {
                    header('Set-Cookie: ' . $this->feUserAuth->setCookie->__toString());
                }
            }
            if ($type === self::TYPE_BE) {
                throw new \Exception('not yet implemented');
            }

            return $redirectUrl;
        }
    }

    /**
     * Delete the eid connection and attributes of a user.
     * NOT YET DONE FOR TYPE_BE
     *
     * @param int $uid The uid of the user, for which the connection should be deleted. If null is given the current user is used.
     * @param string $locale The locale to use for l10n
     * @return string A success msgid
     *
     * @throws \Exception If the user to work which could not be determined
     */
    public function deleteEid(int $uid=null, string $locale='en'): string
    {
        if (is_null($uid)) {
            if (is_null($this->uid)) {
                throw new \Exception('$uid of EidService is null, could not determine user for which to deleteEid!');
            }
            $uid = $this->uid;
        }
        try {
            $eid = $this->eidRepository->findByFeuid($uid);
            $attributes = $this->attributeRepository->findByEidUid($eid->getUid());
            foreach ($attributes as $attribute) {
                $this->persistenceManager->remove($attribute);
            }
            $this->persistenceManager->remove($eid);
            $this->frontendUserRepository->setDisablePwLogin($uid, 0);
            $this->logger->info('eid connection of user with uid ' . $uid . ' deleted successfully');
        } catch (\Exception $e) {
            throw new \Exception('failed to delete eid connection of user with uid ' . $uid . ': ' . $e->getMessage());
        }

        return $this->setSuccessMsg($this->l10nUtil->translate('co_msg_scs_eid_deleted', 'eidlogin', [], $locale));
    }

    /**
     * Delete the eid connections and attributes of all users belonging to given site
     * NOT YET DONE FOR TYPE_BE
     *
     * @param SiteInfo $siteInfo The siteInfo to delete for
     *
     * @return bool True on success, false in case of error
     */
    public function deleteEids(SiteInfo $siteInfo): bool
    {
        try {
            $userPids = $siteInfo->getUserPageIds();
            foreach ($userPids as $userPid) {
                $eids = [];
                try {
                    $eids = $this->eidRepository->findByPid($userPid);
                } catch (\Exception $e) {
                }
                foreach ($eids as $eid) {
                    $attributes = $this->attributeRepository->findByEidUid($eid->getUid());
                    foreach ($attributes as $attribute) {
                        $this->persistenceManager->remove($attribute);
                    }
                    $this->persistenceManager->remove($eid);
                    $this->frontendUserRepository->setDisablePwLogin($eid->getFeuid(), 0);
                }
            }
            $this->logger->info('eid connections and attributes for site ' . $siteInfo->getSite()->getIdentifier . ' deleted');

            return true;
        } catch (\Exception $e) {
            $this->logger->error('failed to delete eid connection of all users: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Set error messages.
     *
     * @param string $flow The flow we are in (create of login)
     * @param string $errMsgLogin The msg for the login flow
     * @param string $errMsgCreate The msg for the create flow
     * @return string A msgid
     */
    private function setErrorMsg(
        string $flow=self::FLOW_LOGIN,
        string $errMsgLogin='',
        string $errMsgCreate=''
    ): string {
        if ($flow !== self::FLOW_CREATE && $flow !== self::FLOW_LOGIN) {
            throw new \Exception('invalid flow');
        }
        $msg = '';
        if ($flow === self::FLOW_LOGIN) {
            $msg = $errMsgLogin;
        }
        if ($flow === self::FLOW_CREATE) {
            $msg = $errMsgCreate;
        }

        return $this->messageService->saveMessage(AbstractMessage::ERROR, $msg);
    }

    /**
     * Set success message.
     *
     * @param string $msg The msg
     * @return string A msgid
     */
    private function setSuccessMsg(string $msg=''): string
    {
        return $this->messageService->saveMessage(AbstractMessage::OK, $msg);
    }
}
