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

use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Error;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\IdPMetadataParser;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Settings;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Utils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Class SamlService
 */
class SamlService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ExtensionConfiguration */
    private $config;
    /** @var UriBuilder */
    private $uriBuilder;

    /**
     * @param ExtensionConfiguration $config
     * @param UriBuilder $uriBuilder
     */
    public function __construct(
        ExtensionConfiguration $config,
        UriBuilder $uriBuilder
    ) {
        $this->config = $config;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Get Service Provider SAML Metadata.
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param int $samlPageId The pageId of the saml page of the site to be used
     * @param int $languageId The id of the language to be used for the url building
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the param $samlPageId is missing
     * @throws Error If the Service Provider SAML Metadata are invalid
     *
     * @return string
     */
    public function getSpSamlMetadata(
        int $siteRootPageId,
        int $samlPageId,
        int $languageId
    ): string {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('not config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        $settings = new Settings($this->getSamlSettings($siteRootPageId, $samlPageId, $languageId), true);
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);
        if (!empty($errors)) {
            throw new Error('Invalid SP metadata: ' . implode(', ', $errors), Error::METADATA_SP_INVALID);
        }

        return $metadata;
    }

    /**
     * Get Identity Provider SAML Metadata.
     *
     * @param string $url The url where to fetch the metadata
     *
     * @throws Error If the Identity Provider SAML Metadata are invalid
     *
     * @return array<string> $metadata
     */
    public function fetchIdpSamlMetadata(string $url): array
    {
        $metadata = [];
        $metadataRaw = IdPMetadataParser::parseRemoteXML($url);
        $metadataFirst = $metadataRaw['idp'];
        if (array_key_exists('x509cert', $metadataFirst)) {
            $metadata['idp_cert_sign'] = $metadataFirst['x509cert'];
            $metadata['idp_cert_enc'] = $metadataFirst['x509cert'];
        } else {
            $metadata['idp_cert_sign'] = $metadataFirst['x509certMulti']['signing'][0];
            $metadata['idp_cert_enc'] = $metadataFirst['x509certMulti']['encryption'][0];
        }
        $metadata['idp_entity_id'] = $metadataFirst['entityId'];
        $metadata['idp_sso_url'] = $metadataFirst['singleSignOnService']['url'];

        return $metadata;
    }

    /**
     * Test if we have TR-03130 configured
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     *
     * @return bool True if it is configured
     */
    public function checkForTr03130(int $siteRootPageId): bool
    {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('no config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        // determine if we are in tr03130 a flow
        try {
            $idp_ext_tr03130 = $this->config->get('eidlogin', $siteRootPageId . '/idp_ext_tr03130');
            if ($idp_ext_tr03130!='') {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Get the SAML settings with values from the extensions config
     *
     * @param int $siteRootPageId The pageId of the root page of the site to be used for configuration set/get
     * @param int $samlPageId The pageId of the saml page of the site to be used
     * @param int $defaultLanguage The defaultLanguage for url creation
     *
     * @throws \Exception If the param $siteRootPageId is missing or no matching config can be found
     * @throws \Exception If the param $samlPageId is missing
     * @throws \Exception If the actual signature certificate could not be found or read or parsed
     *
     * @return array<mixed>
     */
    public function getSamlSettings(
        int $siteRootPageId,
        int $samlPageId,
        int $defaultLanguage
    ): array {
        if (!is_array($this->config->get('eidlogin', (string)$siteRootPageId))) {
            throw new \Exception('no config for given siteRootPageId ' . $siteRootPageId . ' found');
        }
        //determine if we should skip xml validation
        $skipXmlValidation = false;
        try {
            $skipXmlValidation = $this->config->get('eidlogin', $siteRootPageId . '/skipxmlvalidation');
        } catch (\Exception $e) {
            $this->logger->debug('eidlogin_skipxmlvalidation is not set');
        }
        // determine if we are in tr03130 a flow
        $idp_ext_tr03130 = '';
        try {
            $idp_ext_tr03130 = $this->config->get('eidlogin', $siteRootPageId . '/idp_ext_tr03130');
        } catch (\Exception $e) {
        }
        // build acs url in dependency of tr03130 flow
        $this->uriBuilder->reset();
        $this->uriBuilder->setLanguage((string)$defaultLanguage);
        $this->uriBuilder->setTargetPageUid($samlPageId);
        $this->uriBuilder->setCreateAbsoluteUri(true);
        if ($idp_ext_tr03130!='') {
            $this->uriBuilder->uriFor('acsRedirect', [], 'Saml', 'eidlogin', 'Saml');
        } else {
            $this->uriBuilder->uriFor('acsPost', [], 'Saml', 'eidlogin', 'Saml');
        }
        $acsUrl = $this->uriBuilder->buildFrontendUri();
        $acsUrl = preg_replace('/&cHash=.*$/', '', $acsUrl);
        // set value for sp_enforce_enc
        $sp_enforce_enc = false;
        try {
            $sp_enforce_enc = $this->config->get('eidlogin', $siteRootPageId . '/sp_enforce_enc');
        } catch (\Exception $e) {
            $this->logger->debug('sp_enforce_enc is not set');
        }
        // build the settings
        $settings = [
            'strict' => true,
            'debug' => false,
            'security' => [
                'wantNameId' => true,
                'wantAssertionsEncrypted' => $sp_enforce_enc,
                'wantAssertionsSigned' => true,
                'wantXMLValidation' => !$skipXmlValidation,
                'authnRequestsSigned' => true,
                'signMetadata' => true,
                'requestedAuthnContext' => false,
                'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
                'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                'encryption_algorithm' => 'http://www.w3.org/2009/xmlenc11#aes256-gcm',
            ],
            'sp' => [
                'entityId' => $this->config->get('eidlogin', $siteRootPageId . '/sp_entity_id'),
                'assertionConsumerService' => [
                    'url' => $acsUrl,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'x509cert' => Utils::formatCert($this->config->get('eidlogin', $siteRootPageId . '/sp_cert_act')),
                'privateKey' => Utils::formatPrivateKey($this->config->get('eidlogin', $siteRootPageId . '/sp_key_act')),
                'x509certEnc' => Utils::formatCert($this->config->get('eidlogin', $siteRootPageId . '/sp_cert_act_enc')),
                'privateKeyEnc' => Utils::formatPrivateKey($this->config->get('eidlogin', $siteRootPageId . '/sp_key_act_enc')),
            ],
            'idp' => [
                'entityId' => $this->config->get('eidlogin', $siteRootPageId . '/idp_entity_id'),
                'singleSignOnService' => [
                    'url' => $this->config->get('eidlogin', $siteRootPageId . '/idp_sso_url'),
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509certMulti' => [
                    'signing' => [
                        0 => Utils::formatCert($this->config->get('eidlogin', $siteRootPageId . '/idp_cert_sign')),
                    ],
                    'encryption' => [
                        0 => Utils::formatCert($this->config->get('eidlogin', $siteRootPageId . '/idp_cert_enc')),
                    ]
                ],
            ],
            'alg' => [
                'signing' => [
                    //TODO remove rsa-sha256 2022
                    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
                    'http://www.w3.org/2007/05/xmldsig-more#sha224-rsa-MGF1',
                    'http://www.w3.org/2007/05/xmldsig-more#sha256-rsa-MGF1',
                    'http://www.w3.org/2007/05/xmldsig-more#sha384-rsa-MGF1',
                    'http://www.w3.org/2007/05/xmldsig-more#sha512-rsa-MGF1',
                ],
                'encryption' => [
                    'key' => [
                        'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p'
                    ],
                    'data' => [
                        'http://www.w3.org/2009/xmlenc11#aes128-gcm',
                        'http://www.w3.org/2009/xmlenc11#aes192-gcm',
                        'http://www.w3.org/2009/xmlenc11#aes256-gcm'
                    ]
                ],
            ],
            'authnReqExt' => []
        ];

        // set prepared certs if present
        try {
            $settings['sp']['x509certNew'] = Utils::formatCert($this->config->get('eidlogin', $siteRootPageId . '/sp_cert_new'));
            $settings['sp']['x509certNewEnc'] = Utils::formatCert($this->config->get('eidlogin', $siteRootPageId . '/sp_cert_new_enc'));
        } catch (\Exception $e) {
        }

        // set tr03130 stuff if present
        if ($idp_ext_tr03130 != '') {
            // add AuthnRequestExtension
            $settings['authnReqExt']['tr03130'] = $idp_ext_tr03130;
            // no signed assertion
            $settings['security']['wantAssertionsSigned'] = false;
            // signature of message is checked outside of php-saml
            $settings['security']['wantMessagesSigned'] = false;
        }

        return $settings;
    }
}
