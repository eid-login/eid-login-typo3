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
 * An attribute bound to a fe or be user via an eid.
 */
class Attribute extends AbstractEntity
{
    /**
     * The id of the eid the attribute belongs to
     *
     * @var Eid
     **/
    protected $eid;

    /**
     * The name of the attribute
     *
     * @var string
     **/
    protected $name;

    /**
     * The value of the attribute
     *
     * @var string
     **/
    protected $value;

    /**
     * Attribute constructor.
     *
     * @param Eid $eid
     * @param string $name
     * @param string $value
     */
    public function __construct($eid = null, $name = null, $value = null)
    {
        if (!is_null($eid)) {
            $this->setEid($eid);
        }
        if (!is_null($name)) {
            $this->setName($name);
        }
        if (!is_null($value)) {
            $this->setValue($value);
        }
    }

    /**
     * Sets the eid
     *
     * @param Eid $eid
     */
    public function setEid(Eid $eid): void
    {
        $this->eid = $eid;
    }

    /**
     * Gets the eid
     *
     * @return Eid
     */
    public function getEid(): Eid
    {
        return $this->eid;
    }

    /**
     * Gets the name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
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
     * Sets the value
     *
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
