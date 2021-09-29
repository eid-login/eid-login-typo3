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
namespace Ecsec\Eidlogin\Domain\Repository;

use Ecsec\Eidlogin\Domain\Model\Attribute;

/**
 * Class AttributeRepository
 */
class AttributeRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Find the attributes of an eid
     *
     * @param string $eidUid
     * @return Attribute[] $attributes The attributes of an eid
     */
    public function findByEidUid(string $eidUid)
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('eid', $eidUid))
            ->execute()
            ->toArray();
    }
}
