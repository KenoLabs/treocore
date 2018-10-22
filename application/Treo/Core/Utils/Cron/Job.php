<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */
declare(strict_types=1);

namespace Treo\Core\Utils\Cron;

use PDO;
use Espo\Core\CronManager;
use Espo\Core\Utils\System;
use Treo\Core\EventManager;

/**
 * Job util
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Job extends \Espo\Core\Utils\Cron\Job
{
    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param EventManager $eventManager
     *
     * @return Job
     */
    public function setEventManager(EventManager $eventManager): Job
    {
        $this->eventManager = $eventManager;

        return $this;
    }

    /**
     * @return EventManager
     */
    protected function getEventManager(): EventManager
    {
        return $this->eventManager;
    }

    /**
     * @param string $period
     */
    protected function markFailedJobsByPeriod($period)
    {
        $time = time() - $this->getConfig()->get($period);

        $pdo = $this->getEntityManager()->getPDO();

        $select
            = "
            SELECT 
              id, 
              name, 
              service_name, 
              method, 
              job.data, 
              scheduled_job_id, 
              execute_time, 
              target_id, 
              target_type, 
              pid 
            FROM 
               job
            WHERE
            `status` = '" . CronManager::RUNNING . "' AND execute_time < '" . date('Y-m-d H:i:s', $time) . "'
        ";
        $sth = $pdo->prepare($select);
        $sth->execute();

        $jobData = array();

        switch ($period) {
            case 'jobPeriod':
                while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                    if (empty($row['pid']) || !System::isProcessActive($row['pid'])) {
                        $jobData[$row['id']] = $row;
                    }
                }
                break;

            case 'jobPeriodForActiveProcess':
                while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                    $jobData[$row['id']] = $row;
                }
                break;
        }

        if (!empty($jobData)) {
            $jobQuotedIdList = [];
            foreach ($jobData as $jobId => $job) {
                $jobQuotedIdList[] = $pdo->quote($jobId);
            }

            // triggered event
            foreach ($jobData as $jobId => $job) {
                $eventData = [
                    'id'          => $jobId,
                    'status'      => CronManager::FAILED,
                    'name'        => $job['name'],
                    'serviceName' => $job['service_name'],
                    'method'      => $job['method'],
                    'data'        => $job['data'],
                    'attempts'    => 0
                ];

                $this
                    ->getEventManager()
                    ->triggered('Job', 'beforeUpdate', $eventData);
            }

            $update
                = "
                UPDATE job
                SET `status` = '" . CronManager::FAILED . "', attempts = 0
                WHERE id IN (" . implode(", ", $jobQuotedIdList) . ")
            ";

            $sth = $pdo->prepare($update);
            $sth->execute();

            $cronScheduledJob = $this->getCronScheduledJob();
            foreach ($jobData as $jobId => $job) {
                if (!empty($job['scheduled_job_id'])) {
                    $cronScheduledJob->addLogRecord(
                        $job['scheduled_job_id'],
                        CronManager::FAILED,
                        $job['execute_time'],
                        $job['target_id'],
                        $job['target_type']
                    );
                }
            }
        }
    }
}
