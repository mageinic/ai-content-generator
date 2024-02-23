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

namespace MageINIC\AIContentGenerator\Ui;

use Exception;
use MageINIC\AIContentGenerator\Model\CompletionConfig;
use Magento\Catalog\Api\ProductAttributeManagementInterface as ProductAttributeManagement;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Container;
use Magento\Framework\App\Request\Http;

/**
 * Ui generator class
 */
class Generator extends Container
{
    public const XML_PATH_ENABLED = 'content_generator/general/enabled';
    public const XML_PATH_BASE_URL = 'content_generator/api/end_point_url';
    public const XML_PATH_TOKEN = 'content_generator/api/token';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var Attribute
     */
    private Attribute $eavAttribute;

    /**
     * @var Entity
     */
    private Entity $entity;

    /**
     * @var ProductAttributeManagement
     */
    private ProductAttributeManagement $productAttributeManagement;

    /**
     * @var Http
     */
    private Http $request;

    /**
     * @param ContextInterface $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Registry $registry
     * @param Entity $entity
     * @param Attribute $eavAttribute
     * @param ProductAttributeManagement $productAttributeManagement
     * @param Http $request
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface           $context,
        ScopeConfigInterface       $scopeConfig,
        Registry                   $registry,
        Entity                     $entity,
        Attribute                  $eavAttribute,
        ProductAttributeManagement $productAttributeManagement,
        Http                       $request,
        array                      $components = [],
        array                      $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->scopeConfig = $scopeConfig;
        $this->coreRegistry = $registry;
        $this->eavAttribute = $eavAttribute;
        $this->entity = $entity;
        $this->productAttributeManagement = $productAttributeManagement;
        $this->request = $request;
    }

    /**
     * Get Admin Configuration Values
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        $config = parent::getConfiguration();
        try {
            $enable = $this->scopeConfig->getValue(self::XML_PATH_ENABLED);
            if ($enable) {
                $endpointURL = $this->scopeConfig->getValue(self::XML_PATH_BASE_URL);
                $token = $this->scopeConfig->getValue(self::XML_PATH_TOKEN);
                $productObject = $this->coreRegistry->registry('product');
                $promptAttribute = '';
                if ($productObject) {
                    $currentProductAttribute = $this->getAttributeListBySetId($productObject->getAttributeSetId());
                    $promptAttribute = $this->getPromptAttribute($currentProductAttribute);
                }
                $generatorType = '';
                $fullActionName = $this->request->getFullActionName();
                if ($fullActionName == "catalog_category_edit" || $fullActionName == "catalog_category_add") {
                    $generatorType = 'category';
                }
                if ($fullActionName == "catalog_product_edit" || $fullActionName == "catalog_product_new") {
                    $generatorType = 'product';
                }

                /** @var CompletionConfig $completionConfig */
                $completionConfig = $this->getData('completion_config');

                return array_merge(
                    $config,
                    $completionConfig->getConfig(),
                    [
                        'settings' => [
                            'serviceUrl' => $this->context->getUrl('content_generator/generate'),
                            'attribute' => $promptAttribute,
                            'generatorType' => $generatorType
                        ],
                        'enable' => $enable && $endpointURL && $token,
                    ]
                );
            } else {
                return $config;
            }
        } catch (Exception $e) {
            return $config;
        }
    }

    /**
     * Get Admin Configuration Field Value.
     *
     * @param string $path
     * @param string $scopeType
     * @param int|null|string $scopeCode
     * @return mixed
     */
    public function getValue(
        string     $path,
        string     $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int|string $scopeCode = null
    ): mixed
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Get Product Attributes from the attribute set.
     *
     * @param int $attributeSetId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAttributeListBySetId(int $attributeSetId): array
    {
        try {
            $attributeCollection = $this->productAttributeManagement->getAttributes($attributeSetId);
            $data = [];
            foreach ($attributeCollection as $attribute) {
                $data[] = $attribute->getAttributeId();
            }

            return $data;
        } catch (NoSuchEntityException $exception) {
            throw new NoSuchEntityException(__($exception->getMessage()));
        }
    }

    /**
     * Get Prompt Attribute
     *
     * @param array $productAttribute
     * @return array
     */
    public function getPromptAttribute(array $productAttribute): array
    {
        $attributeCollection = $this->eavAttribute->getCollection();
        $attributeCollection->addFieldToFilter(
            'entity_type_id',
            $this->entity->setType('catalog_product')->getTypeId()
        );
        $attributeCollection->getSelect()->joinInner(
            ['cea' => "catalog_eav_attribute"],
            "cea.attribute_id = main_table.attribute_id && cea.used_in_ai = 1",
            ['cea.used_in_ai']
        );
        $promptAttribute = [];
        if ($attributeCollection) {
            foreach ($attributeCollection as $attribute) {
                if (in_array($attribute->getAttributeId(), $productAttribute)) {
                    $promptAttribute[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
                }
            }
        }

        return $promptAttribute;
    }
}
