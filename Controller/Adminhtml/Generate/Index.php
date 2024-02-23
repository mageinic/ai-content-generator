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

namespace MageINIC\AIContentGenerator\Controller\Adminhtml\Generate;

use Exception;
use MageINIC\AIContentGenerator\Model\CompletionConfig;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ResponseInterface;

/**
 * Content generator index controller
 */
class Index extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'MageINIC_AIContentGenerator::content_generator';

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var CompletionConfig
     */
    private CompletionConfig $completionConfig;

    /**
     * Index Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CompletionConfig $completionConfig
     */
    public function __construct(
        Context          $context,
        JsonFactory      $jsonFactory,
        CompletionConfig $completionConfig
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->completionConfig = $completionConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $resultPage = $this->jsonFactory->create();
        $type = $this->completionConfig->getByType($this->getRequest()->getParam('type'));
        $customPrompt = $this->getRequest()->getParam('custom_prompt');
        $attribute = $this->getRequest()->getParam('attribute');
        $prompt = $this->getRequest()->getParam('prompt');
        $generatorType = $this->getRequest()->getParam('generator_type');
        if ($type === null || !$prompt) {
            $resultPage->setData([
                'error' => __(
                    'Sorry, there was an error processing your request.
                    Please check that you have entered all required fields correctly and try again.'
                )
            ]);
            return $resultPage;
        }
        try {
            $result = $type->query([
                'prompt' => $prompt,
                'custom_prompt' => $customPrompt,
                'product_attribute' => $attribute,
                'generator_type' => $generatorType
            ]);
        } catch (Exception $e) {
            $resultPage->setData(['error' => $e->getMessage()]);
            return $resultPage;
        }
        $resultPage->setData([
            'result' => $result,
            'type' => $this->getRequest()->getParam('type')
        ]);

        return $resultPage;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
