<?php

use Helper\Acceptance;

class BackendManualCertificateCest
{
    /**
     * Test the eID-Login backend manual certificate rollover
     *
     * @param AcceptanceTester $I The actor
     */
    public function testEidLoginBackendManualCertificate(AcceptanceTester $I)
    {
        // start with an activated eID-Login
        $I->setConfiguration($I, Acceptance::CONFIG_TYPE_ACTIVATED);
        // got to backend and select site
        $I->loginBe();
        $I->openEidLoginBeModule();
        $I->selectOption('EidLoginSiteSelector', 'p396');
        $I->waitForElement('#eidlogin-settings-manual');
        $I->see('eID-Login - p396', 'h1');
        // check the default state
        $I->see('No new certificate prepared yet.', '#eidlogin-settings-manual-div-cert-new');
        $I->see('No new certificate prepared yet.', '#eidlogin-settings-manual-div-cert-new-enc');
        $I->seeElement('button[disabled]', ['id'=>'eidlogin-settings-button-rollover-execute']);
        // test cancel of prepare
        $I->click('#eidlogin-settings-button-rollover-prepare');
        $I->clickButtonInDialog('Cancel');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 0);
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 0);
        // do the prepare
        $I->click('#eidlogin-settings-button-rollover-prepare');
        $I->clickButtonInDialog('Yes');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        $I->see('...', '#eidlogin-settings-manual-div-cert-new');
        $I->see('...', '#eidlogin-settings-manual-div-cert-new-enc');
        $I->dontSeeElement('button[disabled]', ['id'=>'eidlogin-settings-button-rollover-execute']);
        // grab the new certs
        $certNew = $I->grabTextFrom('#eidlogin-settings-manual-div-cert-new');
        $certNewEnc = $I->grabTextFrom('#eidlogin-settings-manual-div-cert-new-enc');
        // test cancel of rollover
        $I->click('#eidlogin-settings-button-rollover-execute');
        $I->clickButtonInDialog('Cancel');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 0);
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 0);
        // do the rollover
        $I->click('#eidlogin-settings-button-rollover-execute');
        $I->clickButtonInDialog('Yes');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        $I->see($certNew, '#eidlogin-settings-manual-div-cert-act');
        $I->see($certNewEnc, '#eidlogin-settings-manual-div-cert-act-enc');
        $I->see('No new certificate prepared yet.', '#eidlogin-settings-manual-div-cert-new');
        $I->see('No new certificate prepared yet.', '#eidlogin-settings-manual-div-cert-new-enc');
        $I->seeElement('button[disabled]', ['id'=>'eidlogin-settings-button-rollover-execute']);
        // logout
        $I->logoutBe();
        // check for eID-Login button presence
        $I->amOnPage('/');
        $I->seeElement('#eidlogin-login-logo');

        /*
    cy.logout();
    cy.get('.eidlogin-login-button').should('be.visible')
    */
    }
}
