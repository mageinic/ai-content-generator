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

namespace MageINIC\AIContentGenerator\Model\Import;

use Exception;
use MageINIC\AIContentGenerator\Api\Data\ContentGeneratorInterface as ContentGenerator;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory as OperationFactory;
use Magento\CatalogImportExport\Model\Import\Product\Skip;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject\IdentityGeneratorInterface as IdentityGenerator;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\MessageQueue\PublisherInterface as Publisher;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface as ProcessingError;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;

/**
 * Model Import Class Content
 */
class Content extends AbstractEntity
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC_CONTENT_GENERATOR_SYNCHRONIZATION = 'content.generator.communication';

    /**
     * EAV entity type code getter.
     */
    public const ENTITY_CODE = 'ai_content_import';

    /**
     * Column product sku.
     */
    public const COL_SKU = 'sku';

    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     *
     * @var bool
     */
    protected $logInHistory = true;

    /**
     * Permanent entity columns.
     *
     * @var string[]
     */
    protected $_permanentAttributes = [self::COL_SKU];

    /**
     * @var array
     */
    protected $validColumnNames = ['sku'];

    /**
     * @var AdapterInterface
     */
    protected AdapterInterface $connection;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resource;

    /**
     * @var ContentGenerator
     */
    protected ContentGenerator $contentGenerator;

    /**
     * @var SkuProcessor
     */
    protected SkuProcessor $skuProcessor;

    /**
     * @var Publisher
     */
    private Publisher $publisher;

    /**
     * @var OperationFactory
     */
    private OperationFactory $operationFactory;

    /**
     * @var IdentityGenerator
     */
    private IdentityGenerator $identityService;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @var array
     */
    private array $_oldSku;

    /**
     * Content Constructor.
     *
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param Data $importData
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param ProcessingError $errorAggregator
     * @param ContentGenerator $contentGenerator
     * @param Publisher $publisher
     * @param OperationFactory $operationFactory
     * @param IdentityGenerator $identityService
     * @param Json $serializer
     * @param SkuProcessor $skuProcessor
     */
    public function __construct(
        JsonHelper         $jsonHelper,
        ImportHelper       $importExportData,
        Data               $importData,
        ResourceConnection $resource,
        Helper             $resourceHelper,
        ProcessingError    $errorAggregator,
        ContentGenerator   $contentGenerator,
        Publisher          $publisher,
        OperationFactory   $operationFactory,
        IdentityGenerator  $identityService,
        Json               $serializer,
        SkuProcessor       $skuProcessor
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->contentGenerator = $contentGenerator;
        $this->publisher = $publisher;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->skuProcessor = $skuProcessor;
        $this->_initSkus();
    }

    /**
     * Initialize existent product SKUs.
     *
     * @return $this
     */
    protected function _initSkus(): static
    {
        $this->_oldSku = $this->skuProcessor->reloadOldSkus()->getOldSkus();
        return $this;
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode(): string
    {
        return static::ENTITY_CODE;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Import data
     *
     * @return bool
     * @throws Exception
     */
    protected function _importData(): bool
    {
        if (Import::BEHAVIOR_ADD_UPDATE == $this->getBehavior()) {
            $this->_saveProducts();
        }
        return true;
    }

    /**
     * Save Products
     *
     * @return bool
     * @codingStandardsIgnoreStart
     */
    protected function _saveProducts(): bool
    {
        while ($bunch = $this->_dataSourceModel->getNextUniqueBunch($this->getIds())) {
            $publishData = [];
            foreach ($bunch as $rowNum => $rowData) {
                try {
                    if (!$this->validateRow($rowData, $rowNum)) {
                        continue;
                    }
                    if ($this->getErrorAggregator()->hasToBeTerminated()) {
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                    $rowSku = $rowData[self::COL_SKU];
                    if (null === $rowSku) {
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                    if (!$this->isSkuExist($rowSku)) {
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                    $publishData[] = $rowSku;
                } catch (Skip $skip) {
                    // Product is skipped.  Go on to the next one.
                }
            }
            foreach ($bunch as $rowNum => $rowData) {
                if ($this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    unset($bunch[$rowNum]);
                }
            }

            $serializedData = $this->serializer->serialize($publishData);
            $bulkUuid = $this->identityService->generateId();
            $data = [
                'data' => [
                    'bulk_uuid' => $bulkUuid,
                    'topic_name' => 'watcher_order_attribute.update',
                    'serialized_data' => $serializedData,
                    'status' => OperationInterface::STATUS_TYPE_OPEN,
                ]
            ];

            /** @var OperationInterface $operation */
            $operation = $this->operationFactory->create($data);
            $this->publisher->publish(self::TOPIC_CONTENT_GENERATOR_SYNCHRONIZATION, $operation);
        }

        return true;
    }

    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Check if product exists for specified SKU
     *
     * @param string $sku
     * @return bool
     */
    private function isSkuExist(string $sku): bool
    {
        if ($sku !== null) {
            $sku = strtolower($sku);
            return isset($this->_oldSku[$sku]);
        }
        return false;
    }
}
