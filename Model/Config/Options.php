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

namespace MageINIC\AIContentGenerator\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Model Config Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        $options[] = [
            'value' => 'meta_title',
            'label' => 'Meta Title'
        ];
        $options[] = [
            'value' => 'meta_keywords',
            'label' => 'Meta Keywords'
        ];
        $options[] = [
            'value' => 'meta_description',
            'label' => 'Meta Description'
        ];
        $options[] = [
            'value' => 'short_description',
            'label' => 'Short Description'
        ];
        $options[] = [
            'value' => 'description',
            'label' => 'Description'
        ];

        return $options;
    }
}
