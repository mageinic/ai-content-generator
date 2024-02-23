/**
 * MageINIC
 * Copyright (C) 2023 MageINIC <support@mageinic.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category MageINIC
 * @package MageINIC_AIContentGenerator
 * @copyright Copyright (c) 2023 MageINIC (https://www.mageinic.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author MageINIC <support@mageinic.com>
 */

define([
    'uiComponent',
    'mageUtils',
    'uiRegistry',
    'uiLayout',
    'Magento_Ui/js/lib/spinner',
    'underscore'
], function (Component, utils, Registry, layout, loader, _) {
    'use strict';

    return Component.extend({
        defaults: {
            targets: {},
            settings: {},
            enable: {}
        },

        /**
         * function initialize
         */
        initialize: function () {
            this._super();
            if (this.enable) {
                for (const [key, group] of Object.entries(this.targets)) {
                    this.containerReady(group.container)
                        .then((component) => {
                            this.createComponents(key, group, component);
                        });
                }
            }
        },

        /**
         *
         * @param component
         * @returns {Promise}
         */
        containerReady: function (component) {
            return new Promise((resolve) => {
                Registry.get(component, (component) => {
                    component.elems.subscribe(() => {
                        resolve(component);
                    });
                });
            });
        },

        /**
         *
         * @param type
         * @param groupConfig
         * @param parent
         */
        createComponents: function (type, groupConfig, parent) {
            const settings = {
                ...this.settings,
                ...groupConfig,
                type
            };

            const modalTemplate = {
                parent: this.name,
                name: type + '-modal',
                component: 'Magento_Ui/js/modal/modal-component',
                config: {
                    isTemplate: true,
                    settings,
                    loader: loader.get('product_form.product_form')
                }
            };

            const buttonConfig = {
                parent: parent.name,
                name: 'ai-button-' + type,
                component: groupConfig.component,
                config: {
                    settings,
                    modalName: this.name + '.' + type + '-modal',
                    loader: loader.get('product_form.product_form')
                }
            };

            layout([buttonConfig, modalTemplate]);
        }
    });
});
