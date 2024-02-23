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
 * Model CompletionRequest Class ShortDescription
 */
class Description extends AbstractCompletion implements CompletionRequestInterface
{
    public const TYPE = 'description';
    protected const CUT_RESULT_PREFIX = 'Description: ';
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
            return [];
        } else if ($fullActionName == "catalog_category_edit" || $fullActionName == "catalog_category_add") {
            return [];
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
                    'Write a description for "%s" category.'
                    .'The output will be in HTML format, following these guidelines:'
                    .'- Do not include the H1 tag.'
                    .'- Avoid using passive voice and instead,'
                    .' use an active voice to convey a sense of energy and empowerment.'
                    .'- Utilize numbered and bulleted lists to present information in a clear and concise manner.'
                    .'- Use bold and italics to emphasize key points and create visual interest.'
                    .'- Make use of emotional words to evoke a sense of desire and aspiration.'
                    .'- Do not include any buttons in the description.'
                    .'- Ensure proper HTML code structure and indentation for clean and readable formatting.',
                    $text['prompt']
                );
        }
        if ($generatorType == "product") {
            $promptTxt = $text['custom_prompt'] === "true" ?
                $text['prompt'] :
                sprintf(
                    'Write a description for "%s" product.'
                    .'The output will be in HTML format, following these guidelines:'
                    .'- Do not include the H1 tag.'
                    .'- Avoid using passive voice and instead,'
                    .' use an active voice to convey a sense of energy and empowerment.'
                    .'- Utilize numbered and bulleted lists to present information in a clear and concise manner.'
                    .'- Use bold and italics to emphasize key points and create visual interest.'
                    .'- Make use of emotional words to evoke a sense of desire and aspiration.'
                    .'- Do not include any buttons in the description.'
                    .'- Ensure proper HTML code structure and indentation for clean and readable formatting.',
                    $text['prompt']
                );
        }
        $optionsTxt = sprintf(" Product options are %s.", $productAttribute);
        $promptTxt = $productAttribute ? $promptTxt . $optionsTxt : $promptTxt;

        return [
            "model" => $this->getConfigData(self::XML_PATH_OPENAI_MODEL),
            "prompt" => $promptTxt,
            "n" => 1,
            "max_tokens" => 400,
            "temperature" => (double)$this->getConfigData(self::XML_PATH_TEMPERATURE),
            "top_p" => (double)$this->getConfigData(self::XML_PATH_TOP_P),
            "frequency_penalty" => (double)$this->getConfigData(self::XML_PATH_FREQUENCY_PENALTY),
            "presence_penalty" => (double)$this->getConfigData(self::XML_PATH_PRESENCE_PENALTY)
        ];
    }
}
