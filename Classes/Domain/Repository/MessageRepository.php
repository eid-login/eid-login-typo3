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

use Ecsec\Eidlogin\Domain\Model\Message;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Class MessageRepository
 */
class MessageRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Find message by the msgid
     *
     * @param string $msgid
     * @return Message The array of messages
     */
    public function findByMsgid(string $msgid): ?Message
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('msgid', $msgid))
            ->execute()
            ->getFirst();
    }

    /**
     * Find messages older than a given limit.
     *
     * @param int $limit The limit for removal as timestamp
     * @return QueryResult The query result
     */
    public function findOlderThan(int $limit): QueryResult
    {
        $query = $this->createQuery();
        return $query->matching($query->lessThan('time', $limit))
                ->execute();
    }
}
