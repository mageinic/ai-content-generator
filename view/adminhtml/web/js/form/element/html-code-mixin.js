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
    'jquery',
    'Magento_Ui/js/form/element/textarea',
    'autoGenerateContent',
    'mage/adminhtml/wysiwyg/widget'
], function ($, Textarea, AutoContent) {
    'use strict';

    var mixin = {
        /**
         * Click event for Generate AI Data
         */
        clickGenerateAIData: function () {
            if (this.moduleIsEnabled && this.displayPageEnabled) {
                AutoContent.generateContent(this.attributeData, this.serviceUrl);
            }
        },

        /**
         * Retrieve module enabled or disabled
         *
         * @returns {boolean}
         */
        moduleIsEnabled: function () {
            return this.AIContentGeneratorEnabled;
        },

        /**
         * Retrieve Page is enabled or disabled
         *
         * @returns {boolean}
         */
        displayPageEnabled: function () {
            return this.displayPage;
        }
    };

    return function (Textarea) {
        return Textarea.extend(mixin);
    };
});
