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

use Ecsec\Eidlogin\Domain\Repository\AttributeRepository;
use Ecsec\Eidlogin\Domain\Repository\ContinueDataRepository;
use Ecsec\Eidlogin\Domain\Repository\EidRepository;
use Ecsec\Eidlogin\Domain\Repository\MessageRepository;
use Ecsec\Eidlogin\Domain\Repository\ResponseDataRepository;
use Ecsec\Eidlogin\Service\EidService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Database cleaning command removing orphaned/old database entries.
 * To be scheduled every five minutes.
 */
class CleanDbCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var AttributeRepository */
    private $attributeRepository;
    /** @var EidRepository */
    private $eidRepository;
    /** @var ContinueDataRepository */
    private $continueDataRepository;
    /** @var MessageRepository */
    private $messageRepository;
    /** @var ResponseDataRepository */
    private $responseDataRepository;
    /** @var PersistenceManager */
    private $persistenceManager;

    public function __construct(
        AttributeRepository $attributeRepository,
        EidRepository $eidRepository,
        ContinueDataRepository $continueDataRepository,
        MessageRepository $messageRepository,
        ResponseDataRepository $responseDataRepository,
        PersistenceManager $persistenceManager
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->eidRepository = $eidRepository;
        $this->continueDataRepository = $continueDataRepository;
        $this->messageRepository = $messageRepository;
        $this->responseDataRepository = $responseDataRepository;
        $this->persistenceManager = $persistenceManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Remove orphaned/old entries from the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('eidlogin CleanDbCommand - START');
        // delete older than 5 minutes
        $limit = time()-300;
        $this->logger->info('eidlogin CleanDbCommand - delete all ContinueData older than ' . date(DATE_ATOM, $limit, ));
        $res = $this->continueDataRepository->findOlderThan($limit);
        foreach ($res as $continueData) {
            $this->continueDataRepository->remove($continueData);
        }
        $this->logger->info('eidlogin CleanDbCommand - delete all ResponseData older than ' . date(DATE_ATOM, $limit, ));
        $res = $this->responseDataRepository->findOlderThan($limit);
        foreach ($res as $responseData) {
            $this->responseDataRepository->remove($responseData);
        }
        $this->logger->info('eidlogin CleanDbCommand - delete all Messages older than ' . date(DATE_ATOM, $limit, ));
        $res = $this->messageRepository->findOlderThan($limit);
        foreach ($res as $message) {
            $this->messageRepository->remove($message);
        }
        $this->logger->info('eidlogin CleanDbCommand - delete all orphaned fe eids');
        $res = $this->eidRepository->findOrphaned(EidService::TYPE_FE);
        foreach ($res as $eid) {
            $this->eidRepository->remove($eid);
            $res = $this->attributeRepository->findByEidUid($eid->getUid());
            foreach ($res as $attribute) {
                $this->attributeRepository->remove($attribute);
            }
        }
        $this->logger->info('eidlogin CleanDbCommand - delete all orphaned be eids');
        $res = $this->eidRepository->findOrphaned(EidService::TYPE_BE);
        foreach ($res as $eid) {
            $this->eidRepository->remove($eid);
            $res = $this->attributeRepository->findByEidUid($eid->getUid());
            foreach ($res as $attribute) {
                $this->attributeRepository->remove($attribute);
            }
        }
        $this->persistenceManager->persistAll();
        $this->logger->info('eidlogin CleanDbCommand - END ');

        return 0;
    }
}
