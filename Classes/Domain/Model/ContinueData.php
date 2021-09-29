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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Entity for storing continue data, which is used when returning from a SAML flow.
 */
class ContinueData extends AbstractEntity
{

    /** @var string the reqid the continue data belongs to */
    protected $reqid;
    /** @var string the data needed for continue after a SAML flow as json */
    protected $value;
    /** @var int the time of creation as timestamp*/
    protected $time;

    /**
     * ContinueData constructor.
     *
     * @param string $reqid
     * @param string $value
     * @param int $time
     */
    public function __construct($reqid = '', $value = '', $time = 0)
    {
        $this->setReqid($reqid);
        $this->setValue($value);
        $this->setTime($time);
    }

    /**
     * Sets the reqid
     *
     * @param string $reqid
     */
    public function setReqid(string $reqid): void
    {
        $this->reqid = $reqid;
    }

    /**
     * Gets the reqid
     *
     * @return string
     */
    public function getReqid(): string
    {
        return $this->reqid;
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
