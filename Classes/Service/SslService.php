<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace Ecsec\Eidlogin\Service;

use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Utils;
use Ecsec\Eidlogin\Util\Typo3VersionUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Class SslService
 */
class SslService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var string */
    public const VALID_SPAN = 730; // 2 years, if this is changed, you must also change span def. in CertificateJob!
    /** @var string */
    public const DATES_VALID_FROM = 'validFrom';
    /** @var string */
    public const DATES_VALID_TO = 'validTo';
    /** @var int */
    public const KEY_LENGTH_LIMIT_LOWER = 2048;

    /** @var ExtensionConfiguration */
    private $config;

    /**
     * @param ExtensionConfiguration $config
     */
    public function __construct(
        ExtensionConfiguration $config
    ) {
        $this->config = $config;
    }

    /**
     * Checks for actual keys and certs in config.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing
     * @return bool True if an actual key and cert has been found
     */
    public function checkActCertPresent(int $siteRootPageId): bool
    {
        try {
            $keyStr = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_act');
            $certStr = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_act');
            $keyStrEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_act_enc');
            $certStrEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_act_enc');
            if (!empty(trim($keyStr)) && !empty(trim($certStr)) && !empty(trim($keyStrEnc)) && !empty(trim($certStrEnc))) {
                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * Return the actual signature cert as string.
     * An empty string is returned if no value has been found.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param bool $endOnly Give back only the last 20 chars of the cert if true
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the certificate could not be found or read or parsed
     *
     * @return string The actual signature ert as string
     */
    public function getCertAct(int $siteRootPageId, $endOnly = false): string
    {
        return $this->getCertString($siteRootPageId, 'sp_cert_act', $endOnly);
    }

    /**
     * Return the actual encryption cert as string.
     * An empty string is returned if no value has been found.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param bool $endOnly Give back only the last 20 chars of the cert if true
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the certificate could not be found or read or parsed
     *
     * @return string The actual encryption cert as string
     */
    public function getCertActEnc(int $siteRootPageId, $endOnly = false): string
    {
        return $this->getCertString($siteRootPageId, 'sp_cert_act_enc', $endOnly);
    }

    /**
     * Checks for new key and cert in config.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing
     * @return bool True if an actual key and cert has been found
     */
    public function checkNewCertPresent(int $siteRootPageId): bool
    {
        try {
            $keyStr = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_new');
            $certStr = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_new');
            $keyStrEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_new_enc');
            $certStrEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_new_enc');
            if (!empty(trim($keyStr)) && !empty(trim($certStr)) && !empty(trim($keyStrEnc)) && !empty(trim($certStrEnc))) {
                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * Return the new signature cert as string.
     * An empty string is returned if no value has been found.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param bool $endOnly Give back only the last 20 chars of the cert if true
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the certificate could not be found or read or parsed
     *
     * @return string The new signature cert as string
     */
    public function getCertNew(int $siteRootPageId, bool $endOnly = false): string
    {
        return $this->getCertString($siteRootPageId, 'sp_cert_new', $endOnly);
    }

    /**
     * Return the new encryption cert as string.
     * An empty string is returned if no value has been found.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param bool $endOnly Give back only the last 20 chars of the cert if true
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the certificate could not be found or read or parsed
     *
     * @return string The new encryption cert as string
     */
    public function getCertNewEnc(int $siteRootPageId, bool $endOnly = false): string
    {
        return $this->getCertString($siteRootPageId, 'sp_cert_new_enc', $endOnly);
    }

    /**
     * Return the cert as string.
     * An empty string is returned if no value has been found.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param string $key The config key of the cert
     * @param bool $endOnly Give back only the last 20 chars of the cert if true
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the certificate could not be found or read or parsed
     *
     * @return string The cert as string
     */
    private function getCertString(int $siteRootPageId, string $key, bool $endOnly): string
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('not config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        $certStr = $this->config->get('eidlogin', $siteRootPageId . '/' . $key);
        if ($endOnly) {
            $certStr = substr($certStr, strlen($certStr)-66, 40);
        }

        return $certStr;
    }

    /**
     * Creates new private key and certificate.
     * Saves them for actual use if non is set already.
     * Saves them for later use otherwise.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If something with OpenSSL goes wrong
     */
    public function createNewCert(int $siteRootPageId): void
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('not config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        if (!extension_loaded('openssl')) {
            throw new \Exception('openssl error: openssl extension not available.');
        }
        // use our own config
        $opensslConfigArgs = ['config' => __DIR__ . '/../../openssl.conf'];
        // use the app name as common name
        $subject = ['commonName' => 'TYPO3 eID-Login Extension'];
        // use current time as serial number
        $serial = time();
        // create signature key and cert
        $key = openssl_pkey_new($opensslConfigArgs);
        if (!$key) {
            throw new \Exception('openssl error: failed to create signature private key');
        }
        $res = openssl_pkey_export($key, $keyStr);
        if (!$res) {
            throw new \Exception('openssl error: failed to export signature private key');
        }
        $csr = openssl_csr_new($subject, $key, $opensslConfigArgs);
        if (!$csr) {
            throw new \Exception('openssl error: failed to create signature csr');
        }
        $cert = openssl_csr_sign($csr, null, $key, self::VALID_SPAN, $opensslConfigArgs, $serial);
        if (!$cert) {
            throw new \Exception('openssl error: failed to create signature cert');
        }
        $res = openssl_x509_export($cert, $certStr);
        if (!$res) {
            throw new \Exception('openssl error: failed to export signature cert');
        }
        // use current time as serial number
        $serial = time();
        // create encryption key and cert
        $keyEnc = openssl_pkey_new($opensslConfigArgs);
        if (!$keyEnc) {
            throw new \Exception('openssl error: failed to create encryption private key');
        }
        $res = openssl_pkey_export($keyEnc, $keyStrEnc);
        if (!$res) {
            throw new \Exception('openssl error: failed to export encryption private key');
        }
        $csrEnc = openssl_csr_new($subject, $keyEnc, $opensslConfigArgs);
        if (!$csrEnc) {
            throw new \Exception('openssl error: failed to create encryption csr');
        }
        $certEnc = openssl_csr_sign($csrEnc, null, $key, self::VALID_SPAN, $opensslConfigArgs, $serial);
        if (!$certEnc) {
            throw new \Exception('openssl error: failed to create encryption cert');
        }
        $res = openssl_x509_export($certEnc, $certStrEnc);
        if (!$res) {
            throw new \Exception('openssl error: failed to export encryption cert');
        }
        // save as act or new
        $currentExtConfig = $this->config->get('eidlogin');
        $currentSiteConfig = $currentExtConfig[$siteRootPageId];
        $configKeyKey = 'sp_key_act';
        $configKeyCert = 'sp_cert_act';
        $configKeyKeyEnc = 'sp_key_act_enc';
        $configKeyCertEnc = 'sp_cert_act_enc';
        if ($this->checkActCertPresent($siteRootPageId)) {
            $configKeyKey = 'sp_key_new';
            $configKeyCert = 'sp_cert_new';
            $configKeyKeyEnc = 'sp_key_new_enc';
            $configKeyCertEnc = 'sp_cert_new_enc';
        }
        $currentSiteConfig[$configKeyKey] = $keyStr;
        $currentSiteConfig[$configKeyCert] = $certStr;
        $currentSiteConfig[$configKeyKeyEnc] = $keyStrEnc;
        $currentSiteConfig[$configKeyCertEnc] = $certStrEnc;
        $currentExtConfig[$siteRootPageId] = $currentSiteConfig;
        if (Typo3VersionUtil::isVersion10()) {
            $this->config->set('eidlogin', '', $currentExtConfig);
        } else {
            $this->config->set('eidlogin', $currentExtConfig);
        }

        return;
    }

    /**
     * The DateTimes from and until the actual signature certificate is valid to.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     *
     * @return array<\DateTimeImmutable> $retVal The DateTimes form and until the actual signature certificate is valid to as assoc array
     */
    public function getActDates(int $siteRootPageId): array
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('no config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        $certAct = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_act');
        if ('' === $certAct) {
            throw new \Exception('for siteRootPageId ' . $siteRootPageId . ' is no actual cert found in eID-Login config');
        }
        $cert = openssl_x509_read($certAct);
        if (!$cert) {
            throw new \Exception('openssl error: failed to read the certificate found in eID-Login config for siteRootPageId ' . $siteRootPageId);
        }
        $certDetails = openssl_x509_parse($cert);
        if (!$certDetails) {
            throw new \Exception('openssl error: failed to parse certificate found in eID-Login App config in siteRootPageId ' . $siteRootPageId);
        }

        $retVal = [];
        $retVal[self::DATES_VALID_FROM] = \DateTimeImmutable::createFromFormat('ymdGisT', $certDetails['validFrom']);
        $retVal[self::DATES_VALID_TO] = \DateTimeImmutable::createFromFormat('ymdGisT', $certDetails['validTo']);

        return $retVal;
    }

    /**
     * Do a key rollover. This will backup the actual keys and certs,
     * and replace it with the new keys and certs.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the actual or new keys or certificates could not be found
     */
    public function rollover(int $siteRootPageId): void
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('no config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        $keyAct = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_act');
        if (''===$keyAct) {
            throw new \Exception('no actual key found in eID-Login config for siteRootPageId ' . $siteRootPageId);
        }
        $certAct = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_act');
        if (''===$certAct) {
            throw new \Exception('no actual cert found in eID-Login config for ' . $siteRootPageId);
        }
        $keyNew = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_new');
        if (''===$keyNew) {
            throw new \Exception('no new key found in eID-Login config for siteRootPageId ' . $siteRootPageId);
        }
        $certNew = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_new');
        if (''===$certNew) {
            throw new \Exception('no new cert found in eID-Login config for siteRootPageId ' . $siteRootPageId);
        }
        $keyActEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_act_enc');
        if (''===$keyActEnc) {
            throw new \Exception('no actual key found in eID-Login config');
        }
        $certActEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_act_enc');
        if (''===$certActEnc) {
            throw new \Exception('no actual cert found in eID-Login config for siteRootPageId ' . $siteRootPageId);
        }
        $keyNewEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_key_new_enc');
        if (''===$keyNewEnc) {
            throw new \Exception('no new key found in eID-Login config for siteRootPageId ' . $siteRootPageId);
        }
        $certNewEnc = $this->config->get('eidlogin', $siteRootPageId . '/sp_cert_new_enc');
        if (''===$certNewEnc) {
            throw new \Exception('no new cert found in eID-Login config for siteRootPageId ' . $siteRootPageId);
        }
        $currentExtConfig = $this->config->get('eidlogin');
        $currentExtConfig[$siteRootPageId]['sp_key_old'] = $keyAct;
        $currentExtConfig[$siteRootPageId]['sp_cert_old'] = $certAct;
        $currentExtConfig[$siteRootPageId]['sp_key_old_enc'] = $keyActEnc;
        $currentExtConfig[$siteRootPageId]['sp_cert_old_enc'] = $certActEnc;
        $currentExtConfig[$siteRootPageId]['sp_key_act'] = $keyNew;
        $currentExtConfig[$siteRootPageId]['sp_cert_act'] = $certNew;
        $currentExtConfig[$siteRootPageId]['sp_key_act_enc'] = $keyNewEnc;
        $currentExtConfig[$siteRootPageId]['sp_cert_act_enc'] = $certNewEnc;
        $currentExtConfig[$siteRootPageId]['sp_key_new'] = '';
        $currentExtConfig[$siteRootPageId]['sp_cert_new'] = '';
        $currentExtConfig[$siteRootPageId]['sp_key_new_enc'] = '';
        $currentExtConfig[$siteRootPageId]['sp_cert_new_enc'] = '';
        if (Typo3VersionUtil::isVersion10()) {
            $this->config->set('eidlogin', '', $currentExtConfig);
        } else {
            $this->config->set('eidlogin', $currentExtConfig);
        }

        return;
    }

    /**
     * Check if the public key of a given certificate has a longer key than the limit.
     * The limit ist set as const of SsLService.
     *
     * @param string $cert The certificate
     *
     * @throws \Exception if the input can not be handled as certificate
     *
     * @return bool True if the key length is longer than the limit
     */
    public function checkCertPubKeyLength(string $cert): bool
    {
        $pubKey = openssl_pkey_get_public(Utils::formatCert($cert));
        if (!$pubKey) {
            throw new \Exception('Could not read public key of x509 cert string');
        }
        $pubKeyDetails = openssl_pkey_get_details($pubKey);
        if (!$pubKeyDetails) {
            throw new \Exception('Could not read public key details');
        }
        if ($pubKeyDetails['bits']>=self::KEY_LENGTH_LIMIT_LOWER) {
            return true;
        }

        return false;
    }
}
