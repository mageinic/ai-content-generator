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

namespace MageINIC\AIContentGenerator\Model;

use MageINIC\AIContentGenerator\Api\Data\ContentGeneratorInterface;
use Magento\AsynchronousOperations\Model\OperationStatusValidator;
use Magento\Framework\DataObject;

/**
 * Class Consumer encapsulates methods for Operation Model Object
 */
class Consumer extends DataObject implements ContentGeneratorInterface
{
    /**
     * @var OperationStatusValidator
     */
    private $operationStatusValidator;

    /**
     * Operation constructor.
     *
     * @param OperationStatusValidator $operationStatusValidator
     * @param array $data
     */
    public function __construct(
        OperationStatusValidator $operationStatusValidator,
        array                    $data = []
    ) {
        $this->operationStatusValidator = $operationStatusValidator;
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getBulkUuid()
    {
        return $this->getData(self::BULK_ID);
    }

    /**
     * @inheritDoc
     */
    public function setBulkUuid($bulkId)
    {
        return $this->setData(self::BULK_ID, $bulkId);
    }

    /**
     * @inheritDoc
     */
    public function getTopicName()
    {
        return $this->getData(self::TOPIC_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setTopicName($topic)
    {
        return $this->setData(self::TOPIC_NAME, $topic);
    }

    /**
     * @inheritDoc
     */
    public function getSerializedData()
    {
        return $this->getData(self::SERIALIZED_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setSerializedData($serializedData)
    {
        return $this->setData(self::SERIALIZED_DATA, $serializedData);
    }

    /**
     * @inheritDoc
     */
    public function getResultSerializedData()
    {
        return $this->getData(self::RESULT_SERIALIZED_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setResultSerializedData($resultSerializedData)
    {
        return $this->setData(self::RESULT_SERIALIZED_DATA, $resultSerializedData);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        $this->operationStatusValidator->validate($status);
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getResultMessage()
    {
        return $this->getData(self::RESULT_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setResultMessage($resultMessage)
    {
        return $this->setData(self::RESULT_MESSAGE, $resultMessage);
    }

    /**
     * @inheritDoc
     */
    public function getErrorCode()
    {
        return $this->getData(self::ERROR_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setErrorCode($errorCode)
    {
        return $this->setData(self::ERROR_CODE, $errorCode);
    }

    /**
     * @inheritdoc
     */
    public function getSku(): ?string
    {
        return $this->getData('sku');
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku): static
    {
        return $this->setData('sku', $sku);
    }
}
