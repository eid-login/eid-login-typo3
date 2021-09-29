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
namespace Ecsec\Eidlogin\EventListener;

use Ecsec\Eidlogin\Service\MessageService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\FrontendLogin\Event\LoginConfirmedEvent;

/**
 * This will prevent an pw based login, if this option is set for a fe user.
 */
class LoginConfirmedEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var MessageService */
    private $messageService;
    /** @var UriBuilder */
    private $uriBuilder;
    /** @var LocalizationUtility */
    private $l10nUtil;

    /**
     * @param MessageService $messageService
     * @param UriBuilder $uriBuilder
     * @param LocalizationUtility $l10nUtil
     */
    public function __construct(
        MessageService $messageService,
        UriBuilder $uriBuilder,
        LocalizationUtility $l10nUtil
    ) {
        $this->messageService = $messageService;
        $this->uriBuilder = $uriBuilder;
        $this->l10nUtil = $l10nUtil;
    }

    public function __invoke(LoginConfirmedEvent $event): void
    {
        $this->logger->debug('eidlogin - LoginConfirmedEventListener - __invoke ; enter');
        if ($GLOBALS['TSFE']->fe_user->user['tx_eidlogin_disablepwlogin'] !== 0) {
            $this->logger->info('eidlogin - LoginConfirmedEventListener - __invoke ; prevent pw based login for fe user uid ' . $GLOBALS['TSFE']->fe_user->user['uid']);
            $_POST['logintype'] = 'logout';
            $GLOBALS['TSFE']->fe_user->start();
            $msg = $this->l10nUtil->translate('fe_msg_err_pwlogin_disabled', 'eidlogin', []);
            $msgId = $this->messageService->saveMessage(AbstractMessage::ERROR, $msg);
            $this->uriBuilder->reset();
            $this->uriBuilder->setTargetPageUid($GLOBALS['TSFE']->id);
            $this->uriBuilder->setCreateAbsoluteUri(true);
            $redirectUrl = $this->uriBuilder->buildFrontendUri();
            $redirectUrl = preg_replace('/&cHash=.*$/', '', $redirectUrl);
            $redirectUrl .= '?' . MessageService::PARAM_MSGID . '=' . $msgId;
            $this->logger->debug('eidlogin - LoginConfirmedEventListener - __invoke ; will redirect to ' . $redirectUrl);
            header('Location: ' . $redirectUrl);
            die();
        }
    }
}
