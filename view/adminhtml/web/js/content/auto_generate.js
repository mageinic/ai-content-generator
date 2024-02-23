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
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'mage/backend/notification',
    'uiRegistry'
], function ($, translate, modal) {
    'use strict';

    return {
        /**
         * Bind generate content click action
         *
         * @param attribute
         * @param serviceUrl
         */
        generateContent: function (attribute, serviceUrl) {
            var self = this;
            var usedAttributeData = attribute;
            var generatorType = self.getPage();

            var usedAttribute = '';
            if (usedAttributeData) {
                $.each(usedAttributeData, function (key, value) {
                    usedAttribute += translate(value) + ', ';
                });
            }

            usedAttribute = usedAttribute.slice(0, -2);

            var promptTitle;
            if (generatorType === "category") {
                promptTitle = translate('We will use <strong>Category Name</strong> to send as a prompt.');
            } else {
                promptTitle = (usedAttribute) ?
                    translate('We will use product <strong>Name</strong>'
                        + ' & select product attributes (%1) value to send as a prompt.')
                        .replace('%1', '<strong>' + usedAttribute + '</strong>') :
                    translate('We will use product <strong>Name</strong> to send as a prompt.');
            }

            var dynamicContent = '<div class="need-prompt">' +
                '<p class="used-attribute">' + promptTitle + '</p>' +
                '<p class="prompt-text">' + translate('Would you like to add your own prompt?') + '</p>' +
                '</div>' +
                '<div class="custom-prompt-text" style="display: none;">' +
                '<textarea name="custom-prompt" id="custom-prompt" rows="7" cols="55" ' +
                'placeholder="' + translate('Write your own prompt...') + '"></textarea>' +
                '<div class="error-message" style="display: none;"></div>' +
                '</div>';

            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: 'Popup Title',
                modalClass: 'content-generator-modal',
                buttons: [{
                    text: translate('Yes'),
                    class: 'action-primary action-accept need-prompt-yes',
                    click: function () {
                        $('.need-prompt').hide();
                        $('.need-prompt-yes').hide();
                        $('.need-prompt-no').hide();
                        $('.need-prompt-submit').show();
                        $('.need-prompt-back').show();
                        $('.custom-prompt-text').show();
                    }
                }, {
                    text: translate('Continue'),
                    class: 'action-primary action-accept need-prompt-no',
                    click: function () {
                        self.getContent(usedAttributeData, serviceUrl);
                        this.closeModal();
                    }
                }, {
                    text: translate('Submit'),
                    class: 'action-secondary action-accept need-prompt-submit',
                    click: function () {
                        var customPrompt = $('#custom-prompt');
                        var errorMessage = $('.error-message');
                        if (!customPrompt.val().replace(/\s/g, '').length) {
                            customPrompt.addClass("error");
                            errorMessage.html(translate('This is a required field.')).show();
                            customPrompt.focus();
                            return;
                        }
                        customPrompt.removeClass("error");
                        errorMessage.html('').hide();
                        self.getContent(attribute, serviceUrl, customPrompt.val());
                        customPrompt.val('');
                        this.closeModal();
                    }
                }, {
                    text: translate('Back'),
                    class: 'action-primary action-accept need-prompt-back',
                    click: function () {
                        $('.need-prompt').show();
                        $('.need-prompt-yes').show();
                        $('.need-prompt-no').show();
                        $('.need-prompt-submit').hide();
                        $('.need-prompt-back').hide();
                        $('.custom-prompt-text').hide();
                        $('#custom-prompt').val('');
                    }
                }]
            };
            var aiGeneratePopup = $('#ai-generate-popup');
            if (aiGeneratePopup.length !== 0) {
                aiGeneratePopup.remove();
            }
            $('body').append('<div id="ai-generate-popup">' + dynamicContent + '</div>');
            aiGeneratePopup = $('#ai-generate-popup');
            var popup = modal(options, aiGeneratePopup);
            popup.openModal();
            $(".need-prompt-submit").hide();
            $('.need-prompt-back').hide();
            aiGeneratePopup.on('modalclosed', function () {
                aiGeneratePopup.remove();
            });
        },

        /**
         * Call API to get data
         *
         * @param attribute
         * @param serviceUrl
         * @param customPrompt
         */
        getContent: function (attribute = null, serviceUrl = null, customPrompt = null) {

            var self = this;
            var promptAttribute = self.getPromptAttribute(attribute);
            if (self.getPage() === "category") {
                var prompt = (customPrompt) ? customPrompt : $("input[name='name").val();
            }
            if (self.getPage() === "product") {
                var prompt = (customPrompt) ? customPrompt : $("input[name='product[name]']").val();
            }
            var isCustomPrompt = !!(customPrompt);

            try {
                if (prompt) {
                    $('body').notification('clear');
                    var payload = {
                        'form_key': FORM_KEY,
                        'prompt': prompt,
                        'custom_prompt': isCustomPrompt,
                        'generator_type': self.getPage(),
                        'attribute': promptAttribute,
                        'type': 'description'
                    };

                    $.ajax({
                        url: serviceUrl,
                        data: payload,
                        type: 'POST',
                        showLoader: true
                    }).done(function (response) {
                        if (response.error) {
                            $('body').notification('clear').notification('add', {
                                error: true,
                                message: translate(response.error),

                                /**
                                 * @param {*} message
                                 */
                                insertMethod: function (message) {
                                    $('.page-main-actions').after(message);
                                }
                            });
                            $("html, body").animate({scrollTop: 0}, "slow");
                        } else if (response.type === 'description') {
                            $("textarea[name='html']").val(response.result).trigger('change');
                        }

                    }).fail(function (xhr, ajaxOptions, thrownError) {
                        $('body').notification('clear').notification('add', {
                            error: true,
                            message: translate(thrownError),

                            /**
                             * @param {*} message
                             */
                            insertMethod: function (message) {
                                $('.page-main-actions').after(message);
                            }
                        });
                        $("html, body").animate({scrollTop: 0}, "slow");
                    });
                } else {
                    if (self.getPage() === "category") {
                        var message = (!customPrompt) ?
                            translate('The category name field is required.'
                                + ' Please enter a category name and try again.') :
                            translate('Sorry, We can\'t find your prompt value. Please try again.');
                    }

                    if (self.getPage() === "product") {
                        var message = (!customPrompt) ?
                            translate('The product name field is required.'
                                +' Please enter a product name and try again.') :
                            translate('Sorry, We can\'t find your prompt value. Please try again.');
                    }

                    $('body').notification('clear').notification('add', {
                        error: true,
                        message: message,

                        /**
                         * @param {*} message
                         */
                        insertMethod: function (message) {
                            $('.page-main-actions').after(message);
                        }
                    });
                    $("html, body").animate({scrollTop: 0}, "slow");
                }
            } catch (ex) {
                $('body').notification('clear').notification('add', {
                    error: true,
                    message: translate('Sorry, there has been an error processing your request. ' +
                        'Please try again or check the browser console.'),

                    /**
                     * @param {*} message
                     */
                    insertMethod: function (message) {
                        $('.page-main-actions').after(message);
                    }
                }).trigger('processStop');
            }
        },

        /**
         * Get current product prompt attribute data
         *
         * @param attribute
         * @returns {string}
         */
        getPromptAttribute: function (attribute = null) {

            var promptAttribute = '';
            var usedAttributeInPrompt = attribute;
            if (usedAttributeInPrompt) {
                $.each(usedAttributeInPrompt, function (key, value) {
                    if ($(`input[name='product[${key}]']`).length > 0) {
                        var selectedVal = $(`input[name='product[${key}]']`).val();
                    } else if ($(`input[name='${key}']`).length > 0) {
                        var selectedVal = $(`input[name='${key}']`).val();
                    } else if ($(`select[name='product[${key}]']`).length > 0) {
                        var selectedVal = $(`select[name='product[${key}]']`)
                            .find(':selected').toArray().map(item => item.text).join(', ');
                    } else if ($(`select[name='${key}']`).length > 0) {
                        var selectedVal = $(`select[name='${key}']`)
                            .find(':selected').toArray().map(item => item.text).join(', ');
                    } else {
                        var selectedVal = '';
                    }
                    if (selectedVal) {
                        promptAttribute += value + ': ' + selectedVal + ' and ';
                    }
                });
                promptAttribute = promptAttribute.slice(0, -5);
            }

            return promptAttribute;
        },

        /**
         * Insert content to target instance.
         *
         * @param {Object} element
         * @param {*} value
         */
        insertAtCursor: function (element, value) {
            var sel, startPos, endPos, scrollTop;

            if ('selection' in document) {
                //For browsers like Internet Explorer
                element.focus();
                sel = document.selection.createRange();
                sel.text = value;
                element.focus();
            } else if (element.selectionStart || element.selectionStart === '0') { //eslint-disable-line eqeqeq
                //For browsers like Firefox and Webkit based
                startPos = element.selectionStart;
                endPos = element.selectionEnd;
                scrollTop = element.scrollTop;
                element.val(element.value.substring(0, startPos) + value +
                    element.value.substring(startPos, endPos) + element.value.substring(endPos, element.value.length));
                element.focus();
                element.selectionStart = startPos + value.length;
                element.selectionEnd = startPos + value.length + element.value.substring(startPos, endPos).length;
                element.scrollTop = scrollTop;
            } else {
                element.val(value);
                element.focus();
            }
        },

        /**
         * Retrieve Page Name
         *
         * @returns {string}
         */
        getPage: function () {
            if (window.location.href.includes('catalog/product/')) {
                return 'product';
            } else if (window.location.href.includes('catalog/category/')) {
                return 'category'
            }
        }
    }
});
