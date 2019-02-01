<?php
declare(strict_types=1);
namespace Undefined\TranslateLocallang\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016-2019 Raphael Graf <r@undefined.ch>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class TranslateUtility
{
    const LANGUAGE_DIR = 'Resources/Private/Language/';

    /**
     * get list of extensions, loaded or not
     *
     * @param array $allowedExts (empty = all)
     * @param array $allowedFiles (empty = all)
     * @return array
     */
    public static function getExtList(array $allowedExts, array $allowedFiles = []): array {
        //ListUtility->getAvailableExtensions() is too slow..
        $extensions = [];
        $configPath = static::getConfigPath();
        if (($handle = @opendir($configPath . '/ext/')) === FALSE) {
            return $extensions;
        }
        while (($entry = readdir($handle)) !== FALSE) {
            $extdir = $configPath . '/ext/' . $entry . '/';
            if ($entry[0] === '.' || !static::isExtension($extdir)) {
                continue;
            }
            if (!$GLOBALS['BE_USER']->user['admin']) {
                if ((!empty($allowedExts) && !in_array($entry, $allowedExts))
                    || (!empty($allowedFiles) && !static::fileExists($extdir . static::LANGUAGE_DIR, $allowedFiles))
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
    public static function getFileList(string $extension, array $allowedFiles = []): array {
        $files = [];
        $extdir = static::getConfigPath() . '/ext/' . $extension . '/';
        if ($handle = @opendir($extdir . static::LANGUAGE_DIR)) {
            while (FALSE !== ($entry = readdir($handle))) {
                $parts = explode('.', $entry);
                if (count($parts) !== 2 || $parts[0] === '' || end($parts) !== 'xlf') {
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
     * get path to XLF, no checking for file existence
     *
     * @param string $extension
     * @param string $file
     * @param string $langKey
     * @param bool $useL10n
     * @return string
     */
    public static function getXlfPath(string $extension, string $file, string $langKey = 'default', bool $useL10n = FALSE): string {
        //get default path
        $relPath = $extension . '/' . static::LANGUAGE_DIR;
        $configPath = static::getConfigPath();
        $l10nPath = static::getLabelsPath();
        if ($langKey === 'default') {
            return $configPath . '/ext/' . $relPath . $file;
        }
        //get overlay path
        $fileName = $langKey . '.' . $file;
        if ($useL10n) {
            return $l10nPath . '/' . $langKey . '/' . $relPath . $fileName;
        } else {
            return $configPath . '/ext/' . $relPath . $fileName;
        }
    }

    /**
     * @param string extdir
     * @return bool
     */
    private static function isExtension(string $extdir): bool {
        return (@is_file($extdir . 'ext_emconf.php') && @is_dir($extdir . static::LANGUAGE_DIR));
    }

    /**
     * @param string dir
     * @param array filenames
     * @return bool
     */
    private static function fileExists(string $dir, array $filenames): bool {
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
    public static function getModuleData(): array {
        $moduledata = BackendUtility::getModuleData(['data' => ''], [], 'tools_translate_locallang');
        if (!empty($moduledata['data'])) {
            $data = unserialize($moduledata['data']);
            if (count($data) === 5 && isset($data['sessid'])) {
                return $data;
            }
        }
        return [];
    }

    /**
     * @param array $data
     * @return void
     */
    public static function setModuleData($data) {
        BackendUtility::getModuleData(['data' => ''], ['data' => serialize($data)], 'tools_translate_locallang');
    }

    /**
     * compatibility wrapper
     *
     * @return string
     */
    public static function getConfigPath(): string {
        if (class_exists('\\TYPO3\\CMS\\Core\\Core\\Environment')) {
            //TYPO3 >= 9.2
            return \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3conf';
        } else {
            return rtrim(PATH_typo3conf, '/');
        }
    }

    /**
     * compatibility wrapper
     *
     * @return string
     */
    public static function getLabelsPath(): string {
        if (class_exists('\\TYPO3\\CMS\\Core\\Core\\Environment')) {
            //TYPO3 >= 9.2
            return \TYPO3\CMS\Core\Core\Environment::getLabelsPath();
        } else {
            return PATH_typo3conf . 'l10n';
        }
    }

    /**
     * compatibility wrapper
     *
     * @return array
     */
    public static function getExtConf() {
        if (class_exists('\\TYPO3\\CMS\\Core\\Configuration\\ExtensionConfiguration')) {
            //TYPO3 >= 9.0
            return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('translate_locallang');
        } else {
            return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['translate_locallang']);
        }
    }
}
