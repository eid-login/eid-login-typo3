<?php

use Helper\Acceptance;

class BackendManualEnterCest
{
    /**
     * Test the eID-Login backend manual configuration and error validation
     *
     * @param AcceptanceTester $I The actor
     */
    public function testEidLoginBackendManualEnter(AcceptanceTester $I)
    {
        // start with an activated eID-Login
        Acceptance::setConfiguration($I, Acceptance::CONFIG_TYPE_ACTIVATED);
        // got to backend and select site
        $I->loginBe();
        $I->openEidLoginBeModule();
        $I->selectOption('EidLoginSiteSelector', 'p396');
        $I->seeElement('#eidlogin-settings-manual');
        $I->see('eID-Login - p396', 'h1');
        // test cancel button of save security question
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Cancel');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 0);
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 0);
        // empty form results in 4 errors
        $I->clearField('#eidlogin-settings-form-manual-sp_entity_id');
        $I->clearField('#eidlogin-settings-form-manual-idp_entity_id');
        $I->clearField('#eidlogin-settings-form-manual-idp_sso_url');
        $I->clearField('#eidlogin-settings-form-manual-idp_cert_sign');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 4);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling sp_entity_id correctly
        $I->fillField('#eidlogin-settings-form-manual-sp_entity_id', Acceptance::URL_SP_BASE);
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 3);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_entity_id correctly
        $I->fillField('#eidlogin-settings-form-manual-idp_entity_id', 'foobar');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 2);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_sso_url with invalid url
        $I->fillField('#eidlogin-settings-form-manual-idp_sso_url', 'foobar');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 2);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_sso_url with non TLS url
        $I->fillField('#eidlogin-settings-form-manual-idp_sso_url', 'http://foobar.com');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 2);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_sso_url correctly
        $I->fillField('#eidlogin-settings-form-manual-idp_sso_url', 'https://foobar.com');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_cert_sign and idp_cert_enc incorrectly (no cert)
        $I->fillField('#eidlogin-settings-form-manual-idp_cert_sign', 'foobar');
        $I->fillField('#eidlogin-settings-form-manual-idp_cert_enc', 'foobar');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 2);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_cert_sign and idp_cert_enc incorrectly (insufficent pub key length)
        $I->clearField('#eidlogin-settings-form-manual-idp_cert_sign');
        $I->fillField('#eidlogin-settings-form-manual-idp_cert_sign', Acceptance::CERT_INVALID_INSUFFICENT_PUBKEY_LENGTH);
        $I->clearField('#eidlogin-settings-form-manual-idp_cert_enc');
        $I->fillField('#eidlogin-settings-form-manual-idp_cert_enc', Acceptance::CERT_INVALID_INSUFFICENT_PUBKEY_LENGTH);
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 2);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_cert_sign and idp_cert_enc correctly
        $I->clearField('#eidlogin-settings-form-manual-idp_cert_sign');
        $I->fillField('#eidlogin-settings-form-manual-idp_cert_sign', Acceptance::CERT_VALID);
        $I->clearField('#eidlogin-settings-form-manual-idp_cert_enc');
        $I->fillField('#eidlogin-settings-form-manual-idp_cert_enc', Acceptance::CERT_VALID);
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        // filling idp_ext_tr03130 incorrectly with missing idp_cert_enc and missing sp_enforce_enc
        $I->fillField('#eidlogin-settings-form-manual-idp_ext_tr03130', 'foobar');
        $I->uncheckOption('#eidlogin-settings-form-manual-sp_enforce_enc');
        $I->clearField('#eidlogin-settings-form-manual-idp_cert_enc');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 3);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_DANGER);
        // filling idp_ext_tr03130 correctly (well, it is xml)
        $I->clickWithLeftButton('#eidlogin-settings-form-manual-sp_enforce_enc');
        $I->fillField('#eidlogin-settings-form-manual-idp_cert_enc', Acceptance::CERT_VALID);
        $I->fillField('#eidlogin-settings-form-manual-idp_ext_tr03130', '<foo>bar</foo>');
        $I->click('#eidlogin-settings-button-manual-save');
        $I->clickButtonInDialog('Save and delete eID connections');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        // logout
        $I->logoutBe();
        // check for eID-Login button presence
        $I->amOnPage('/');
        $I->seeElement('#eidlogin-login-logo');
    }
}
