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

use Ecsec\Eidlogin\Domain\Repository\FrontendUserRepository;
use Ecsec\Eidlogin\Util\Typo3VersionUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class SettingsService
 */
class SettingsService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var FrontendUserRepository */
    private $frontendUserRepository;
    /** @var SslService */
    private $sslService;
    /** @var ExtensionConfiguration */
    private $config;
    /** @var LocalizationUtility */
    private $l10nUtil;

    /**
     * @param FrontendUserRepository $frontendUserRepository
     * @param SslService $sslService
     * @param ExtensionConfiguration $config
     * @param LocalizationUtility $l10nUtil
     */
    public function __construct(
        FrontendUserRepository $frontendUserRepository,
        SslService $sslService,
        ExtensionConfiguration $config,
        LocalizationUtility $l10nUtil
    ) {
        $this->frontendUserRepository = $frontendUserRepository;
        $this->sslService = $sslService;
        $this->config = $config;
        $this->l10nUtil = $l10nUtil;
    }

    /**
     * Test if we have settings already
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing
     *
     * @return bool True if settings are present
     */
    public function settingsPresent(int $siteRootPageId): bool
    {
        $appValueKeys = [
            'idp_cert_sign',
            'idp_entity_id',
            'idp_sso_url',
            'sp_cert_act',
            'sp_cert_act_enc',
            'sp_entity_id',
            'sp_key_act',
            'sp_key_act_enc',
        ];

        foreach ($appValueKeys as $key) {
            try {
                if ($this->config->get('eidlogin', $siteRootPageId . '/' . $key)==='') {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the current Settings
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     *
     * @return array<mixed> $settings The settings
     */
    public function getSettings(int $siteRootPageId): array
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('not config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        $settingKeys = [
            'activated',
            'idp_cert_enc',
            'idp_cert_sign',
            'idp_entity_id',
            'idp_ext_tr03130',
            'idp_sso_url',
            'sp_cert_act',
            'sp_cert_act_enc',
            'sp_cert_new',
            'sp_cert_new_enc',
            'sp_enforce_enc',
            'sp_entity_id',
            'sp_key_act',
            'sp_key_act_enc',
            'sp_key_new',
            'sp_key_new_enc',
        ];
        $settings = [];
        foreach ($settingKeys as $key) {
            $val = null;
            try {
                $val = $this->config->get('eidlogin', $siteRootPageId . '/' . $key);
            } catch (\Exception $e) {
            }
            $settings[$key] = $val;
        }

        return $settings;
    }

    /**
     * Save app specific settings.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param string $idp_cert_enc The certificate used for encryption at the SP
     * @param string $idp_cert_sign The certificate used for signing at the IDP
     * @param string $idp_entity_id The entity id of the IDP
     * @param ?string $idp_ext_tr03130 The TR-03130 Extension value
     * @param string $idp_sso_url The sso url at the IDP
     * @param ?string $sp_enforce_enc Enforce encryption of SAML assertions
     * @param string $sp_entity_id The entity id of the SP
     *
     * @throws \Exception If the param $siteRootPageId is missing
     *
     * @return array<string> An array of errors if some occured, an empty array otherwise.
     */
    public function saveSettings(
        int $siteRootPageId,
        string $idp_cert_enc,
        string $idp_cert_sign,
        string $idp_entity_id,
        ?string $idp_ext_tr03130,
        string $idp_sso_url,
        ?string $sp_enforce_enc,
        string $sp_entity_id
    ): array {
        $errors = [];
        $currentExtConfig = $this->config->get('eidlogin');
        $currentSiteConfig = array_key_exists($siteRootPageId, $currentExtConfig) ? $currentExtConfig[$siteRootPageId] : [];
        if ($idp_cert_enc !== '') {
            try {
                $keyLengthValid = $this->sslService->checkCertPubKeyLength($idp_cert_enc);
                if ($keyLengthValid) {
                    $currentSiteConfig['idp_cert_enc'] = filter_var($idp_cert_enc, FILTER_SANITIZE_STRIPPED);
                } else {
                    $errors[] = $this->l10nUtil->translate('be_msg_err_idp_cert_enc_invalid_key_length', 'eidlogin') . SslService::KEY_LENGTH_LIMIT_LOWER . '.';
                }
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                $errors[] = $this->l10nUtil->translate('be_msg_err_idp_cert_enc_invalid', 'eidlogin');
            }
        } else {
            $currentSiteConfig['idp_cert_enc'] = '';
        }
        if ($idp_cert_sign !== '') {
            try {
                $keyLengthValid = $this->sslService->checkCertPubKeyLength($idp_cert_sign);
                if ($keyLengthValid) {
                    $currentSiteConfig['idp_cert_sign'] = filter_var($idp_cert_sign, FILTER_SANITIZE_STRIPPED);
                } else {
                    $errors[] = $this->l10nUtil->translate('be_msg_err_idp_cert_sign_invalid_key_length', 'eidlogin') . SslService::KEY_LENGTH_LIMIT_LOWER . '.';
                }
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                $errors[] = $this->l10nUtil->translate('be_msg_err_idp_cert_sign_invalid', 'eidlogin');
            }
        } else {
            $errors[] = $this->l10nUtil->translate('be_msg_err_idp_cert_sign_missing', 'eidlogin');
        }
        if ($idp_entity_id !== '') {
            $currentSiteConfig['idp_entity_id'] = filter_var($idp_entity_id, FILTER_SANITIZE_STRIPPED);
        } else {
            $errors[] = $this->l10nUtil->translate('be_msg_err_idp_entityid_missing', 'eidlogin');
        }
        if ($idp_ext_tr03130 !== '') {
            $tr03130Errors = [];
            if ($idp_cert_enc === '') {
                $tr03130Errors[] = $this->l10nUtil->translate('be_msg_err_idp_cert_enc_needed', 'eidlogin');
            }
            if (is_null($sp_enforce_enc)) {
                $tr03130Errors[] = $this->l10nUtil->translate('be_msg_err_idp_tr03130ext_enforce_enc_needed', 'eidlogin');
            }
            try {
                $dom = new \DOMDocument();
                $res = $dom->loadXML($idp_ext_tr03130);
                if (!$res) {
                    $tr03130Errors[] = $this->l10nUtil->translate('be_msg_err_idp_tr03130ext_invalid', 'eidlogin');
                }
            } catch (\Exception $e) {
                $tr03130Errors[] = $this->l10nUtil->translate('be_msg_err_idp_tr03130ext_invalid', 'eidlogin');
            }
            if (count($tr03130Errors)>0) {
                $errors = array_merge($errors, $tr03130Errors);
            } else {
                $currentSiteConfig['idp_ext_tr03130'] = $idp_ext_tr03130;
            }
        } else {
            $currentSiteConfig['idp_ext_tr03130'] = '';
        }
        if ($idp_sso_url != '') {
            if (!filter_var($idp_sso_url, FILTER_VALIDATE_URL)) {
                $errors[] = $this->l10nUtil->translate('be_msg_err_idp_sso_url_invalid', 'eidlogin');
            } elseif (strpos($idp_sso_url, 'https://')!==0) {
                $errors[] = $this->l10nUtil->translate('be_msg_err_idp_sso_url_no_https', 'eidlogin');
            } else {
                $currentSiteConfig['idp_sso_url'] = filter_var($idp_sso_url, FILTER_SANITIZE_STRIPPED);
            }
        } else {
            $errors[] = $this->l10nUtil->translate('be_msg_err_idp_sso_url_missing', 'eidlogin');
        }
        if (!is_null($sp_enforce_enc)) {
            $currentSiteConfig['sp_enforce_enc'] = true;
        } else {
            $currentSiteConfig['sp_enforce_enc'] = false;
        }
        if ($sp_entity_id !== '') {
            $currentSiteConfig['sp_entity_id'] = filter_var($sp_entity_id, FILTER_SANITIZE_STRIPPED);
        } else {
            $errors[] = $this->l10nUtil->translate('be_msg_err_sp_entityid_missing', 'eidlogin');
        }
        if (count($currentSiteConfig) > 0) {
            $currentExtConfig[$siteRootPageId] = $currentSiteConfig;
            if (Typo3VersionUtil::isVersion10()) {
                $this->config->set('eidlogin', '', $currentExtConfig);
            } else {
                $this->config->set('eidlogin', $currentExtConfig);
            }
        }

        return $errors;
    }

    /**
     * Toggle the active state of the extension
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     */
    public function toggleActivated(int $siteRootPageId): void
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('no config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        $activated='';
        try {
            $activated = $this->config->get('eidlogin', $siteRootPageId . '/activated');
        } catch (\Exception $e) {
        }
        if ($activated=='') {
            $activated = false;
        }
        $currentExtConfig = $this->config->get('eidlogin');
        $currentExtConfig[$siteRootPageId]['activated'] = !$activated;
        if (Typo3VersionUtil::isVersion10()) {
            $this->config->set('eidlogin', '', $currentExtConfig);
        } else {
            $this->config->set('eidlogin', $currentExtConfig);
        }

        return;
    }

    /**
     * Get the current active state of the extension
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     *
     * @return bool The value of the activation
     */
    public function getActivated(int $siteRootPageId): bool
    {
        try {
            $activated = $this->config->get('eidlogin', $siteRootPageId . '/activated');
            if ($activated=='') {
                $activated = false;
            } else {
                $activated = true;
            }

            return $activated;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
    * Delete extension specific settings.
    *
    * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
    *
    * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
    */
    public function deleteSettings(int $siteRootPageId): void
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('not config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        $currentExtConfig = $this->config->get('eidlogin');
        unset($currentExtConfig[$siteRootPageId]);
        if (Typo3VersionUtil::isVersion10()) {
            $this->config->set('eidlogin', '', $currentExtConfig);
        } else {
            $this->config->set('eidlogin', $currentExtConfig);
        }
        /*
        $settingKeys = [
         'activated',
         'idp_cert_enc',
         'idp_cert_sign',
         'idp_entity_id',
         'idp_ext_tr03130',
         'idp_sso_url',
         'sp_cert_act',
         'sp_cert_act_enc',
         'sp_cert_new',
         'sp_cert_new_enc',
         'sp_cert_old',
         'sp_cert_old_enc',
         'sp_enforce_enc',
         'sp_entity_id',
         'sp_key_act',
         'sp_key_act_enc',
         'sp_key_new',
         'sp_key_new_enc',
         'sp_key_old',
         'sp_key_old_enc',
      ];
        foreach ($settingKeys as $key) {
            $this->config->set('eidlogin', $siteRootPageId . '/' . $key, '');
        }
        */
    }

    /**
     * Get the value of the tx_eidlogin_disablepwlogin setting for the given uid.
     *
     * @param int $uid The uid to fetch the value for
     * @param string $type The type of the uid given
     *
     * @return int The value of the setting, null if none has been found
     */
    public function getDisablePwLogin(int $uid, string $type=EidService::TYPE_FE): ?int
    {
        // check type
        if ($type !== EidService::TYPE_FE && $type !== EidService::TYPE_BE) {
            throw new \Exception('invalid type');
        }
        if ($type == EidService::TYPE_BE) {
            throw new \Exception('not yet implemented');
        }

        return $this->frontendUserRepository->getDisablePwLogin($uid);
    }

    /**
     * Set the value of the tx_eidlogin_disablepwlogin setting for the given uid.
     * This may fail if the fe_user with the given uid has no email set and the value to set is 1
     *
     * @param int $uid The uid to set the value for
     * @param int $value The value to set
     * @param string $type The type of the uid given
     *
     * @return bool True if the value has been set
     */
    public function setDisablePwLogin(int $uid, $value, string $type=EidService::TYPE_FE): bool
    {
        // check type
        if ($type !== EidService::TYPE_FE && $type !== EidService::TYPE_BE) {
            throw new \Exception('invalid type');
        }
        if ($type == EidService::TYPE_BE) {
            throw new \Exception('not yet implemented');
        }
        if (!$this->frontendUserRepository->hasEmailAdressSet($uid) && $value===1) {
            return false;
        }
        $this->frontendUserRepository->setDisablePwLogin($uid, $value);

        return true;
    }
}
