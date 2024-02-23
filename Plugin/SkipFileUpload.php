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

namespace MageINIC\AIContentGenerator\Plugin;

use Magento\ImportExport\Model\Import;

/**
 * class SkipFileUpload To check file is default import file or custom
 */
class SkipFileUpload
{
    /**
     * Around Plugin on Upload Source
     *
     * @param Import $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundUploadSource(Import $subject, callable $proceed): string
    {
        $customImport = $subject->getData('custom_import');
        $customFileName = $subject->getData('custom_file_name');
        $entity = $subject->getData('entity');
        if ($customImport && $customFileName && $entity) {
            return $subject->getWorkingDir() . $entity . '.csv';
        } else {
            return $proceed();
        }
    }
}
