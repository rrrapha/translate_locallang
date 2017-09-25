<?php
namespace Undefined\TranslateLocallang\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016-2017 Raphael Graf <r@undefined.ch>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;

class TranslateUtility
{
    /**
     * get all extenions, loaded or not
     *
     * @param array $allowedExts (empty = all)
     * @param array $allowedFiles (empty = all)
     * @return array
     */
    public static function getExtList($allowedExts, $allowedFiles = []) {
        //ListUtility->getAvailableExtensions() is too slow..
        $extensions = [];
        if (($handle = @opendir(PATH_typo3conf . 'ext/')) === FALSE) {
            return $extensions;
        }
        while (($entry = readdir($handle)) !== FALSE) {
            $extdir = PATH_typo3conf . 'ext/' . $entry . '/';
            if ($entry[0] === '.' || !static::isExtension($extdir)) {
                continue;
            }
            if (!$GLOBALS['BE_USER']->user['admin']) {
                if ((!empty($allowedExts) && !in_array($entry, $allowedExts))
                    || (!empty($allowedFiles) && !static::fileExists($extdir . 'Resources/Private/Language/', $allowedFiles))
                ) {
                    continue;
                }
            }
            $extensions[$entry] = $entry;
        }
        ksort($extensions);
        return $extensions;
    }

    /**
     * get list of XLF files, default language only
     *
     * @param string $extension
     * @param array $allowedFiles
     * @return array
     */
    public static function getFileList($extension, $allowedFiles = []) {
        $files = [];
        $extdir = PATH_typo3conf . 'ext/' . $extension . '/';
        if ($handle = @opendir($extdir . 'Resources/Private/Language')) {
            while (FALSE !== ($entry = readdir($handle))) {
                $parts = explode('.', $entry);
                if (count($parts) < 2 || $parts[0] === '' || end($parts) !== 'xlf' || strpos($parts[0], 'locallang') !== 0) {
                    continue;
                }
                if ($GLOBALS['BE_USER']->user['admin'] || empty($allowedFiles) || in_array($entry, $allowedFiles)) {
                    $files[$entry] = $entry;
                }
            }
        }
        return $files;
    }

    /**
     * @param string extdir
     * @return bool
     */
    private static function isExtension($extdir) {
        return (@is_file($extdir . 'ext_emconf.php') && @is_dir($extdir . 'Resources/Private/Language'));
    }

    /**
     * @param string dir
     * @param array filenames
     * @return bool
     */
    private static function fileExists($dir, $filenames) {
        foreach($filenames as $filename) {
            if (@is_file($dir . $filename)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * get persistent module data
     *
     * @return array
     */
    public static function getModuleData() {
        $moduledata = BackendUtility::getModuleData(['data' => ''], [], 'tools_translate_locallang');
        if (!empty($moduledata['data'])) {
            $data = unserialize($moduledata['data']);
            if (count($data) === 5 && isset($data['sessid'])) {
                return $data;
            }
        }
        return NULL;
    }

    /**
     * @param array $data
     * @return void
     */
    public static function setModuleData($data) {
        BackendUtility::getModuleData(['data' => ''], ['data' => serialize($data)], 'tools_translate_locallang');
    }
}
