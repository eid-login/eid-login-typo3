<?php

use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    // from TYPO3 TestingFramework
    use FrameSteps;

    const ALERT_TYPE_SUCCESS = '.alert-success';
    const ALERT_TYPE_DANGER = '.alert-danger';

    /**
     * Login the user to the frontend
     */
    public function loginFe()
    {
        $I = $this;
        $I->amOnPage('/');
        $I->fillField('user', 'user');
        $I->fillField('pass', 'userP396');
        $I->click('submit');
        // are we logged in?
        $I->waitForText('Logout');
    }

    /**
     * Logout the user from the frontend
     */
    public function logoutFe()
    {
        $I = $this;
        $I->amOnPage('/');
        $I->click('Logout');
        $I->waitForText('You have logged out.');
    }

    /**
     * Login the admin to the backend
     */
    public function loginBe()
    {
        $I = $this;
        $I->amOnPage('/typo3/');
        $I->seeElement(['class' => 'typo3-login']);
        $I->fillField('username', 'admin');
        $I->fillField('p_field', 'adminP396');
        $I->click('#t3-login-submit');
        // are we logged in?
        $I->seeElement(['class' => 'topbar']);
        // are we in english language?
        $I->see('Page');
        // maybe we need to cancel notification modal
        $I->tryToClick('button[name=cancel]');
    }

    /**
     * Logout the admin from the backend
     */
    public function logoutBe()
    {
        $I = $this;
        $I->switchToMainFrame();
        $I->click('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem');
        $I->click(' Logout ');
    }

    /**
     * Open and focus on the eID-Login backend Module
     */
    public function openEidLoginBeModule()
    {
        $I = $this;
        $I->click('#system_EidloginTxEidlogin');
        $I->switchToContentFrame();
    }

    /**
     * Perform a click on a link or a button, given by a locator.
     * Taken from TYPO3\TestingFramework\Core\Acceptance\Helper\AbstractModalDialog
     *
     * @param string $buttonLinkLocator the button title
     * @see \Codeception\Module\WebDriver::click()
     */
    public function clickButtonInDialog(string $buttonLinkLocator)
    {
        $context = 'div.t3js-modal-footer.modal-footer';
        $I = $this;
        $I->switchToIFrame();
        $I->waitForElement($context);
        $I->wait(0.5);
        $I->click($buttonLinkLocator, $context);
        $I->waitForElementNotVisible($context);
        $I->switchToContentFrame();
    }

    /**
     * See alerts of a specific type.
     * Valid types are:
     * - AcceptanceTester::ALERT_TYPE_SUCCESS
     * - AcceptanceTester::ALERT_TYPE_DANGER
     *
     * @param string $alertType The type of alert to look for
     * @param int $number The number of alerts to expect
     * @throws \Exception $e If an invalid type is given
     */
    public function seeAlerts(string $alertType, int $number = 1)
    {
        if ($alertType !== self::ALERT_TYPE_SUCCESS &&
            $alertType !== self::ALERT_TYPE_DANGER
        ) {
            throw new \Exception('invalid alert type given');
        }
        $I = $this;
        $I->switchToIFrame();
        if ($number==0) {
            $I->wait(1);
        } else {
            $I->waitForElement($alertType);
            $I->wait(1);
        }
        $I->seeNumberOfElements($alertType, $number);
        $I->switchToContentFrame();
    }

    /**
     * Close an alert of a specific type.
     * Valid types are:
     * - AcceptanceTester::ALERT_TYPE_SUCCESS
     * - AcceptanceTester::ALERT_TYPE_DANGER
     *
     * @param string $alertType The type of alert to close
     * @throws \Exception $e If an invalid type is given
     */
    public function closeAlerts(string $alertType)
    {
        if ($alertType !== self::ALERT_TYPE_SUCCESS &&
            $alertType !== self::ALERT_TYPE_DANGER
        ) {
            throw new \Exception('invalid alert type given');
        }
        $I = $this;
        $I->switchToIFrame();
        $I->waitForElement($alertType);
        $I->wait(1);
        $alerts = $I->grabMultiple('.fa-times-circle', 'class');
        for ($i=0;$i<count($alerts);$i++) {
            $I->click('.fa-times-circle');
            $I->wait(0.5);
        }
        $I->switchToContentFrame();
    }
}
