<?php

use Helper\Acceptance;

class FrontendCest
{
    /**
     * Test the eID-Login frontend
     *
     * @param AcceptanceTester $I The actor
     */
    public function testEidLoginFrontend(AcceptanceTester $I)
    {
        // start with an activated eID-Login
        Acceptance::setConfiguration($I, Acceptance::CONFIG_TYPE_ACTIVATED);
        // check for eID-Login button presence
        $I->amOnPage('/');
        $I->seeElement('#eidlogin-login-logo');
        // login
        $I->loginFe();
        // got to settings and check for absence of eID connection
        $I->amOnPage('/settings');
        $I->seeElement('#eidlogin-settings-create');
        // fake a present cloudID for skID
        $I->click('#eidlogin-settings-create-a');
        $I->waitForElement('#skIdS_modal');
        $I->waitForJS(" localStorage.setItem('" . Acceptance::CLOUDID_KEY_KEY . "', '" . Acceptance::CLOUDID_KEY_VAL . "'); localStorage.setItem('" . Acceptance::CLOUDID_DATA_KEY . "', '" . Acceptance::CLOUDID_DATA_VAL . "'); return true;");
        // abort (next time the cloudID can be used), and test for message
        $I->click('Abort');
        $I->waitForText('Creation of eID connection aborted');
        // start creation of eID connection
        $I->click('#eidlogin-settings-create-a');
        $I->waitForElement('#skIdS_identity-decrypt-password-input');
        $I->fillField('#skIdS_identity-decrypt-password-input', Acceptance::CLOUDID_PIN);
        $I->click('#skIdS_identity-decrypt-button');
        $I->waitForText('eID connection has been created');
        // check for eID, attributes and no leftovers
        $I->seeInDatabase('tx_eidlogin_domain_model_eid', ['feuid' => '1']);
        $I->seeInDatabase('tx_eidlogin_domain_model_attribute', ['name' => 'http://www.skidentity.de/att/eIdentifier']);
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_continuedata');
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_message');
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_responsedata');
        // test login with eID abortion
        $I->logoutFe();
        $I->amOnPage('/');
        $I->click('#eidlogin-login-login a');
        $I->waitForElement('#skIdS_modal');
        $I->click('Abort');
        $I->waitForText('Login with eID aborted');
        // test login with eID
        $I->amOnPage('/');
        $I->click('#eidlogin-login-logo a');
        $I->waitForElement('#skIdS_modal');
        $I->waitForElement('#skIdS_identity-decrypt-password-input');
        $I->fillField('#skIdS_identity-decrypt-password-input', Acceptance::CLOUDID_PIN);
        $I->click('#skIdS_identity-decrypt-button');
        $I->waitForText('Logout');
        // disable pw based login
        $I->amOnPage('/settings');
        $I->click('#eidlogin-settings-disable_pw_login-checkbox');
        $I->waitForText('Setting has been saved');
        $I->logoutFe();
        $I->fillField('user', 'user');
        $I->fillField('pass', 'userP396');
        $I->click('submit');
        $I->waitForText('Password based login is disabled for this account');
        $I->seeInDatabase('fe_users', ['uid' => '1', 'tx_eidlogin_disablepwlogin' => '1']);
        // enable pw based login
        $I->click('#eidlogin-login-logo a');
        $I->waitForElement('#skIdS_modal');
        $I->waitForElement('#skIdS_identity-decrypt-password-input');
        $I->fillField('#skIdS_identity-decrypt-password-input', Acceptance::CLOUDID_PIN);
        $I->click('#skIdS_identity-decrypt-button');
        $I->waitForText('Logout');
        $I->amOnPage('/settings');
        $I->click('#eidlogin-settings-disable_pw_login-checkbox');
        $I->waitForText('Setting has been saved');
        $I->seeInDatabase('fe_users', ['uid' => '1', 'tx_eidlogin_disablepwlogin' => '0']);
        $I->logoutFe();
        $I->loginFe();
        // delete eID connection
        $I->amOnPage('/settings');
        $I->click('#eidlogin-settings-delete a');
        $I->waitForText('eID connection has been deleted');
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_attribute');
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_eid');
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_continuedata');
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_message');
        $I->seeNumRecords(0, 'tx_eidlogin_domain_model_responsedata');
        // logout
        $I->logoutFe();
    }
}
