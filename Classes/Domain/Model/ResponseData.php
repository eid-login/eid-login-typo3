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
 * Entity for storing the response data, used when returning from a SAML flow.
 */
class ResponseData extends AbstractEntity
{

    /** @var string the rspid to identify the response data */
    protected $rspid;
    /** @var string the data needed for continue after a SAML flow as json */
    protected $value;
    /** @var int the time of creation as timestamp*/
    protected $time;

    /**
     * ContinueData constructor.
     *
     * @param string $rspid
     * @param string $value
     * @param int $time
     */
    public function __construct($rspid = '', $value = '', $time = 0)
    {
        $this->setRspid($rspid);
        $this->setValue($value);
        $this->setTime($time);
    }

    /**
     * Sets the rspid
     *
     * @param string $rspid
     */
    public function setRspid(string $rspid): void
    {
        $this->rspid = $rspid;
    }

    /**
     * Gets the rspid
     *
     * @return string
     */
    public function getRspid(): string
    {
        return $this->rspid;
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
