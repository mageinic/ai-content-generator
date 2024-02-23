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

namespace MageINIC\AIContentGenerator\Api;

use Psr\Http\Message\StreamInterface;

/**
 * Interface for completion request.
 *
 * @api
 */
interface CompletionRequestInterface
{
    /**
     * Get Api Payload
     *
     * @param array $text
     * @return array
     */
    public function getApiPayload(array $text): array;

    /**
     * Convert To Response
     *
     * @param StreamInterface $stream
     * @return string
     */
    public function convertToResponse(StreamInterface $stream): string;

    /**
     * Get Js Config
     *
     * @return array|null
     */
    public function getJsConfig(): ?array;

    /**
     * Query
     *
     * @param array $prompt
     * @return string
     */
    public function query(array $prompt): string;

    /**
     * Get Type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get Query
     *
     * @param array $params
     * @return string
     */
    public function getQuery(array $params): string;
}
