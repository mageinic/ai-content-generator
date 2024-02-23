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
    "jquery",
    "Magento_Ui/js/modal/alert",
    "mage/translate",
    "jquery/ui"
], function ($, alert, $t) {
    "use strict";

    $.widget('mage.keyValidate', {
        options: {
            ajaxUrl: '',
            keyElem: '',
            validateElem: ''
        },

        _create: function () {
            var self = this;
            $(this.options.validateElem).click(function (e) {
                e.preventDefault();
                var APIKey = self.element.val();
                if (APIKey) {
                    self._ajaxSubmit(APIKey);
                } else {
                    alert({
                        title: $t('Error'),
                        content: $t('Please enter OpenAI key and try again.')
                    });
                    self.element.focus();
                }
            });
        },

        _ajaxSubmit: function (key) {
            $.ajax({
                url: this.options.ajaxUrl,
                data: { token: key },
                dataType: 'json',
                type: 'post',
                cache: false,
                showLoader: true,

                /** @inheritdoc */
                success: function (result) {
                    alert({
                        title: result.status ? $t('Success') : $t('Error'),
                        content: result.content
                    });
                },

                /** @inheritdoc */
                error: function (result) {
                    alert({
                        title: $t('Error'),
                        content: result
                    });
                }
            });
        }
    });

    return $.mage.keyValidate;
});
