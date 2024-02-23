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
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Controller Class Validate
 */
class Validate extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'MageINIC_AIContentGenerator::content_generator';

    /**
     * @var EncoderInterface
     */
    private EncoderInterface $jsonEncoder;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var CurlFactory
     */
    private CurlFactory $curlFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * Validate Constructor
     *
     * @param Context $context
     * @param EncoderInterface $jsonEncoder
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param CurlFactory $curlFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context              $context,
        EncoderInterface     $jsonEncoder,
        EncryptorInterface   $encryptor,
        ScopeConfigInterface $scopeConfig,
        CurlFactory          $curlFactory,
        SerializerInterface  $serializer
    ) {
        parent::__construct($context);
        $this->jsonEncoder = $jsonEncoder;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->curlFactory = $curlFactory;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = ['status' => false];
        $params = $this->getRequest()->getParams();
        if ($params && $params['token']) {
            if ($params['token'] === '******') {
                $apiKey = $this->getPassword();
            } else {
                $apiKey = $params['token'];
            }
            try {
                $client = $this->curlFactory->create();
                $client->setOption(CURLOPT_TIMEOUT, 10);
                $client->setOption(CURLOPT_MAXREDIRS, 0);
                $client->setOption(CURLOPT_USERAGENT, 'Magento');
                $client->addHeader('Content-Type', 'application/json');
                $client->addHeader('Authorization', 'Bearer ' . $apiKey);
                $client->get('https://api.openai.com/v1/engines');
                $response = $this->serializer->unserialize($client->getBody());
                if (isset($response['error']) && isset($response['error']['code'])) {
                    $result = [
                        'content' => __('The OpenAI key is not valid. Please check your key and try again.')
                    ];
                } else {
                    $result = [
                        'status' => true,
                        'content' => __('The OpenAI key is valid.')
                    ];
                }
            } catch (Exception $e) {
                $result['content'] = $e->getMessage();
            }
        } else {
            $result['content'] = __('An error occurred while validating the OpenAI key.');
        }

        return $this->getResponse()->representJson($this->jsonEncoder->encode($result));
    }

    /**
     * Get Password
     *
     * @param bool $decrypt
     * @return mixed|string
     */
    public function getPassword(bool $decrypt = false): mixed
    {
        $key = $this->scopeConfig->getValue('content_generator/api/token', ScopeInterface::SCOPE_STORE);
        if ($decrypt) {
            return $this->encryptor->decrypt($key);
        }

        return $key;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
