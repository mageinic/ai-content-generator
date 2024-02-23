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

namespace MageINIC\AIContentGenerator\Model\CompletionRequest;

use Exception;
use MageINIC\AIContentGenerator\Model\Config;
use MageINIC\AIContentGenerator\Model\OpenAI\ApiClient;
use Laminas\Json\Decoder;
use Laminas\Json\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use MageINIC\AIContentGenerator\Model\Normalizer;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Request\Http;

/**
 * Abstract class for AbstractCompletion
 */
abstract class AbstractCompletion
{
    public const TYPE = '';
    protected const CUT_RESULT_PREFIX = '';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var ApiClient|null
     */
    private ?ApiClient $apiClient = null;

    /**
     * @var Http
     */
    protected Http $request;

    /**
     * AbstractCompletion Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http                 $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
    }

    /**
     * Get Api Payload
     *
     * @param array $text
     * @return array
     */
    abstract public function getApiPayload(array $text): array;

    /**
     * Get Client
     *
     * @return ApiClient
     * @throws Exception
     */
    private function getClient(): ApiClient
    {
        $token = $this->scopeConfig->getValue(Config::XML_PATH_TOKEN);
        if (empty($token)) {
            throw new Exception(
                __('API key is missing. Please
                add API key in Stores -> Configuration -> MageINIC -> AI Content Generator -> API Configuration.')
            );
        }
        if ($this->apiClient === null) {
            $this->apiClient = new ApiClient(
                $this->scopeConfig->getValue(Config::XML_PATH_BASE_URL),
                $this->scopeConfig->getValue(Config::XML_PATH_TOKEN)
            );
        }

        return $this->apiClient;
    }

    /**
     * Get Query
     *
     * @param array $params
     * @return string
     */
    public function getQuery(array $params): string
    {
        return $params['prompt'] ?? '';
    }

    /**
     * Query
     *
     * @param array $prompt
     * @return string
     * @throws GuzzleException
     * @throws Exception
     */
    public function query(array $prompt): string
    {
        $payload = $this->getApiPayload([
            'prompt' => Normalizer::htmlToPlainText($prompt['prompt']),
            'custom_prompt' => $prompt['custom_prompt'],
            'product_attribute' => $prompt['product_attribute'],
            'generator_type' => $prompt['generator_type']
        ]);

        $result = $this->getClient()->post(
            '/v1/completions',
            $payload
        );

        $this->validateResponse($result);
        $response = $this->convertToResponse($result->getBody());

        return $this->cleanString($response);
    }

    /**
     * Validate Request
     *
     * @param string $prompt
     * @return void
     * @throws Exception
     */
    protected function validateRequest(string $prompt): void
    {
        if (empty($prompt)) {
            throw new Exception(
                __('Sorry, your search query must be at least 10 characters long.
                Please enter a longer search term and try again.')
            );
        }
    }

    /**
     * Validate Response
     *
     * @param ResponseInterface $result
     * @return void
     * @throws Exception
     */
    protected function validateResponse(ResponseInterface $result): void
    {
        if ($result->getStatusCode() === 401) {
            throw new Exception(
                __('API unauthorized. The token provided may be invalid or has expired.
                Please check your token and ensure that it is valid and try again.')
            );
        }

        if ($result->getStatusCode() >= 500) {
            throw new Exception(__('Server error: %1', $result->getReasonPhrase()));
        }

        $data = Decoder::decode($result->getBody(), Json::TYPE_ARRAY);

        if (isset($data['error'])) {
            throw new Exception(__(
                '%1: %2',
                $data['error']['type'] ?? 'unknown',
                $data['error']['message'] ?? 'unknown'
            ));
        }

        if (!isset($data['choices'])) {
            throw new Exception(
                __('Sorry, no results were returned by the server.
                Please try adjusting your search criteria and try again.')
            );
        }
    }

    /**
     * Convert To Response
     *
     * @param StreamInterface $stream
     * @return string
     */
    public function convertToResponse(StreamInterface $stream): string
    {
        $streamText = (string) $stream;
        $data = Decoder::decode($streamText, Json::TYPE_ARRAY);

        $choices = $data['choices'] ?? [];
        $textData = reset($choices);
        $text = $textData['text'] ?? '';
        $text = trim($text);
        $text = trim($text, '"');

        if (substr($text, 0, strlen(static::CUT_RESULT_PREFIX)) == static::CUT_RESULT_PREFIX) {
            $text = substr($text, strlen(static::CUT_RESULT_PREFIX));
        }

        return $text;
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE;
    }

    /**
     * Get Config Data
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigData(string $path): mixed
    {
        return $this->scopeConfig->getValue($path);
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
}
