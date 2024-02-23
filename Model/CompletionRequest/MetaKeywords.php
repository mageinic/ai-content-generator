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
use MageINIC\AIContentGenerator\Api\CompletionRequestInterface;

/**
 * Model CompletionRequest Class MetaKeywords
 */
class MetaKeywords extends AbstractCompletion implements CompletionRequestInterface
{
    public const TYPE = 'meta_keywords';
    protected const CUT_RESULT_PREFIX = 'Meta Keywords: ';
    public const XML_PATH_OPENAI_MODEL = 'content_generator/api/openai_model';
    public const XML_PATH_TEMPERATURE = 'content_generator/api/temperature';
    public const XML_PATH_TOP_P = 'content_generator/api/top_p';
    public const XML_PATH_FREQUENCY_PENALTY = 'content_generator/api/frequency_penalty';
    public const XML_PATH_PRESENCE_PENALTY = 'content_generator/api/presence_penalty';

    /**
     * Get Js Config
     *
     * @return string[]|null
     */
    public function getJsConfig(): ?array
    {
        $fullActionName = $this->request->getFullActionName();
        if ($fullActionName == "catalog_product_edit" || $fullActionName == "catalog_product_new") {
            return [
                'attribute_label' => 'Meta Keywords',
                'container' => 'product_form.product_form.search-engine-optimization.container_meta_keyword',
                'target_field' =>
                    'product_form.product_form.search-engine-optimization.container_meta_keyword.meta_keyword',
                'component' => 'MageINIC_AIContentGenerator/js/generate_button_renderer'
            ];
        } else if ($fullActionName == "catalog_category_edit" || $fullActionName == "catalog_category_add") {
            return [
                'attribute_label' => 'Meta Title',
                'container' => 'category_form.category_form.search_engine_optimization.seo_group_keywords',
                'target_field' =>
                    'category_form.category_form.search_engine_optimization.seo_group_keywords.meta_keyword',
                'component' => 'MageINIC_AIContentGenerator/js/generate_button_renderer'
            ];
        } else {
            return [];
        }
    }

    /**
     * Get Api Payload
     *
     * @param array $text
     * @return array
     * @throws Exception
     */
    public function getApiPayload(array $text): array
    {
        parent::validateRequest($text['prompt']);
        $productAttribute = $text['product_attribute'];
        $generatorType = $text['generator_type'];
        $promptTxt = '';
        if ($generatorType == "category") {
            $promptTxt = $text['custom_prompt'] === "true" ?
                $text['prompt'] :
                sprintf(
                    'Write a meta keywords for "%s" category.',
                    $text['prompt']
                );
        }
        if ($generatorType == "product") {
            $promptTxt = $text['custom_prompt'] === "true" ?
                $text['prompt'] :
                sprintf(
                    'Write a meta keywords for "%s" product.',
                    $text['prompt']
                );
        }
        $optionsTxt = sprintf(" Product options are %s.", $productAttribute);
        $promptTxt = $productAttribute ? $promptTxt . $optionsTxt : $promptTxt;

        return [
            "model" => $this->getConfigData(self::XML_PATH_OPENAI_MODEL),
            "prompt" => $promptTxt,
            "n" => 1,
            "max_tokens" => 100,
            "temperature" => (double)$this->getConfigData(self::XML_PATH_TEMPERATURE),
            "top_p" => (double)$this->getConfigData(self::XML_PATH_TOP_P),
            "frequency_penalty" => (double)$this->getConfigData(self::XML_PATH_FREQUENCY_PENALTY),
            "presence_penalty" => (double)$this->getConfigData(self::XML_PATH_PRESENCE_PENALTY)
        ];
    }
}
