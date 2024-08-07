<?php
declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Undefined\TranslateLocallang\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

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
    public static function getExtList(array $allowedExts, array $allowedFiles = [], array $patterns = []): array
    {
        $listUtility = GeneralUtility::makeInstance(ListUtility::class);
        $availableExtensions = $listUtility->getAvailableExtensions('Local');
        $extensions = [];
        foreach($availableExtensions as $extkey => $extension) {
            if (!empty($patterns)) {
                $skip = 1;
                foreach($patterns as $pattern) {
                    if (fnmatch($pattern, $extkey)) {
                        $skip = 0;
                    }
                }
                if ($skip) {
                    continue;
                }
            }
            if (!empty($allowedExts)) {
                $skip = 1;
                foreach($allowedExts as $pattern) {
                    if (fnmatch($pattern, $extkey)) {
                        $skip = 0;
                    }
                }
                if ($skip) {
                    continue;
                }
            }
            if (!empty($allowedFiles) && !self::fileExists($extension['packagePath'] . self::LANGUAGE_DIR, $allowedFiles)) {
                continue;
            }
            $extensions[$extkey] = $extension;
        }
        ksort($extensions);
        return $extensions;
    }

    /**
     * get list of XLF files, default language only
     *
     * @param array $extension
     * @param array $allowedFiles
     * @return array
     */
    public static function getFileList(array $extension, array $allowedFiles = []): array
    {
        $files = [];
        $langdir = $extension['packagePath'] . self::LANGUAGE_DIR;

        $allfiles = GeneralUtility::getAllFilesAndFoldersInPath([], $langdir . '/', 'xlf', false);
        foreach($allfiles as $file) {
                $filename = str_replace($langdir . '/', '', $file);
                $parts = explode('.', $filename);
                if (count($parts) !== 2 || $parts[0] === '') {
                    continue;
                }
                if (empty($allowedFiles) || in_array($filename, $allowedFiles)) {
                    $files[$filename] = $filename;
                }
        }
        return $files;
    }

    /**
     * get path to XLF, no checking for file existence
     *
     * @param array $extension
     * @param string $file
     * @param string $langKey
     * @return string
     */
    public static function getXlfPath(array $extension, string $file, string $langKey = 'default'): string
    {
        $relPath = self::getXlfRelPath($file, $langKey);
        $basePath = $extension['packagePath'];
        return $basePath . $relPath;
    }

    /**
     * get relative path to XLF
     *
     * @param string $file
     * @param string $langKey
     * @return string
     */
    public static function getXlfRelPath(string $file, string $langKey = 'default'): string
    {
        if ($langKey === 'default') {
            return self::LANGUAGE_DIR . '/' . $file;
        }
        $pinfo = pathinfo($file);
        $fileName = $langKey . '.' . $pinfo['filename'] . '.' . $pinfo['extension'];
        if ($pinfo['dirname'] !== '.') {
                $fileName = $pinfo['dirname'] . '/' . $fileName;
        }
        return self::LANGUAGE_DIR . '/' . $fileName;
    }

    /**
     * @param string $dir
     * @param array $filenames
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
     * check if locallangXMLOverride array is set
     *
     * @param string $extkey
     * @param string $file
     * @param array $langKeys
     * @return bool
     */
    public static function hasOverride(string $extkey, string $file, array $langKeys)
    {
        $overrideKey = 'EXT:' . $extkey. '/' . self::getXlfRelPath($file);
        foreach($langKeys as $langKey) {
            if (@isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$langKey][$overrideKey])) {
                return TRUE;
            }
        }
        return @isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$overrideKey]);
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
            if (count($data) === 5 && isset($data['sessid']) && isset($data['extkey'])) {
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
