<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Services;

use Espo\Core\Exceptions\NotFound;
use Espo\ORM\Entity;

/**
 * Service Attachment
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class Attachment extends \Espo\Services\Attachment
{
    /**
     * @var array
     */
    protected $inlineAttachmentFieldTypeList = ['text', 'wysiwyg', 'wysiwygMultiLang'];

    /**
     * @param Entity $entity
     * @return mixed
     * @throws NotFound
     */
    public function moveFromTmp(Entity $entity)
    {
        if ($entity->get("storageFilePath")) {
            return true;
        }

        if (!file_exists($entity->get('tmpPath'))) {
            throw new NotFound("File not found");
        }

        return $this->getRepository()->moveFromTmp($entity);
    }

    /**
     * @param Entity $entity
     * @return bool
     * @throws NotFound
     */
    public function moveMultipleAttachment(Entity $entity)
    {
        if ($this->moveFromTmp($entity)) {
            return $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
        }

        return false;
    }
}
