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

use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class SchedulerTaskRepository
 */
class SchedulerTaskRepository
{
    /** @var ConnectionPool */
    private $connectionPool;

    /**
     * @param ConnectionPool $connectionPool
     */
    public function __construct(
        ConnectionPool $connectionPool
    ) {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Check if a given command is set as task
     *
     * @param string $cmd The command of the task we look for
     *
     * @return bool True if it is set
     */
    public function checkForCommandTask(string $cmd=''): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder->select('serialized_task_object');
        $queryBuilder->from('tx_scheduler_task');
        $queryBuilder->where($queryBuilder->expr()->eq('deleted', 0));
        $res = $queryBuilder->execute();
        foreach ($res->fetchAll() as $row) {
            $task = unserialize($row['serialized_task_object']);
            if (get_class($task) === 'TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask') {
                if ($task->getCommandIdentifier()===$cmd) {
                    return true;
                }
            }
        }

        return false;
    }
}
