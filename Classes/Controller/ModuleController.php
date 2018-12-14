<?php
declare(strict_types=1);
namespace Undefined\TranslateLocallang\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016-2018 Raphael Graf <r@undefined.ch>
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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use Undefined\TranslateLocallang\Utility\TranslateUtility;

class ModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var array
     */
    private $conf = [];

    /**
     * @return void
     */
    public function initializeAction() {
        $extConf = TranslateUtility::getExtConf();
        $this->conf['defaultLangKey'] = (trim($extConf['defaultLangKey'])) ? trim($extConf['defaultLangKey']) : 'en';
        $langKeys = GeneralUtility::trimExplode(',', $extConf['langKeys'], TRUE);
        $this->conf['langKeys'] = array_merge(['default' => $this->conf['defaultLangKey'] . ' (default)'], array_combine($langKeys, $langKeys));
        $this->conf['files'] = GeneralUtility::trimExplode(',', $extConf['allowedFiles'], TRUE);
        $allowedExts = GeneralUtility::trimExplode(',', $extConf['allowedExts'], TRUE);
        $this->conf['extensions'] = TranslateUtility::getExtList($allowedExts, $this->conf['files']);
        $this->conf['modifyKeys'] = (bool)$extConf['modifyKeys'] || $GLOBALS['BE_USER']->user['admin'];
        $this->conf['useL10n'] = (bool)$extConf['useL10n'];
        $this->conf['debug'] = (bool)$extConf['debug'];
        $this->conf['sysLog'] = (bool)$extConf['sysLog'];
        $this->conf['langKeysAllowed'] = $this->conf['langKeys'];
        if (!((bool)$extConf['modifyDefaultLang'] || $GLOBALS['BE_USER']->user['admin'] || $this->conf['modifyKeys'])) {
            unset($this->conf['langKeysAllowed']['default']);
        }
    }

    /**
     * @param string $extension
     * @param string $file
     * @param array $langKeys
     * @param bool $sort
     * @return void
     */
    public function listAction(string $extension = '', string $file = '', array $langKeys = ['default'], bool $sort = FALSE) {
        $moduledata = TranslateUtility::getModuleData();
        if (!empty($moduledata) && $extension !== '0' ) {
            if (!$extension && $moduledata['extension'] && isset($this->conf['extensions'][$moduledata['extension']])) {
                //restore from moduledata
                $extension = $moduledata['extension'];
                $file = $moduledata['file'];
                $langKeys = $moduledata['langKeys'];
            }
            if ($moduledata['sessid'] !== $GLOBALS['BE_USER']->id && $moduledata['extension'] === $extension) {
                $timediff = time()- $moduledata['time'];
                if ($timediff < 600) {
                    $minutes = (int)(($timediff + 30) / 60);
                    $this->addFlashMessage('Someone else started editing this extension ' . $minutes . ' minutes ago.', 'Warning', AbstractMessage::WARNING);
                }
            }
        }

        //default is always shown
        if (!in_array('default', $langKeys)) {
            array_unshift($langKeys, 'default');
        }

        $disableSaveButtons = '';
        $files = [];
        $labels = [];

        if ($extension) {
            if (!isset($this->conf['extensions'][$extension])) {
                throw new \UnexpectedValueException('Extension not allowed: ' . $extension);
            }
            $l = next($this->conf['langKeys']);
            $l10ndir = '/l10n/' . $l . '/' . $extension;
            if (!$this->conf['useL10n'] && is_dir(TranslateUtility::getConfigPath() . $l10ndir)) {
                $this->addFlashMessage(
                    'typo3conf/' . $l10ndir . ' directory exists. (You are currently editing the files in typo3conf/ext).', 'Notice', AbstractMessage::NOTICE
                );
            }
            $files = TranslateUtility::getFileList($extension, $this->conf['files']);

            if ($file && !isset($files[$file])) {
                $file = '';
            }
            if ($file) {
                $xliffService = GeneralUtility::makeInstance('Undefined\TranslateLocallang\Service\XliffService');
                $xliffService->init($extension, $file, $this->conf['defaultLangKey'], $this->conf['useL10n'], !$this->conf['modifyKeys']);

                foreach($langKeys as $langKey) {
                    if (!$xliffService->loadLang($langKey)) {
                        $this->addFlashMessage('Could not load language: ' . $langKey, 'Warning', AbstractMessage::WARNING);
                        $xliffService->addLang($langKey);
                    }
                }
                if ($sort) {
                    $xliffService->sortByKey();
                }
                $labels = &$xliffService->getData();
                if (empty($labels)) {
                    $this->addFlashMessage('No labels found.', 'Warning', AbstractMessage::WARNING);
                }
                $max_input_vars = (int)ini_get('max_input_vars');
                $fieldcount = (count($labels) + 1) * (count($langKeys) + 1) + count($langKeys) + 10;
                if ($fieldcount > $max_input_vars) {
                    $this->addFlashMessage('Too many labels, max_input_vars too small. Set max_input_vars to at least: ' . $fieldcount, 'Warning', AbstractMessage::WARNING);
                    $disableSaveButtons = 'disabled';
                }
            }
        }

        $this->view->assignMultiple([
            'extension' => $extension,
            'files' => $files,
            'file' => $file,
            'langKeys' => $langKeys,
            'labels' => $labels,
            'conf' => $this->conf,
            'disableSaveButtons' => $disableSaveButtons
        ]);

        TranslateUtility::setModuleData([
            'extension' => $extension,
            'file' => $file,
            'langKeys' => $langKeys,
            'time' => time(),
            'sessid' => $GLOBALS['BE_USER']->id,
        ]);
    }

    /**
     * @param array $keys
     * @param array $labels
     * @param string $extension
     * @param string $file
     * @param array $langKeys
     * @return void
     */
    public function saveAction(array $keys, array $labels, string $extension, string $file, array $langKeys) {
        if (!isset($this->conf['extensions'][$extension])) {
            throw new \UnexpectedValueException('Extension not allowed: ' . $extension);
        }
        $files = TranslateUtility::getFileList($extension);
        if (!isset($files[$file])) {
            throw new \UnexpectedValueException('File not allowed: ' . $file);
        }

        foreach($langKeys as $key => $langKey) {
            if (!isset($this->conf['langKeysAllowed'][$langKey])) {
                unset($langKeys[$key]);
            }
        }

        //remove empty keys
        foreach($keys as $key => $keyvalue) {
            if (trim($keyvalue) === '') {
                unset($labels[$key]);
                unset($keys[$key]);
            }
        }

        $xliffService = GeneralUtility::makeInstance('Undefined\TranslateLocallang\Service\XliffService');
        $xliffService->init($extension, $file, $this->conf['defaultLangKey'], $this->conf['useL10n'], !$this->conf['modifyKeys']);
        $xliffService->mergeData($labels, $this->conf['langKeys']);

        //handle keychanges
        $keychanges = [];
        foreach($keys as $key => $keyvalue) {
            if ((string)$key !== $keyvalue) {
                if (!$this->conf['modifyKeys']) {
                    throw new \UnexpectedValueException('Not allowed to modify keys');
                }
                $keychanges[$key] = $keyvalue;
                $this->log('Changed key: ' . $extension . '|' . $file . ' ' . $key . '->' . $keyvalue, 0);
            }
        }
        $savelangs = $langKeys;
        if (!empty($keychanges)) {
            //load all languages
            foreach($this->conf['langKeysAllowed'] as $langKey => $dummy) {
                if (!in_array($langKey, $savelangs) && $xliffService->loadLang($langKey)) {
                    $savelangs[] = $langKey;
                }
            }
        }
        foreach($keychanges as $key => $keyvalue) {
            $xliffService->changeKey($key, $keyvalue);
        }

        //save languages
        foreach($savelangs as $langKey) {
            if ($xliffService->fileExists($langKey) || $xliffService->isLanguageLoaded($langKey)) {
                $success = $xliffService->saveLang($langKey);
                if (!$success) {
                    $this->log('Write failed: ' . $xliffService->getFilename($langKey), 2);
                }
            }
        }

        $this->log('Updated ' . $extension . '|' . $file . ' ' . implode(', ', $savelangs), 0);
        $this->forward('list', NULL, NULL, ['extension' => $extension, 'file' => $file, 'langKeys' => $langKeys]);
    }

    /**
     * @param string $extension
     * @param string $file
     * @param array $langKeys
     * @return string
     */
    public function exportCsvAction(string $extension, string $file, array $langKeys): string {
        if (!isset($this->conf['extensions'][$extension])) {
            throw new \UnexpectedValueException('Extension not allowed: ' . $extension);
        }
        $files = TranslateUtility::getFileList($extension);
        if (!isset($files[$file])) {
            throw new \UnexpectedValueException('File not allowed: ' . $file);
        }

        $xliffService = GeneralUtility::makeInstance('Undefined\TranslateLocallang\Service\XliffService');
        $xliffService->init($extension, $file, $this->conf['defaultLangKey'], $this->conf['useL10n'], !$this->conf['modifyKeys']);

        $hrow = ['key'];
        foreach($langKeys as $langKey) {
            if (!$xliffService->loadLang($langKey)) {
                continue;
            }
            $hrow[] = $this->conf['langKeys'][$langKey];
        }

        $data = &$xliffService->getData();

        //output CSV
        $fileName = $extension . '-' . $file . '.csv';
        header('Content-Type: text/x-csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        $output = fopen('php://output', 'w');
        print(pack('CCC', 239, 187, 191)); //BOM
        fputcsv($output, $hrow, ';');
        foreach($data as $key => $labels) {
            $row = [$key];
            foreach($labels as $langKey => $dummy) {
                if (isset($labels[$langKey])) {
                    $row[] = $labels[$langKey];
                } else {
                    $row[] = '';
                }
            }
            fputcsv($output, $row, ';');
        }
        fclose($output);
        return '';
    }

    /**
     * @param string $word
     * @return void
     */
    public function searchAction(string $word = '') {
        if (!$word) {
            return;
        }
        $results = [];
        foreach($this->conf['extensions'] as $extension) {
            $files = TranslateUtility::getFileList($extension, $this->conf['files']);
            foreach($files as $file) {
                $langKeys = [];
                foreach($this->conf['langKeysAllowed'] as $langKey => $dummy) {
                    $path = TranslateUtility::getXlfPath($extension, $file, $langKey);
                    if (is_file($path)) {
                        $xliff = file_get_contents($path);
                        $matchtag = ($langKey === 'default') ? 'source' : 'target';
                        if ($xliff && preg_match('/<' . $matchtag . '>.*' . preg_quote($word) . '.*<\/' . $matchtag . '>/i', $xliff)) {
                            $langKeys[$langKey] = $langKey;
                        }
                    }
                }
                if (!empty($langKeys)) {
                    $results[] = [$extension, $file, $langKeys];
                }
            }
        }
        $this->view->assignMultiple([
            'word' => $word,
            'results' => $results,
        ]);
    }

    /**
     * @param string $msg
     * @param int $error (0 = message, 1 = User Error, 2 = System Error, 3 = security notice)
     * @return void
     */
    protected function log(string $msg, int $error = 0) {
        if ($this->conf['sysLog'] || $error) {
            $GLOBALS['BE_USER']->writelog(4, 0, $error, 0, '[translate_locallang] ' . $msg, []);
        }
        if ($this->conf['debug'] || $error) {
            $this->addFlashMessage($msg, ($error) ? 'Error' : 'Debug', ($error) ? AbstractMessage::ERROR : AbstractMessage::NOTICE);
        }
    }
}
