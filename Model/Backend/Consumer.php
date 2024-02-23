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

declare(strict_types=1);

namespace MageINIC\AIContentGenerator\Model\Backend;

use Exception;
use MageINIC\AIContentGenerator\Model\CompletionConfig;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Catalog\Api\ProductAttributeManagementInterface as ProductAttributeManagement;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\ImportExport\Model\History;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_Adapter_Exception;

/**
 * Consumer for import content
 */
class Consumer
{
    public const XML_PATH_ENABLED = 'content_generator/general/enabled';
    public const IMPORT_FIELD = 'content_generator/general/import_field';
    public const SKU = 'sku';
    public const META_TITLE = 'meta_title';
    public const META_KEYWORDS = 'meta_keywords';
    public const META_DESCRIPTION = 'meta_description';
    public const SHORT_DESCRIPTION = 'short_description';
    public const DESCRIPTION = 'description';
    public const CSV_FILE_NAME = 'catalog_product.csv';
    public const CSV_FILE_PATH = 'importexport/' . self::CSV_FILE_NAME;
    public const IMPORT_HISTORY_DIR = 'import_history/';
    public const EMAIL_RECIPIENT_NAME = 'trans_email/ident_general/name';
    public const EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_general/email';
    public const EMAIL_TEMPLATE_IDENTIFIER = 'content_generator/general/notification_template';
    public const EMAIL_TEMPLATE_SEND_TO = 'content_generator/general/send_to';

    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $_objectManager;

    /**
     * @var OperationManagementInterface
     */
    protected OperationManagementInterface $operationManagement;

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * @var File
     */
    protected File $file;

    /**
     * @var Import
     */
    protected Import $import;

    /**
     * @var StateInterface
     */
    protected StateInterface $inlineTranslation;

    /**
     * @var TransportBuilder
     */
    protected TransportBuilder $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var CompletionConfig
     */
    private CompletionConfig $completionConfig;

    /**
     * @var WriteInterface
     */
    private WriteInterface $directory;

    /**
     * @var DateTime
     */
    private DateTime $localeDate;

    /**
     * @var History
     */
    private History $importHistoryModel;

    /**
     * @var NotifierInterface
     */
    private NotifierInterface $notifier;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

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
     * Consumer construct
     *
     * @param OperationManagementInterface $operationManagement
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param CompletionConfig $completionConfig
     * @param Filesystem $filesystem
     * @param File $file
     * @param DateTime $localeDate
     * @param History $importHistoryModel
     * @param ObjectManagerInterface $objectManager
     * @param NotifierInterface $notifier
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param Entity $entity
     * @param Attribute $eavAttribute
     * @param ProductAttributeManagement $productAttributeManagement
     * @throws FileSystemException
     */
    public function __construct(
        OperationManagementInterface $operationManagement,
        LoggerInterface              $logger,
        SerializerInterface          $serializer,
        EntityManager                $entityManager,
        CompletionConfig             $completionConfig,
        Filesystem                   $filesystem,
        File                         $file,
        DateTime                     $localeDate,
        History                      $importHistoryModel,
        ObjectManagerInterface       $objectManager,
        NotifierInterface            $notifier,
        ScopeConfigInterface         $scopeConfig,
        TransportBuilder             $transportBuilder,
        StateInterface               $inlineTranslation,
        StoreManagerInterface        $storeManager,
        ProductRepositoryInterface   $productRepository,
        Entity                       $entity,
        Attribute                    $eavAttribute,
        ProductAttributeManagement   $productAttributeManagement
    ) {
        $this->operationManagement = $operationManagement;
        $this->_objectManager = $objectManager;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->completionConfig = $completionConfig;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->importHistoryModel = $importHistoryModel;
        $this->localeDate = $localeDate;
        $this->notifier = $notifier;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->eavAttribute = $eavAttribute;
        $this->entity = $entity;
        $this->productAttributeManagement = $productAttributeManagement;
    }

    /**
     * Process
     *
     * @param OperationInterface $operation
     * @return void
     * @throws Exception
     */
    public function process(OperationInterface $operation): void
    {
        try {
            if ($this->isEnabled()) {
                $serializedData = $operation->getSerializedData();
                $data = $this->serializer->unserialize($serializedData);
                $this->execute($data);
            }
        } catch (Zend_Db_Adapter_Exception $e) {
            $this->notifier->addMajor(
                __('Error during import of the AI content generate process occurred'),
                __('Error during import of the AI content generator process occurred.
                Please check logs for details.')
            );
            $this->logger->critical("===========================");
            $this->logger->critical(
                __('Error during import of the AI content generator process occurred.
                Please check logs for details.')
            );
            $this->logger->critical($e->getMessage());
            $this->logger->info($e->getMessage());
            if ($e instanceof LockWaitException
                || $e instanceof DeadlockException
                || $e instanceof ConnectionException
            ) {
                $status = BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during AI content generate. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->notifier->addMajor(
                __('Error during import of the AI content generate process occurred'),
                __('Error during import of the AI content generator process occurred.
                Please check logs for details.')
            );
            $this->logger->critical("===========================");
            $this->logger->critical(
                __('Error during import of the AI content generator process occurred.
                Please check logs for details.')
            );
            $this->logger->critical($e->getMessage());
            $this->logger->info($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->notifier->addMajor(
                __('Error during import of the AI content generate process occurred'),
                __('Error during import of the AI content generator process occurred.
                Please check logs for details.')
            );
            $this->logger->critical("===========================");
            $this->logger->critical(
                __('Error during import of the AI content generator process occurred.
                Please check logs for details.')
            );
            $this->logger->critical($e->getMessage());
            $this->logger->info($e->getMessage());
            $status = BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $this->notifier->addMajor(
                __('Error during import of the AI content generate process occurred'),
                __('Error during import of the AI content generator process occurred.
                Please check logs for details.')
            );
            $this->logger->critical("===========================");
            $this->logger->critical(__('Sorry, something went wrong during AI content generate process.'));
            $this->logger->critical($e->getMessage());
            $this->logger->info($e->getMessage());
            $status = BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Error during import of the AI content generator process occurred.
            Please check logs for details.');
        }

        $operation->setStatus($status ?? BulkOperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Is Enabled
     *
     * @return mixed
     */
    public function isEnabled(): mixed
    {
        return $this->getConfig(self::XML_PATH_ENABLED);
    }

    /**
     * Get System Config
     *
     * @param string $path
     * @return mixed
     */
    public function getConfig(string $path): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Execute
     *
     * @param array $data
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     * @phpcs:disable
     */
    private function execute(array $data): void
    {
        if ($data) {
            if ($importField = $this->getImportCSVField()) {
                $importField = explode(",", $importField);
                $productCSVData = [];
                foreach ($data as $sku) {
                    if (!empty($sku) && $sku != "") {
                        $product = $this->productRepository->get($sku);
                        if ($product) {
                            $productName = $product->getName();
                            $attributeSetId = $product->getAttributeSetId();
                            $currentProductAttribute = $this->getAttributeListBySetId($attributeSetId);
                            $promptAttribute = $this->getPromptAttribute($currentProductAttribute);
                            $productAttribute = '';
                            foreach ($promptAttribute as $subKey => $subValue) {
                                $productAttributeValue = $product->getAttributeText($subKey);
                                if (is_array($productAttributeValue)) {
                                    $productAttributeValue = implode(", ", $productAttributeValue);
                                }
                                if ($productAttributeValue) {
                                    $productAttribute .= $subValue . ': ' . $productAttributeValue . ' and ';
                                }
                            }
                            if ($productAttribute) {
                                $productAttribute = substr_replace($productAttribute, "", -5);
                            }

                            $metaTitleVal = in_array(self::META_TITLE, $importField) ?
                                $this->cleanString(
                                    $this->getGeneratedContent(self::META_TITLE, $productName, $productAttribute)
                                ) :
                                '';
                            $metaKeywordsVal = in_array(self::META_KEYWORDS, $importField) ?
                                $this->cleanString(
                                    $this->getGeneratedContent(self::META_KEYWORDS, $productName, $productAttribute)
                                ) :
                                '';
                            $metaDescriptionVal = in_array(self::META_DESCRIPTION, $importField) ?
                                $this->truncateString(
                                    $this->cleanString(
                                        $this->getGeneratedContent(
                                            self::META_DESCRIPTION,
                                            $productName,
                                            $productAttribute
                                        )
                                    ),
                                    250
                                ) :
                                '';
                            $shortDescriptionVal = in_array(self::SHORT_DESCRIPTION, $importField) ?
                                $this->cleanString(
                                    $this->getGeneratedContent(
                                        self::SHORT_DESCRIPTION,
                                        $productName,
                                        $productAttribute
                                    )
                                ) :
                                '';
                            $descriptionVal = in_array(self::DESCRIPTION, $importField) ?
                                $this->cleanString(
                                    $this->getGeneratedContent(
                                        self::DESCRIPTION,
                                        $productName,
                                        $productAttribute
                                    )
                                ) :
                                '';
                            $productCSVData[] = [
                                self::SKU => $sku,
                                self::META_TITLE => $metaTitleVal,
                                self::META_KEYWORDS => $metaKeywordsVal,
                                self::META_DESCRIPTION => $metaDescriptionVal,
                                self::SHORT_DESCRIPTION => $shortDescriptionVal,
                                self::DESCRIPTION => $descriptionVal
                            ];
                        }
                    } else {
                        $this->notifier->addMajor(
                            __('Error during import of the AI content generate process occurred'),
                            __('The AI content generated data does not have a required value.')
                        );
                        $this->logger->critical("===========================");
                        $this->logger->critical(
                            __("The AI content generated data does not have a required value.")
                        );
                    }
                }

                if ($productCSVData) {
                    $fileName = $this->generateCSV($productCSVData);
                    $data = [
                        'entity' => 'catalog_product',
                        'custom_import' => true,
                        'custom_file_name' => $fileName,
                        'behavior' => 'append',
                        'validation_strategy' => 'validation-stop-on-errors',
                        'allowed_error_count' => 10,
                        '_import_field_separator' => ',',
                        '_import_multiple_value_separator' => ',',
                        '_import_empty_attribute_value_constant' => '__EMPTY__VALUE__',
                        'import_images_file_dir' => null,
                        '_import_ids' => null
                    ];
                    $import = $this->getImport()->setData($data);
                    $source = $import->uploadFileAndGetSource();
                    $import->validateSource($source);
                    $ids = $import->getValidatedIds();
                    if (count($ids) > 0) {
                        $this->logger->critical("===========================");
                        $this->logger->critical(__("Start Import Process"));
                        $import->importSource();
                        if ($this->getEmailSendTo()) {
                            $this->sendMail();
                        }
                        $this->notifier->addNotice(
                            __('The batch process for AI content generation is completed.'),
                            __('')
                        );
                    } else {
                        $this->logger->critical("===========================");
                        $this->logger->critical(
                            __('An error occurred while generating the CSV file.
                            Please try again later or contact support for assistance.')
                        );
                        $this->notifier->addMajor(
                            __('Error during import of the AI content generate process occurred'),
                            __('An error occurred while generating the CSV file.
                            Please try again later or contact support for assistance.')
                        );
                    }
                }
            } else {
                $this->logger->critical("===========================");
                $this->logger->critical(__(
                    'Sorry, Please select a configuration field for importing CSV and try again.'
                ));
                $this->notifier->addMajor(
                    __('Error during import of the AI content generate process occurred'),
                    __('Sorry, Please select a configuration field for importing CSV and try again.')
                );
            }
        } else {
            $this->notifier->addMajor(
                __('Error during import of the AI content generate process occurred'),
                __('Sorry, we are unable to find the appropriate data from RabbitMQ.
                Please try again later or contact customer support for assistance.')
            );
            $this->logger->critical("===========================");
            $this->logger->critical(
                __("Sorry, we are unable to find the appropriate data from RabbitMQ.
                Please try again later or contact customer support for assistance.")
            );
        }
    }

    /**
     * Get Import CSVField
     *
     * @return mixed
     */
    public function getImportCSVField(): mixed
    {
        return $this->getConfig(self::IMPORT_FIELD);
    }

    /**
     * Get Product Attributes from the attribute set.
     *
     * @param string|int $attributeSetId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAttributeListBySetId(string|int $attributeSetId): array
    {
        $attributeCollection = $this->productAttributeManagement->getAttributes($attributeSetId);
        $data = [];
        foreach ($attributeCollection as $attribute) {
            $data[] = $attribute->getAttributeId();
        }

        return $data;
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

    /**
     * Parse(clean) String
     *
     * @param string $content
     * @return string
     */
    private function cleanString(string $content): string
    {
        // Remove extra white space
        $content = preg_replace('/\s+/', ' ', $content);
        // Remove unclosed double quote
        $content = preg_replace('/(?<!\\\\)".*?(?<!\\\\)"/', '', $content);
        // Remove unclosed single quote, excluding contractions like "you're"
        $content = preg_replace("/(?<!\\\\')'.*?(?<!\\\\)'(?!\\w)/", '', $content);

        return trim($content);
    }

    /**
     * Get Generated Content
     *
     * @param string $type
     * @param string $prompt
     * @param string $productAttribute
     * @return string
     */
    private function getGeneratedContent(string $type, string $prompt, string $productAttribute): string
    {
        $completionConfig = $this->completionConfig->getByType($type);
        return $completionConfig->query([
            'prompt' => $prompt,
            'custom_prompt' => false,
            'product_attribute' => $productAttribute,
            'generator_type' => "product",
            'import' => true
        ]);
    }

    /**
     * Truncate String
     *
     * @param string $content
     * @param int $maxlength
     * @return string
     */
    private function truncateString(string $content, int $maxlength): string
    {
        if (strlen($content) > $maxlength) {
            $cutMarker = "**cut_here**";
            $content = wordwrap($content, $maxlength, $cutMarker);
            $content = explode($cutMarker, $content);
            $content = $content[0];
        }

        return $content;
    }

    /**
     * Generate CSV
     *
     * @param array $productCSVData
     * @return string
     * @throws FileSystemException
     */
    private function generateCSV(array $productCSVData): string
    {
        $this->directory->create('importexport');
        $importExportRootDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_IMPORT_EXPORT)
            ->getAbsolutePath();
        if ($this->directory->isExist($importExportRootDir . self::CSV_FILE_PATH)) {
            $this->directory->delete($importExportRootDir . self::CSV_FILE_PATH);
        }

        $importField = $this->getImportCSVField();
        $importField = explode(",", $importField);
        $header = [self::SKU];
        foreach ($importField as $field) {
            array_push($header, $field);
        }

        $stream = $this->directory->openFile(self::CSV_FILE_PATH, 'w+');
        $stream->lock();
        $stream->writeCsv($header);
        foreach ($productCSVData as $data) {
            $csvData = [];
            $csvData[] = $data[self::SKU];
            if (in_array(self::META_TITLE, $header)) {
                $csvData[] = $data[self::META_TITLE];
            }
            if (in_array(self::META_KEYWORDS, $header)) {
                $csvData[] = $data[self::META_KEYWORDS];
            }
            if (in_array(self::META_DESCRIPTION, $header)) {
                $csvData[] = $data[self::META_DESCRIPTION];
            }
            if (in_array(self::SHORT_DESCRIPTION, $header)) {
                $csvData[] = $data[self::SHORT_DESCRIPTION];
            }
            if (in_array(self::DESCRIPTION, $header)) {
                $csvData[] = $data[self::DESCRIPTION];
            }
            $stream->writeCsv($csvData);
        }
        $stream->unlock();
        $stream->close();

        $fileName = 'catalog_product.csv';
        $copyName = $this->localeDate->gmtTimestamp() . '_' . $fileName;
        $copyFile = self::IMPORT_HISTORY_DIR . $copyName;
        $actualFilePath = $this->filesystem->getDirectoryRead(DirectoryList::VAR_IMPORT_EXPORT)
                ->getAbsolutePath() . self::CSV_FILE_PATH;
        if ($this->directory->isExist($actualFilePath)) {
            $this->directory->copyFile($actualFilePath, $copyFile);
        } else {
            $content = $this->directory->getDriver()->fileGetContents($actualFilePath);
            $this->directory->writeFile($copyFile, $content);
        }
        $this->importHistoryModel->addReport($copyName);

        return $copyName;
    }

    /**
     * Provides import model.
     *
     * @return Import
     */
    private function getImport(): Import
    {
        $this->import = $this->_objectManager->get(Import::class);
        return $this->import;
    }

    /**
     * Return email send_to list
     *
     * @return array|bool
     */
    public function getEmailSendTo()
    {
        $data = $this->getConfig(self::EMAIL_TEMPLATE_SEND_TO);
        if (!empty($data)) {
            return array_map('trim', explode(',', $data));
        }
        return false;
    }

    /**
     * Send Mail
     *
     * @return void
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     */
    public function sendMail(): void
    {
        $sendTo = $this->getEmailSendTo();
        if (!empty($sendTo)) {
            foreach ($sendTo as $email) {
                $sender = [
                    'name' => $this->getConfig(self::EMAIL_RECIPIENT_NAME),
                    'email' => $this->getConfig(self::EMAIL_RECIPIENT_EMAIL)
                ];
                $this->inlineTranslation->suspend();
                $this->transportBuilder->setTemplateIdentifier(
                    $this->getConfig(self::EMAIL_TEMPLATE_IDENTIFIER)
                )->setTemplateOptions([
                    'area' => Area::AREA_ADMINHTML,
                    'store' => $this->storeManager->getStore()->getId()
                ])->setTemplateVars([

                ])->setFromByScope(
                    $sender
                )->addTo(
                    $email
                );

                $transport = $this->transportBuilder->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            }
        }
    }

    /**
     * Configure email template
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws MailException
     */
    protected function configureEmailTemplate(): void
    {
        $this->transportBuilder->setTemplateIdentifier($this->getConfig(self::EMAIL_TEMPLATE_IDENTIFIER));
        $this->transportBuilder->setTemplateOptions([
            'area' => Area::AREA_ADMINHTML,
            'store' => $this->storeManager->getStore()->getId()
        ]);
        $sender = [
            'name' => $this->getConfig(self::EMAIL_RECIPIENT_NAME),
            'email' => $this->getConfig(self::EMAIL_RECIPIENT_EMAIL)
        ];
        $this->transportBuilder->setFromByScope($sender);
    }
}
