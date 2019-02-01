<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

namespace Treo\Services;

use Espo\Core\CronManager;
use Espo\Core\Utils\Json;

/**
 * Composer service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Composer extends AbstractService
{
    /**
     * @var string
     */
    protected $moduleStableComposer = 'data/stable-composer.json';

    /**
     * @var string
     */
    protected $moduleComposer = 'data/composer.json';

    /**
     * Put repository file
     *
     * @param string $treoId
     */
    public static function putRepositoryFile(string $treoId): void
    {
        // prepare data
        $data = [
            'repositories' => [
                [
                    "type" => "composer",
                    "url"  => "https://packagist.treopim.com/packages.json?id=$treoId",
                ]
            ]
        ];

        file_put_contents('data/repositories.json', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * Run validate
     *
     * @return bool
     */
    public function runValidate(): bool
    {
        // create file for treo-composer.sh
        $this->filePutContents('data/composer-validate.txt', '1');

        return true;
    }

    /**
     * Run update
     *
     * @return bool
     */
    public function runUpdate(): bool
    {
        // create file for treo-composer.sh
        $this->filePutContents('data/composer-update.txt', '1');

        // set user to config
        $this->setComposerUser();

        return true;
    }

    /**
     * Cancel changes
     */
    public function cancelChanges(): void
    {
        if (file_exists($this->moduleStableComposer)) {
            file_put_contents($this->moduleComposer, file_get_contents($this->moduleStableComposer));
        }
    }

    /**
     * Update composer
     *
     * @param string $package
     * @param string $version
     */
    public function update(string $package, string $version): void
    {
        // get composer.json data
        $data = $this->getModuleComposerJson();

        // prepare data
        $data['require'] = array_merge($data['require'], [$package => $version]);

        // set composer.json data
        $this->setModuleComposerJson($data);
    }

    /**
     * Delete composer
     *
     * @param string $package
     */
    public function delete(string $package): void
    {
        // get composer.json data
        $data = $this->getModuleComposerJson();

        if (isset($data['require'][$package])) {
            unset($data['require'][$package]);
        }

        // set composer.json data
        $this->setModuleComposerJson($data);
    }

    /**
     * Get modules composer.json
     *
     * @return array
     */
    public function getModuleComposerJson(): array
    {
        if (file_exists($this->moduleComposer)) {
            $result = Json::decode(file_get_contents($this->moduleComposer), true);
        } else {
            $result = ['require' => []];

            $this->setModuleComposerJson($result);
        }

        return $result;
    }

    /**
     * Get modules stable-composer.json
     *
     * @return array
     */
    public function getModuleStableComposerJson(): array
    {
        // prepare result
        $result = [];

        if (file_exists($this->moduleStableComposer)) {
            $result = Json::decode(file_get_contents($this->moduleStableComposer), true);
        }

        return $result;
    }

    /**
     * Set modules composer.json
     *
     * @param array $data
     *
     * @return void
     */
    public function setModuleComposerJson(array $data): void
    {
        $this->filePutContents($this->moduleComposer, Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get composer diff
     *
     * @return array
     */
    public function getComposerDiff(): array
    {
        return $this->compareComposerSchemas();
    }

    /**
     * @return array
     */
    protected function compareComposerSchemas(): array
    {
        // prepare result
        $result = [
            'install' => [],
            'update'  => [],
            'delete'  => [],
        ];

        if (!file_exists($this->moduleStableComposer)) {
            // prepare data
            $data = Json::encode(['require' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $this->filePutContents($this->moduleStableComposer, $data);
        }

        // prepare data
        $composerData = $this->getModuleComposerJson();
        $composerStableData = Json::decode(file_get_contents($this->moduleStableComposer), true);
        foreach ($composerData['require'] as $package => $version) {
            if (!isset($composerStableData['require'][$package])) {
                $result['install'][] = [
                    'id'      => $this->getModuleId($package),
                    'package' => $package
                ];
            } elseif ($version != $composerStableData['require'][$package]) {
                // prepare data
                $id = $this->getModuleId($package);
                $from = $this->getModule($id)['version'];
                $result['update'][] = [
                    'id'      => $id,
                    'package' => $package,
                    'from'    => $from
                ];
            }
        }
        foreach ($composerStableData['require'] as $package => $version) {
            if (!isset($composerData['require'][$package])) {
                $result['delete'][] = [
                    'id'      => $this->getModuleId($package),
                    'package' => $package
                ];
            }
        }

        return $result;
    }

    /**
     * Get module ID
     *
     * @param string $packageId
     *
     * @return string
     */
    protected function getModuleId(string $packageId): string
    {
        // prepare result
        $result = $packageId;

        foreach ($this->getPackages() as $package) {
            if ($package['packageId'] == $packageId) {
                $result = $package['treoId'];
            }
        }

        return $result;
    }

    /**
     * Insert job to DB
     */
    protected function insertJob(): void
    {
        $jobEntity = $this->getEntityManager()->getEntity('Job');
        $jobEntity->set(
            [
                'name'        => 'run-treo-update',
                'status'      => CronManager::PENDING,
                'executeTime' => (new \DateTime())->format('Y-m-d H:i:s'),
                'serviceName' => 'Composer',
                'method'      => 'runUpdateJob',
                'data'        => ['createdById' => $this->getUser()->get('id')]
            ]
        );
        $this->getEntityManager()->saveEntity($jobEntity);
    }

    /**
     * Get module data
     *
     * @param string $id
     *
     * @return array
     */
    protected function getModule(string $id): array
    {
        return $this->getContainer()->get('metadata')->getModule($id);
    }

    /**
     * @param      $filename
     * @param      $data
     * @param int  $flags
     * @param null $context
     *
     * @return bool|int
     */
    protected function filePutContents($filename, $data, $flags = 0, $context = null)
    {
        return file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * @return array
     */
    protected function getPackages(): array
    {
        return $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Store')
            ->getPackages();
    }

    /**
     * Set current user to config for composer
     */
    protected function setComposerUser(): void
    {
        $this->getConfig()->set('composerUser', $this->getUser()->get('id'));
        $this->getConfig()->save();
    }
}
