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

use MageINIC\AIContentGenerator\Api\CompletionRequestInterface;
use Magento\Framework\App\RequestInterface as Request;

/**
 * Model Class CompletionConfig
 */
class CompletionConfig
{
    /**
     * @var CompletionRequestInterface[]
     */
    private array $pool;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * CompletionConfig Constructor
     *
     * @param array $pool
     * @param Config $config
     * @param Request $request
     */
    public function __construct(
        array   $pool,
        Config  $config,
        Request $request
    ) {
        $this->pool = $pool;
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Get Config
     *
     * @return array|array[]
     */
    public function getConfig(): array
    {
        if (!$this->config->getValue(Config::XML_PATH_ENABLED)) {
            return [
                'targets' => []
            ];
        }

        $allowedStores = $this->config->getEnabledStoreIds();
        $storeId = (int)$this->request->getParam('store', '0');
        if (!in_array($storeId, $allowedStores)) {
            return [
                'targets' => []
            ];
        }

        $targets = [];
        foreach ($this->pool as $config) {
            $targets[$config->getType()] = $config->getJsConfig();
        }
        $targets = array_filter($targets);

        return [
            'targets' => $targets
        ];
    }

    /**
     * Get By Type
     *
     * @param string $type
     * @return CompletionRequestInterface|null
     */
    public function getByType(string $type): ?CompletionRequestInterface
    {
        foreach ($this->pool as $config) {
            if ($config->getType() === $type) {
                return $config;
            }
        }

        return null;
    }
}
