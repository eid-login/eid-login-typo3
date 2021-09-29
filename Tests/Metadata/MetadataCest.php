<?php

use Codeception\Util\HttpCode;
use Helper\Acceptance;

class MetadataCest
{
    /**
     * Test the eID-Login Metadata with a blank config
     *
     * @param MetadataTester $I The actor
     */
    public function testMetadataBlank(MetadataTester $I)
    {
        // set a blank config and test for 404 on metadata url
        Acceptance::setConfiguration($I, Acceptance::CONFIG_TYPE_BLANK);
        $I->amOnPage('/eidlogin?tx_eidlogin_saml%5Baction%5D=meta&tx_eidlogin_saml%5Bcontroller%5D=Saml');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    /**
     * Test the eID-Login Metadata with an activated config
     *
     * @param MetadataTester $I The actor
     */
    public function testMetadataActivated(MetadataTester $I)
    {
        // set an activated config
        Acceptance::setConfiguration($I, Acceptance::CONFIG_TYPE_ACTIVATED);
        $I->amOnPage('/eidlogin?tx_eidlogin_saml%5Baction%5D=meta&tx_eidlogin_saml%5Bcontroller%5D=Saml');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->haveHttpHeader('content-type', 'text/xml;charset=UTF-8');
        $src = $I->grabPageSource();
        $xml = simplexml_load_string($src);
        $ns = $xml->getNamespaces(true);
        $sig = $xml->children($ns['ds'])[0];
        $I->assertEquals('Signature', $sig->getName());
        $sigVal = $sig->children($ns['ds'])[1];
        $I->assertEquals('SignatureValue', $sigVal->getName());
        $ssoDesc = $xml->children($ns['md'])[0];
        $I->assertEquals('SPSSODescriptor', $ssoDesc->getName());
        $attr = $ssoDesc->attributes();
        $I->assertEquals(true, (bool)($attr['WantAssertionsSigned']));
        $I->assertEquals(true, (bool)($attr['AuthnRequestsSigned']));
        $keyDescriptor = $ssoDesc->children($ns['md'])[0];
        $I->assertEquals('KeyDescriptor', $keyDescriptor->getName());
        $attr = $keyDescriptor->attributes();
        $I->assertEquals('signing', $attr['use']);
        $keyInfo = $keyDescriptor->children($ns['ds'])[0];
        $I->assertEquals('KeyInfo', $keyInfo->getName());
        $x509Data = $keyInfo->children($ns['ds'])[0];
        $I->assertEquals('X509Data', $x509Data->getName());
        $x509Cert = $x509Data->children($ns['ds'])[0];
        $I->assertEquals('X509Certificate', $x509Cert->getName());
        $I->assertEquals(Acceptance::CERT_VALID, $x509Cert);
        $nameIdFormat = $ssoDesc->children($ns['md'])[1];
        $I->assertEquals('NameIDFormat', $nameIdFormat->getName());
        $I->assertEquals('urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified', $nameIdFormat[0]);
        $acs = $ssoDesc->children($ns['md'])[2];
        $I->assertEquals('AssertionConsumerService', $acs->getName());
        $attr = $acs->attributes();
        $I->assertEquals(Acceptance::URL_SP_ACS, $attr['Location']);
        $I->assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', $attr['Binding']);
    }
}
