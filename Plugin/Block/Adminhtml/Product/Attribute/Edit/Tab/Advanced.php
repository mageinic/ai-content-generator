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

namespace MageINIC\AIContentGenerator\Plugin\Block\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Advanced as ParentAdvanced;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Registry;

/**
 * class Advanced for AI content generator attribute
 */
class Advanced
{
    /**
     * @var Yesno
     */
    private Yesno $yesNo;

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * Advanced Constructor
     *
     * @param Yesno $yesNo
     * @param Registry $registry
     */
    public function __construct(
        Yesno    $yesNo,
        Registry $registry
    ) {
        $this->yesNo = $yesNo;
        $this->coreRegistry = $registry;
    }

    /**
     * Add Use in AI content generator attribute
     *
     * @param ParentAdvanced $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetFormHtml(ParentAdvanced $subject, callable $proceed): mixed
    {
        $attributeObject = $this->coreRegistry->registry('entity_attribute');
        $yesNoSource = $this->yesNo->toOptionArray();
        $form = $subject->getForm();
        $fieldset = $form->getElement('advanced_fieldset');
        $fieldset->addField(
            'used_in_ai',
            'select',
            [
                'name' => 'used_in_ai',
                'label' => __('Use in AI content generator'),
                'title' => __('Use in AI content generator'),
                'values' => $yesNoSource,
                'note' => __(
                    'Select "Yes" to add this attribute to the AI content generator in the product edit page.'
                )
            ]
        );
        $form->setValues($attributeObject->getData());

        return $proceed();
    }
}
