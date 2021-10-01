<?php

use Helper\Acceptance;

class BackendManualResetCest
{
    /**
     * Test the eID-Login backend manual configuration reset
     *
     * @param AcceptanceTester $I The actor
     */
    public function testEidLoginBackendManualReset(AcceptanceTester $I)
    {
        // start with an activated eID-Login
        Acceptance::setConfiguration($I, Acceptance::CONFIG_TYPE_ACTIVATED);
        // got to backend and select site
        $I->loginBe();
        $I->openEidLoginBeModule();
        $I->selectOption('EidLoginSiteSelector', 'p396');
        $I->waitForElement('#eidlogin-settings-manual');
        $I->see('eID-Login - p396', 'h1');
        // test cancel button of reset security question
        $I->click('#eidlogin-settings-button-reset');
        $I->clickButtonInDialog('Cancel');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 0);
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_DANGER, 0);
        // test reset
        $I->click('#eidlogin-settings-button-reset');
        $I->clickButtonInDialog('Yes');
        $I->seeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS, 1);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        // logout
        $I->logoutBe();
        // check for eID-Login button absence
        $I->amOnPage('/');
        $I->dontseeElement('#eidlogin-login-logo');
    }
}
