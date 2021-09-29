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
namespace Ecsec\Eidlogin\Service;

use Ecsec\Eidlogin\Domain\Model\Message;
use Ecsec\Eidlogin\Domain\Repository\MessageRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class MessageService
 */
class MessageService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var MessageRepository */
    private $messageRepository;
    /** @var PersistenceManager */
    private $persistenceManager;

    /** @var string */
    public const PARAM_MSGID = 'eidmsgid';

    /**
     * @param MessageRepository $messageRepository
     * @param PersistenceManager $persistenceManager
     */
    public function __construct(
        MessageRepository $messageRepository,
        PersistenceManager $persistenceManager
    ) {
        $this->messageRepository = $messageRepository;
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Save a message to db.
     *
     * @param int $severity The severity of the message
     * @param string $msg The message itselg
     * @return string The id of the message used for storage
     */
    public function saveMessage(int $severity=AbstractMessage::OK, string $msg=''): string
    {
        $msgid = 'eidmsg_' . bin2hex(random_bytes(8));
        $message = new Message(
            $msgid,
            $severity,
            $msg,
            time()
        );
        $this->persistenceManager->add($message);
        $this->persistenceManager->persistAll();
        $this->logger->debug('created msg ' . print_r($message, true));

        return $msgid;
    }

    /**
     * Get a message
     *
     * @param string $msgid The id of the message to get
     *
     * @return Message The message
     */
    public function getMessage(string $msgid): ?Message
    {
        return $this->messageRepository->findByMsgid($msgid);
    }

    /**
     * Delete a message
     *
     * @param Message $msg The message to delete
     */
    public function deleteMessage(Message $msg): void
    {
        $this->persistenceManager->remove($msg);
        $this->persistenceManager->persistAll();
    }
}
