<?php
declare(strict_types=1);
namespace Undefined\TranslateLocallang\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016-2020 Raphael Graf <r@undefined.ch>
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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class TranslateUtility
{
    const LANGUAGE_DIR = 'Resources/Private/Language';

    /**
     * get list of extensions, loaded or not
     *
     * @param array $allowedExts (empty = all)
     * @param array $allowedFiles (empty = all)
     * @param array $patterns
     * @return array
     */
    public static function getExtList(array $allowedExts, array $allowedFiles = [], array $patterns): array
    {
        //ListUtility->getAvailableExtensions() is too slow..
        $extensions = [];
        $extsPath = Environment::getPublicPath() . '/typo3conf/ext';
        if (($handle = @opendir($extsPath)) === FALSE) {
            return $extensions;
        }
        while (($entry = readdir($handle)) !== FALSE) {
            $extdir = $extsPath . '/' . $entry . '/';
            if ($entry[0] === '.' || !static::isExtension($extdir)) {
                continue;
            }
            if (!empty($patterns)) {
                $skip = 1;
                foreach($patterns as $pattern) {
                    if (fnmatch($pattern, $entry)) {
                        $skip = 0;
                    }
                }
                if ($skip) {
                    continue;
                }
            }
            if (!$GLOBALS['BE_USER']->user['admin'] && !empty($allowedExts)) {
                $skip = 1;
                foreach($allowedExts as $pattern) {
                    if (fnmatch($pattern, $entry)) {
                        $skip = 0;
                    }
                }
                if ($skip || (!empty($allowedFiles) && !static::fileExists($extdir . static::LANGUAGE_DIR, $allowedFiles))) {
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
    public static function getFileList(string $extension, array $allowedFiles = []): array
    {
        $files = [];
        $extdir = Environment::getPublicPath() . '/typo3conf/ext/' . $extension . '/';
        $langdir = $extdir . static::LANGUAGE_DIR;

        $allfiles = GeneralUtility::getAllFilesAndFoldersInPath([], $langdir . '/', 'xlf', false, 3);
        foreach($allfiles as $file) {
                $filename = str_replace($langdir . '/', '', $file);
                $parts = explode('.', $filename);
                if (count($parts) !== 2 || $parts[0] === '') {
                    continue;
                }
                if ($GLOBALS['BE_USER']->user['admin'] || empty($allowedFiles) || in_array($filename, $allowedFiles)) {
                    $files[$filename] = $filename;
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
    public static function getXlfPath(string $extension, string $file, string $langKey = 'default', bool $useL10n = FALSE): string
    {
        //get default path
        $relPath = $extension . '/' . static::LANGUAGE_DIR;
        $extsPath = Environment::getPublicPath() . '/typo3conf/ext';
        $l10nPath = Environment::getLabelsPath();
        if ($langKey === 'default') {
            return $extsPath . '/' . $relPath . '/' . $file;
        }
        //get overlay path
        $pinfo = pathinfo($file);
        $fileName = $langKey . '.' . $pinfo['filename'] . '.' . $pinfo['extension'];
        if ($pinfo['dirname'] !== '.') {
                $fileName = $pinfo['dirname'] . '/' . $fileName;
        }
        if ($useL10n) {
            return $l10nPath . '/' . $langKey . '/' . $relPath . '/' . $fileName;
        } else {
            return $extsPath . '/' . $relPath . '/' . $fileName;
        }
    }

    /**
     * @param string extdir
     * @return bool
     */
    private static function isExtension(string $extdir): bool
    {
        return (@is_file($extdir . 'ext_emconf.php') && @is_dir($extdir . static::LANGUAGE_DIR));
    }

    /**
     * @param string dir
     * @param array filenames
     * @return bool
     */
    private static function fileExists(string $dir, array $filenames): bool
    {
        foreach($filenames as $filename) {
            if (@is_file($dir . '/' . $filename)) {
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
    public static function getModuleData(): array
    {
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
    public static function setModuleData($data): void
    {
        BackendUtility::getModuleData(['data' => ''], ['data' => serialize($data)], 'tools_translate_locallang');
    }
}
