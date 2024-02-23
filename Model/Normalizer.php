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

/**
 * Model Class Normalizer
 */
class Normalizer
{
    /**
     * Html To PlainText
     *
     * @param string $html
     * @return string
     */
    public static function htmlToPlainText(string $html): string
    {
        $plainText = preg_replace('/<style[^>]*>.*<\/style>/Uis', '', $html);
        $plainText = preg_replace('/<script[^>]*>.*<\/script>/Uis', '', $plainText);
        $plainText = strip_tags($plainText);
        $plainText = html_entity_decode($plainText);
        $plainText = preg_replace('/\s+/u', ' ', $plainText);

        return trim($plainText);
    }
}
