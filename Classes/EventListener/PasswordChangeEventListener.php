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

use Ecsec\Eidlogin\Domain\Repository\FrontendUserRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent;

/**
 * This will unset the tx_eidlogin_disablepwlogin for an fe user if the pw is changed.
 */
class PasswordChangeEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var FrontendUserRepository */
    private $frontendUserRepository;

    /**
     * @param FrontendUserRepository $frontendUserRepository
     */
    public function __construct(
        FrontendUserRepository $frontendUserRepository
    ) {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    public function __invoke(PasswordChangeEvent $event): void
    {
        $this->logger->debug('eidlogin - PasswordChangeEventListener - __invoke ; enter');
        if ($GLOBALS['TSFE']->fe_user->user['tx_eidlogin_disablepwlogin'] !== 0) {
            $uid = $event->getUser()['uid'];
            $this->logger->info('eidlogin - PasswordChangeEventListener - __invoke ; unset tx_eidlogin_disablepwlogin for fe user uid ' . $uid);
            $this->frontendUserRepository->setDisablePwLogin($uid, 0);
        }
    }
}
