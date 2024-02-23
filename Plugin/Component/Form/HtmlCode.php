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

namespace MageINIC\AIContentGenerator\Plugin\Component\Form;

use MageINIC\AIContentGenerator\Ui\Generator;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\PageBuilder\Component\Form\HtmlCode as PageBuilderHtmlCode;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin class Entity
 */
class HtmlCode
{
    public const XML_PATH_ENABLED = 'content_generator/general/enabled';

    /**
     * @var ContextInterface
     */
    private ContextInterface $context;

    /**
     * @var ScopeConfig
     */
    private ScopeConfig $scopeConfig;

    /**
     * @var Generator
     */
    private Generator $generator;

    /**
     * @var Context
     */
    private Context $contextAction;

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * HtmlCode Constructor.
     *
     * @param ContextInterface $context
     * @param ScopeConfig $scopeConfig
     * @param Generator $generator
     * @param Context $contextAction
     * @param ProductRepository $productRepository
     */
    public function __construct(
        ContextInterface   $context,
        ScopeConfig        $scopeConfig,
        Generator          $generator,
        Context            $contextAction,
        ProductRepository  $productRepository
    ) {
        $this->context = $context;
        $this->scopeConfig = $scopeConfig;
        $this->generator = $generator;
        $this->contextAction = $contextAction;
        $this->productRepository = $productRepository;
    }

    /**
     * Around Plugin on Prepare
     *
     * @param PageBuilderHtmlCode $subject
     * @return void
     * @throws NoSuchEntityException
     */
    public function afterPrepare(PageBuilderHtmlCode $subject): void
    {
        $enabled = $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
        if ($enabled) {
            $currentUrl = $this->contextAction->getRequest()->getServer('HTTP_REFERER');
            $config = $subject->getData('config');
            $config['AIContentGeneratorEnabled'] = true;
            $config['displayPage'] = strpos($currentUrl, 'catalog/category')
                || strpos($currentUrl, 'catalog/product');
            $config['serviceUrl'] = $this->context->getUrl('content_generator/generate');

            if (strpos($currentUrl, '/id/')) {
                if (strpos($currentUrl, '/catalog/product/')) {
                    $data = $this->productRepository->getById($this->getId($currentUrl));

                    $currentProductAttribute = $this->generator->getAttributeListBySetId($data->getAttributeSetId());
                    $promptAttribute = $this->generator->getPromptAttribute($currentProductAttribute);

                    $config['attributeData'] = $promptAttribute;
                } elseif (strpos($currentUrl, '/catalog/category/')) {
                    $config['attributeData'] = '';
                }
            }

            $subject->setData('config', $config);
        }
    }

    /**
     * Retrieve Id.
     *
     * @param string $url
     * @return string
     */
    private function getId(string $url): string
    {
        $keys = parse_url($url);
        $urlData = explode("/", $keys['path'] ?? '');
        $key = array_search("id", $urlData);

        return $urlData[$key + 1];
    }
}
