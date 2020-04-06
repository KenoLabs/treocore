/*
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

Espo.define('treo-core:views/composer/modals/install', 'views/modal',
    Dep => Dep.extend({

        template: 'treo-core:composer/modals/install',

        model: null,

        buttonList: [],

        setup() {
            Dep.prototype.setup.call(this);

            this.model = this.options.currentModel.clone();

            this.prepareAttributes();

            this.createVersionView();

            this.setupHeader();
            this.setupButtonList();
        },

        setupHeader() {
            this.header = this.translate('installModule', 'labels', 'Store');
        },

        setupButtonList() {
            this.buttonList = [
                {
                    name: 'save',
                    label: this.translate('installModule', 'labels', 'Store'),
                    style: 'primary',
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];
        },

        createVersionView() {
            this.createView('settingVersion', 'views/fields/varchar', {
                el: `${this.options.el} .field[data-name="settingVersion"]`,
                model: this.model,
                mode: 'edit',
                defs: {
                    name: 'settingVersion',
                }
            });
        },

        prepareAttributes() {
            let settingVersion = this.model.get('settingVersion');
            if (typeof settingVersion === 'string' && settingVersion.substring(0, 1) == 'v') {
                settingVersion = settingVersion.substr(1);
            }
            if (!settingVersion) {
                settingVersion = '*';
            }

            this.model.set({
                settingVersion: settingVersion
            });
        },

        actionSave() {
            this.trigger('save', {id: this.model.id, version: this.model.get('settingVersion')});
            this.close();
        }

    })
);