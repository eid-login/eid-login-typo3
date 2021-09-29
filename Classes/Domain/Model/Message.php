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
namespace Ecsec\Eidlogin\Domain\Model;

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Entity for storing message data, which is used when returning from a SAML flow.
 */
class Message extends AbstractEntity
{

    /** @var string the msgid */
    protected $msgid;
    /** @var int the severity */
    protected $severity;
    /** @var string the msg itself */
    protected $value;
    /** @var int the time of creation as timestamp*/
    protected $time;

    /**
     * Message constructor.
     *
     * @param string $msgid
     * @param int $severity
     * @param string $value
     * @param int $time
     */
    public function __construct($msgid = '', $severity = 0, $value = '', $time = 0)
    {
        $this->setMsgid($msgid);
        $this->setSeverity($severity);
        $this->setValue($value);
        $this->setTime($time);
    }

    /**
     * Sets the msgid
     *
     * @param string $msgid
     */
    public function setMsgid(string $msgid): void
    {
        $this->msgid = $msgid;
    }

    /**
     * Gets the msgid
     *
     * @return string
     */
    public function getMsgid(): string
    {
        return $this->msgid;
    }

    /**
     * Sets the severity
     *
     * @param int $severity
     */
    public function setSeverity(int $severity): void
    {
        $this->severity = MathUtility::forceIntegerInRange($severity, AbstractMessage::NOTICE, AbstractMessage::ERROR, AbstractMessage::OK);
    }

    /**
     * Gets the severity
     *
     * @return int
     */
    public function getSeverity(): int
    {
        return $this->severity;
    }

    /**
     * Sets the value
     *
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * Gets the value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the time
     *
     * @param int $time
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }

    /**
     * Gets the time
     *
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }
}
