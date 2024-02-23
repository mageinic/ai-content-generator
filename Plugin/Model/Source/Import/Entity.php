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

namespace MageINIC\AIContentGenerator\Plugin\Model\Source\Import;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ImportExport\Model\Source\Import\Entity as ParentEntity;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin class Entity
 */
class Entity
{
    public const XML_PATH_ENABLED = 'content_generator/general/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Entity Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * After Plugin To OptionArray
     *
     * @param ParentEntity $subject
     * @param array $result
     * @return array
     */
    public function afterToOptionArray(ParentEntity $subject, array $result): array
    {
        $isEnable = $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
        if (!$isEnable) {
            foreach ($result as $key => $value) {
                if (isset($value['value']) && $value['value'] == "ai_content_import") {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }
}
