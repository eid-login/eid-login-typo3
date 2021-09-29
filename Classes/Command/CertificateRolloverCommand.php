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
namespace Ecsec\Eidlogin\Command;

use Ecsec\Eidlogin\Domain\Repository\BackendUserRepository;
use Ecsec\Eidlogin\Service\SiteInfoService;
use Ecsec\Eidlogin\Service\SslService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Certificate rollover command, Prepare or execute certificate rollover before current certificates expire.
 * To be scheduled once a day.
 */
class CertificateRolloverCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var int */
    public const KEYROLLOVER_PREPARE_FAILED = 1;
    /** @var int */
    public const KEYROLLOVER_EXECUTE_FAILED = 2;

    /** @var BackendUserRepository */
    private $beUserRepo;
    /** @var SiteInfoService */
    private $siteInfoService;
    /** @var SslService */
    private $sslService;

    public function __construct(
        BackendUserRepository $beUserRepo,
        SiteInfoService $siteInfoService,
        SslService $sslService
    ) {
        $this->beUserRepo = $beUserRepo;
        $this->siteInfoService = $siteInfoService;
        $this->sslService = $sslService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Prepare or execute certificate rollover before current certificates expire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('eidlogin CertRolloverCommand - START');
        // iterate over sites
        $siteInfos = $this->siteInfoService->getSiteInfos();
        foreach ($siteInfos as $siteInfo) {
            $site = $siteInfo->getSite();
            $siteRootPageId = $siteInfo->getSite()->getRootPageId();
            $this->logger->info('Processing site ' . $siteInfo->getSite()->getIdentifier() . ' (' . $siteRootPageId . ') ############## ...');
            try {
                $this->logger->info('Certificate Job checking the dates of the actual certificate ...');
                $now = new \DateTimeImmutable();
                try {
                    $actDates = $this->sslService->getActDates($siteRootPageId);
                } catch (\Exception $e) {
                    $this->logger->info('No certificate act dates found for siteRootPageId ' . $siteRootPageId);
                    continue;
                }
                $remainingVaildIntervall = $actDates[SslService::DATES_VALID_TO]->diff($now);
                $this->logger->info('Certificate remains valid for ' . $remainingVaildIntervall->days . ' days.');
                $prepSpan = 56; // 2 months
                $exeSpan = 28; // 1 month
                // are we in key rollover execute span?
                if ($remainingVaildIntervall->days <= $exeSpan) {
                    $this->logger->info('Certificate Job is in key rollover execute span ...');
                    try {
                        $this->sslService->rollover($siteRootPageId);
                        $this->informOnRollover($site->getIdentifier());
                        $this->logger->info('Certificate Job rollover executed!');
                    } catch (\Exception $e) {
                        $this->logger->error('Certificate Job: failed to make rollover to new cert: ' . $e->getMessage());
                        $this->informOnError($site->getIdentifier(), self::KEYROLLOVER_EXECUTE_FAILED, $actDates[SslService::DATES_VALID_TO], $e->getMessage());
                        $this->logger->info('Certificate Job: informed admins!');
                    }
                    continue;
                }
                // are we in key rollover prepare span?
                if ($remainingVaildIntervall->days <= $prepSpan) {
                    $this->logger->info('Certificate Job is in key rollover prepare span ...');
                    if ($this->sslService->checkNewCertPresent($siteRootPageId)) {
                        $this->logger->info('Certificate Job: new cert already present!');
                        continue;
                    }
                    try {
                        $this->sslService->createNewCert($siteRootPageId);
                        $this->logger->info('Certificate Job: new cert created ...');
                        $validTo = $actDates[SslService::DATES_VALID_TO];
                        $activateOn = $validTo->modify('-' . $exeSpan . ' days');
                        $this->informOnNewCert($site->getIdentifier(), $validTo, $activateOn);
                        $this->logger->info('Certificate Job: admins informed!');
                    } catch (\Exception $e) {
                        $this->logger->error('Certificate Job: failed to create a new cert for siteRootPageId ' . $siteRootPageId . ': ' . $e->getMessage());
                        $this->informOnError($site->getIdentifier(), self::KEYROLLOVER_EXECUTE_FAILED, $actDates[SslService::DATES_VALID_TO], $e->getMessage());
                        $this->logger->info('Certificate Job: informed admins!');
                    }
                    continue;
                }
                // nothing to do
                $this->logger->info('Certificate Job is NOT in key rollover prepare or execute span ... Nothing to do!');
            } catch (\Exception $e) {
                $this->logger->error('Certificate Job failed: ' . $e->getMessage());
                $this->informOnError($site->getIdentifier(), self::KEYROLLOVER_EXECUTE_FAILED, $actDates[SslService::DATES_VALID_TO], $e->getMessage());
                $this->logger->info('Certificate Job: informed admins!');
            }
        }
        $this->logger->info('eidlogin CertRolloverCommand - END');

        return 0;
    }

    /**
     * Inform admins about a certificate rollover via mail.
     *
     * @param string $siteIdentifier The siteIdentifier for which to inform
     */
    private function informOnRollover(string $siteIdentifier): void
    {
        $adminMails = $this->beUserRepo->getAdminEmailAdresses();
        foreach ($adminMails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail = GeneralUtility::makeInstance(MailMessage::class);
                $body =  'The TYPO3 eID-Login extension executed a Certificate Rollover for the site ' . $siteIdentifier . ".\n\n";
                $body .= "The old certificates have been saved in the database.\n\n";
                $body .= "Please check, if the certificates are correctly used in communication with the Identity Provider!\n\n";
                $mail
                ->from($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
                ->to($email)
                ->subject('TYPO3 eID Login Certificate Rollover executed for site ' . $siteIdentifier)
                ->text($body)
                ->send();
            }
        }
    }

    /**
     * Inform admins about a new certificate via mail.
     *
     * @param string $siteIdentifier The siteIdentifier for which to inform
     * @param \DateTimeImmutable $validTo Date until the actual certificate is valid
     * @param \DateTimeImmutable $activateOn Date when the new certificate will be activated
     */
    private function informOnNewCert(string $siteIdentifier, \DateTimeImmutable $validTo, \DateTimeImmutable $activateOn): void
    {
        $adminMails = $this->beUserRepo->getAdminEmailAdresses();
        foreach ($adminMails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $body =  'The TYPO3 eID-Login extension prepared a Certificate Rollover by creating new certificates for the site ' . $siteIdentifier . ".\n\n";
                $body .= 'The currently used certificates are about to expire and remain valid until ' . $validTo->format('Y-m-d') . '. The new certificates will be activated on ' . $activateOn->format('Y-m-d') . ".\n\n";
                $body .= "Please check, if you need to add the new certificates manually at the used Identity Provider!\n\n";
                $body .= 'If you want to trigger the rollover manually at some earlier time, you can do this in the settings of the eID-Login extension.';
                $mail = GeneralUtility::makeInstance(MailMessage::class);
                $mail
                ->from($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
                ->to($email)
                ->subject('TYPO3 eID Login Certificate Rollover prepared for site ' . $siteIdentifier)
                ->text($body)
                ->send();
            }
        }
    }

    /**
     * Inform admins about an error via mail and remove the job.
     *
     * @param string $siteIdentifier SiteIdentifier Identifier of the site
     * @param int $errorType Type of error
     * @param \DateTimeImmutable $validTo ValidTo date of the actual certificate
     * @param string $msg The message of the Exception
     */
    private function informOnError(string $siteIdentifier, int $errorType, \DateTimeImmutable $validTo, string $msg=''): void
    {
        $adminMails = $this->beUserRepo->getAdminEmailAdresses();
        foreach ($adminMails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $body =  'The certificate cronjob of the TYPO3 eID-Login extension got an error for site ' . $siteIdentifier . ":\n\n";
                if (self::KEYROLLOVER_PREPARE_FAILED === $errorType) {
                    $body .=  "Failed to create new certificates.\n\n";
                }
                if (self::KEYROLLOVER_EXECUTE_FAILED === $errorType) {
                    $body .=  "Failed to activate new certificates.\n\n";
                }
                if (!empty(trim($msg))) {
                    $body .= 'Exception Message: ' . $msg . "\n\n";
                }
                $body .= "This certificates shall be used for signing and encryption of SAML data.\n";
                $body .= 'The currently used certificates are valid until ' . $validTo->format('Y-m-d') . ". \n";
                $body .= "Please check the logs of your TYPO3 instance!\n";
                $body .= 'You can create and activate new certificates manually in the settings of the eID-Login extension when the cause of the error is fixed.';
                $mail = GeneralUtility::makeInstance(MailMessage::class);
                $mail
                ->from($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
                ->to($email)
                ->subject('TYPO3 eID Login Certificate Rollover error for site ' . $siteIdentifier)
                ->text($body)
                ->send();
            }
        }

        return;
    }
}
