<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
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

namespace Espo\Core\Utils\EntityManager\Hooks;

class BasePlusType extends Base
{
    public function afterCreate($name, $params)
    {
        $activitiesEntityTypeList = $this->getConfig()->get('activitiesEntityList', []);
        $historyEntityTypeList = $this->getConfig()->get('historyEntityList', []);
        $entityTypeList = array_merge($activitiesEntityTypeList, $historyEntityTypeList);
        $entityTypeList[] = 'Task';
        $entityTypeList = array_unique($entityTypeList);

        foreach ($entityTypeList as $entityType) {
            if (!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'])) continue;

            $list = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'], []);
            if (!in_array($name, $list)) {
                $list[] = $name;
                $data = array(
                    'fields' => array(
                        'parent' => array(
                            'entityList' => $list
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityType, $data);
            }
        }

        $this->getMetadata()->save();
    }

    public function afterRemove($name)
    {
        $activitiesEntityTypeList = $this->getConfig()->get('activitiesEntityList', []);
        $historyEntityTypeList = $this->getConfig()->get('historyEntityList', []);
        $entityTypeList = array_merge($activitiesEntityTypeList, $historyEntityTypeList);
        $entityTypeList[] = 'Task';
        $entityTypeList = array_unique($entityTypeList);

        foreach ($entityTypeList as $entityType) {
            if (!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'])) continue;

            $list = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'], []);
            if (in_array($name, $list)) {
                $key = array_search($name, $list);
                unset($list[$key]);
                $list = array_values($list);
                $data = array(
                    'fields' => array(
                        'parent' => array(
                            'entityList' => $list
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityType, $data);
            }
        }

        $this->getMetadata()->save();
    }
}