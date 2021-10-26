<?php

use Facebook\WebDriver\WebDriverElement;
use Helper\Acceptance;

class BackendWizardCest
{
    /**
     * Test the eID-Login backend index page and wizard logic
     *
     * @param AcceptanceTester $I The actor
     */
    public function testEidLoginBackendIndexAndWizard(AcceptanceTester $I)
    {
        // start with a blank eID-Login
        Acceptance::setConfiguration($I, Acceptance::CONFIG_TYPE_BLANK);
        // got to backend
        $I->loginBe();
        $I->openEidLoginBeModule();
        // check for matrix with site p396 present without being setup already
        $I->seeElement('#eidlogin-settings-matrix');
        $I->see('p396', '//*[@id="eidlogin-settings-matrix"]/tbody/tr[last()]/td[1]');
        $I->see('No', '//*[@id="eidlogin-settings-matrix"]/tbody/tr[last()]/td[7]/span');
        // check action menu
        $I->selectOption('EidLoginSiteSelector', 'p396');
        $I->seeElement('#eidlogin-settings-wizard-panel-1');
        $I->see('eID-Login - p396', 'h1');
        $I->selectOption('EidLoginSiteSelector', 'Select Site ...');
        $I->seeElement('#eidlogin-settings-matrix');
        // check info panel
        $I->click('#eidlogin-settings-button-help');
        $I->seeElement('#eidlogin-settings-wizard-panel-help');
        $I->click('#eidlogin-settings-button-close-help');
        $I->dontSeeElement('#eidlogin-settings-wizard-panel-help');
        $I->click('#eidlogin-settings-button-help');
        $I->seeElement('#eidlogin-settings-wizard-panel-help');
        $I->click('#eidlogin-settings-button-help');
        $I->dontSeeElement('#eidlogin-settings-wizard-panel-help');
        // open wizard for site p396
        // $I->click('Open Settings');
        $I->click('#eidlogin-opensettings_p396');
        $I->seeElement('#eidlogin-settings-wizard-panel-1');
        // check wizard navigation steps
        $I->seeElement('#eidlogin-settings-wizard-step-3', ['class'=>'step disabled']);
        $I->seeElement('#eidlogin-settings-wizard-step-4', ['class'=>'step disabled']);
        $I->click('#eidlogin-settings-wizard-step-2');
        $I->seeElement('#eidlogin-settings-wizard-panel-2');
        $I->seeElement('#eidlogin-settings-wizard-step-3', ['class'=>'step']);
        $I->seeElement('#eidlogin-settings-wizard-step-4', ['class'=>'step disabled']);
        $I->click('#eidlogin-settings-wizard-step-1');
        $I->seeElement('#eidlogin-settings-wizard-panel-1');
        $I->seeElement('#eidlogin-settings-wizard-step-3', ['class'=>'step disabled']);
        $I->seeElement('#eidlogin-settings-wizard-step-4', ['class'=>'step disabled']);
        // configure IDP with skid metadataurl and check fetched values
        $skidMetadata = $I->fetchSkidMetaData();
        $I->click('#eidlogin-settings-button-next-2');
        $I->seeElement('#eidlogin-settings-wizard-panel-2');
        $I->seeInField('#eidlogin-settings-form-wizard-sp_entity_id', Acceptance::getBaseUrl());
        $I->dontSeeCheckboxIsChecked('#eidlogin-settings-form-wizard-sp_enforce_enc');
        $I->fillField('#eidlogin-settings-form-wizard-idp_metadata_url', Acceptance::URL_SKID_META);
        $I->click('#eidlogin-settings-button-toggleidp');
        $I->seeElement('#eidlogin-settings-wizard-panel-idp_settings');
        $I->waitForElementChange('#eidlogin-settings-form-wizard-idp_entity_id', function (WebDriverElement $el) use ($skidMetadata) {
            return $el->getAttribute('value') === $skidMetadata['idp_entity_id'];
        }, 10);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_sso_url', $skidMetadata['idp_sso_url']);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_cert_sign', $skidMetadata['idp_cert_sign']);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_cert_enc', $skidMetadata['idp_cert_enc']);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_ext_tr03130', '');
        $I->click('#eidlogin-settings-button-toggleidp');
        $I->dontSeeElement('#eidlogin-settings-wizard-panel-idp_settings');
        // fetched values should saved as they are valid and result in correct SP entityId
        $I->click('#eidlogin-settings-button-next-3');
        $I->waitForElementVisible('#eidlogin-settings-wizard-panel-3', 10);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        $I->see(Acceptance::getBaseUrl(), '#eidlogin-settings-wizard-display-sp_entity_id');
        $I->see(Acceptance::getAcsUrl(), '#eidlogin-settings-wizard-display-sp_acs_url');
        $I->see(Acceptance::getMetaUrl(), '#eidlogin-settings-wizard-display-sp_meta_url');
        // test back buttons
        $I->click('#eidlogin-settings-button-back-2');
        $I->waitForElementVisible('#eidlogin-settings-wizard-panel-2', 10);
        $I->click('#eidlogin-settings-button-back-1');
        $I->waitForElementVisible('#eidlogin-settings-wizard-panel-1', 10);
        // use SKID Button, should result in valid and saved values also
        $I->click('#eidlogin-settings-button-select-skid');
        $I->waitForElementVisible('#eidlogin-settings-wizard-panel-3', 10);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        // go back and check fetched values
        $I->click('#eidlogin-settings-button-back-2');
        $I->waitForElementVisible('#eidlogin-settings-wizard-panel-2', 10);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_entity_id', $skidMetadata['idp_entity_id']);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_sso_url', $skidMetadata['idp_sso_url']);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_cert_sign', $skidMetadata['idp_cert_sign']);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_cert_enc', $skidMetadata['idp_cert_enc']);
        $I->seeInField('#eidlogin-settings-form-wizard-idp_ext_tr03130', '');
        // proceed to last step with first aborting then confirming the security question
        $I->click('#eidlogin-settings-button-next-3');
        $I->waitForElementVisible('#eidlogin-settings-wizard-panel-3', 10);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        $I->click('#eidlogin-settings-button-next-4');
        $I->clickButtonInDialog('Cancel');
        $I->click('#eidlogin-settings-button-next-4');
        $I->clickButtonInDialog('Next');
        $I->waitForElementVisible('#eidlogin-settings-wizard-panel-4', 10);
        $I->closeAlerts(AcceptanceTester::ALERT_TYPE_SUCCESS);
        // finish configuration, should show manual mode
        $I->click('#eidlogin-settings-button-finish');
        $I->waitForElementVisible('#eidlogin-settings-manual', 10);
        // logout
        $I->logoutBe();
    }
}
