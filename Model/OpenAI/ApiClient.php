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

namespace MageINIC\AIContentGenerator\Model\OpenAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Model OpenAI Class ApiClient
 */
class ApiClient
{
    private const DEFAULT_REQUEST_TIMEOUT = 60;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * ApiClient Constructor
     *
     * @param string $baseUrl
     * @param string $token
     */
    public function __construct(string $baseUrl, string $token)
    {
        $config = [
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ];

        $this->client = new Client($config);
    }

    /**
     * Post
     *
     * @param string $url
     * @param array $data
     * @param array|null $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function post(string $url, array $data, ?array $options = []): ResponseInterface
    {
        try {
            return $this->client->post($url, $this->getPreparedOptions($options, $data));
        } catch (BadResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Get Prepared Options
     *
     * @param array|null $options
     * @param array $data
     * @return array
     */
    protected function getPreparedOptions(?array $options, array $data): array
    {
        $options[RequestOptions::JSON] = $data;

        if (!isset($options['timeout'])) {
            $options['timeout'] = self::DEFAULT_REQUEST_TIMEOUT;
        }

        if (!isset($options['connect_timeout'])) {
            $options['connect_timeout'] = self::DEFAULT_REQUEST_TIMEOUT;
        }

        return $options;
    }
}
