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

var config = {
    map: {
        '*': {
            'Magento_PageBuilder/template/form/element/html-code':
                'MageINIC_AIContentGenerator/template/form/element/html-code'
        }
    },
    paths: {
        'autoGenerateContent': 'MageINIC_AIContentGenerator/js/content/auto_generate',
        'keyValidate': 'MageINIC_AIContentGenerator/js/key_validate'
    },
    config: {
        mixins: {
            'Magento_PageBuilder/js/form/element/html-code': {
                'MageINIC_AIContentGenerator/js/form/element/html-code-mixin': true
            }
        }
    }
};
