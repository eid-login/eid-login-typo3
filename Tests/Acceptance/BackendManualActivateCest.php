<?php

use Helper\Acceptance;

class BackendManualActivateCest
{
    /**
     * Test the eID-Login backend manual (de)activation
     *
     * @param AcceptanceTester $I The actor
     */
    public function testEidLoginBackendManualActivate(AcceptanceTester $I)
    {
        // start with an configured non activated eID-Login
        Acceptance::setConfiguration($I, Acceptance::CONFIG_TYPE_CONFIGURED);
        // check for eID-Login stuff absence
        $I->amOnPage('/');
        $I->dontSeeElement('#eidlogin-login-logo');
        $I->loginFe();
        $I->amOnPage('/settings');
        $I->dontSeeElement('#eidlogin-settings-title');
        // got to backend and select site
        $I->loginBe();
        $I->openEidLoginBeModule();
        $I->selectOption('EidLoginSiteSelector', 'p396');
        $I->seeElement('#eidlogin-settings-manual');
        $I->see('eID-Login - p396', 'h1');
        // activate
        $I->click('#eidlogin-settings-label-activated');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        // logout
        $I->logoutBe();
        // check for eID-Login stuff resence
        $I->amOnPage('/');
        $I->seeElement('#eidlogin-login-logout');
        $I->logoutFe();
        $I->amOnPage('/');
        $I->seeElement('#eidlogin-login-logo');
        // got to backend and select site
        $I->loginBe();
        $I->openEidLoginBeModule();
        $I->selectOption('EidLoginSiteSelector', 'p396');
        $I->seeElement('#eidlogin-settings-manual');
        $I->see('eID-Login - p396', 'h1');
        // activate
        $I->click('#eidlogin-settings-label-activated');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        // logout
        $I->logoutBe();
        // check for eID-Login stuff absence
        $I->amOnPage('/');
        $I->dontSeeElement('#eidlogin-login-logo');
        $I->loginFe();
        $I->amOnPage('/settings');
        $I->dontSeeElement('#eidlogin-settings-title');
        $I->logoutFe();
    }
}
