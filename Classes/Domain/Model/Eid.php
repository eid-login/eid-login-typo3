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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Entity representing an Eid, can be bound to a fe or be user.
 */
class Eid extends AbstractEntity
{
    const NO_FE_AND_BE = 'can not set feuid and beuid for an eid at the same time';

    /**
     * The value of the eid
     *
     * @var string
     **/
    protected $eidvalue = '';

    /**
     * The eidAttributes of the eid
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Attribute>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $attributes;

    /**
     * The uid of an associated frontend user
     *
     * @var int
     **/
    protected $feuid;

    /**
     * The uid of an associated backend user
     *
     * @var int
     **/
    protected $beuid;

    /**
     * Eid constructor.
     *
     * @param string $eidvalue
     * @param ObjectStorage<Attribute> $attributes
     * @param ?int $feuid
     * @param ?int $beuid
     */
    /*
    public function __construct($eidvalue = null, $attributes = null, $feuid = null, $beuid = null)
    {
        if (!is_null($eidvalue)) {
            $this->setEidvalue($eidvalue);
        }
        if (is_null($attributes)) {
            $this->setAttributes(new ObjectStorage());
        } else {
            $this->setAttributes($attributes);
        }
        if (!is_null($feuid) && !is_null($beuid)) {
            throw new \Exception(self::NO_FE_AND_BE);
        }
        if (!is_null($feuid)) {
            $this->setFeuid($feuid);
        }
        if (!is_null($beuid)) {
            $this->setBeuid($beuid);
        }
    }
    */

    /**
     * Sets the eidvalue
     *
     * @param string $eidvalue
     */
    public function setEidvalue(string $eidvalue): void
    {
        $this->eidvalue = $eidvalue;
    }

    /**
     * Gets the eidvalue
     *
     * @return string
     */
    public function getEidvalue(): string
    {
        return $this->eidvalue;
    }

    /**
     * Sets the attributes
     *
     * @param ObjectStorage<Attribute> $attributes
     */
    public function setAttributes(ObjectStorage $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Gets the attributes
     *
     * @return ObjectStorage<Attribute>
     */
    public function getAttributes(): ObjectStorage
    {
        return $this->attributes;
    }

    /**
     * Add an attribute
     *
     * @param Attribute $attribute
     */
    public function addAttribute($attribute): void
    {
        $this->attributes->attach($attribute);
    }

    /**
     * Sets the feuid
     *
     * @param int $feuid
     */
    public function setFeuid(int $feuid): void
    {
        if (!is_null($this->beuid)) {
            throw new \Exception(self::NO_FE_AND_BE);
        }
        $this->feuid = $feuid;
    }

    /**
     * Gets the feuid
     *
     * @return int
     */
    public function getFeuid(): ?int
    {
        return $this->feuid;
    }

    /**
     * Sets the beuid
     *
     * @param int $beuid
     */
    public function setBeuid(int $beuid): void
    {
        if (!is_null($this->feuid)) {
            throw new \Exception(self::NO_FE_AND_BE);
        }
        $this->beuid = $beuid;
    }

    /**
     * Gets the beuid
     *
     * @return int
     */
    public function getBeuid(): ?int
    {
        return $this->beuid;
    }
}
