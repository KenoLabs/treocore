/*
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

Espo.define('treo-core:views/modals/select-entity-and-records', 'views/modals/select-records',
    Dep => Dep.extend({

        template: 'treo-core:modals/select-entity-and-records',

        setup() {
            Dep.prototype.setup.call(this);

            this.buttonList.find(button => button.name === 'select').label = 'applyRelation';
            this.header = this.getLanguage().translate(this.options.headerLabel, 'massActions', 'Global');

            this.waitForView('entitySelect');
            this.createEntitySelectView();
        },

        createEntitySelectView() {
            let options = [];
            let translatedOptions = {};
            this.model.get('foreignEntities').forEach(entityDefs => {
                options.push(entityDefs.entity);
                translatedOptions[entityDefs.entity] = this.translate(entityDefs.link, 'links', this.model.get('mainEntity'));
            });

            this.createView('entitySelect', 'views/fields/enum', {
                model: this.model,
                el: `${this.options.el} .entity-container .field[data-name="entitySelect"]`,
                defs: {
                    name: 'entitySelect',
                    params: {
                        options: options,
                        translatedOptions: translatedOptions
                    }
                },
                mode: 'edit'
            }, view => {
                view.render();
                view.listenTo(view.model, 'change:entitySelect', model => {
                    this.reloadList(model.get('entitySelect'));
                });
            });
        },

        reloadList(entity) {
            if (!entity) {
                return;
            }
            this.collection.name = this.collection.urlRoot = this.collection.url = entity;
            this.loadList();
        }
    })
);

