<?php
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

namespace MageINIC\AIContentGenerator\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Admin Block System Config Class Temperature
 */
class Temperature extends Field
{
    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $html = $element->getElementHtml();
        $html .= '<script type="text/javascript">
            require(["jquery", "jquery/ui"], function($) {
                $(document).ready(function(){
                    var customField = $("#' . $element->getHtmlId() . '");
                    $("#' . $element->getHtmlId() . '").after("<div id=\'viewValue\' style=\'position: absolute; font-size:14px;\'></div>");
                    $("#viewValue").html(customField.val());
                    var min = 0;
                    var max = 1.0;
                    var step = 0.01; //increment/decrement step
                    var value = customField.val();
                    var slider = $("<div style=\'margin: 5px 0 0 30px; cursor: pointer;\'>").appendTo(customField.parent());
                    slider.slider({
                        range: "min",
                        min: min,
                        max: max,
                        step: step,
                        value: value,
                        slide: function(event, ui) {
                            customField.val(ui.value);
                            $("#viewValue").html(ui.value);
                        }
                    });
                    customField.change(function() {
                        slider.slider("value", this.value);
                        $("#viewValue").html(this.value);
                    });
                });
            });
        </script>';

        return $html;
    }
}
