<?php
namespace Undefined\TranslateLocallang\Controller;

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
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['translate_locallang']);
        $this->conf['defaultLangKey'] = (trim($extConf['defaultLangKey'])) ? trim($extConf['defaultLangKey']) : 'en';
        $langKeys = GeneralUtility::trimExplode(',', $extConf['langKeys'], TRUE);
        $this->conf['langKeys'] = array_merge(['default' => $this->conf['defaultLangKey'] . ' (default)'], array_combine($langKeys, $langKeys));
        $this->conf['files'] = GeneralUtility::trimExplode(',', $extConf['allowedFiles'], TRUE);
        $allowedExts = GeneralUtility::trimExplode(',', $extConf['allowedExts'], TRUE);
        $this->conf['extensions'] = TranslateUtility::getExtList($allowedExts, $this->conf['files']);
        $this->conf['modifyKeys'] = (bool)$extConf['modifyKeys'] || $GLOBALS['BE_USER']->user['admin'];
        $this->conf['modifyDefaultLang'] = (bool)$extConf['modifyDefaultLang'] || $GLOBALS['BE_USER']->user['admin'] || $this->conf['modifyKeys'];
        $this->conf['useL10n'] = (bool)$extConf['useL10n'];
        $this->conf['debug'] = (bool)$extConf['debug'];
        $this->conf['sysLog'] = (bool)$extConf['sysLog'];
        $this->conf['langKeysAllowed'] = $this->conf['langKeys'];
        if (!$this->conf['modifyDefaultLang']) {
            unset($this->conf['langKeysAllowed']['default']);
        }
    }

    /**
     * @param string $extension
     * @param string $file
     * @param array $langKeys
     * @return void
     */
    public function listAction($extension = '', $file = '', $langKeys = ['default']) {
        $moduledata = TranslateUtility::getModuleData();
        if ($moduledata && $extension !== '0' ) {
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

        if ($extension) {
            if (!isset($this->conf['extensions'][$extension])) {
                throw new \UnexpectedValueException('Extension not allowed: ' . $extension);
            }
            $l = next($this->conf['langKeys']);
            $l10ndir = 'l10n/' . $l . '/' . $extension;
            if (!$this->conf['useL10n'] && is_dir(PATH_typo3conf . $l10ndir)) {
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
                $xliffService->init($extension, $file, $this->conf['defaultLangKey'], $this->conf['useL10n']);

                foreach($langKeys as $langKey) {
                    if (!$xliffService->loadLang($langKey)) {
                        $this->addFlashMessage('Could not load language: ' . $langKey, 'Warning', AbstractMessage::WARNING);
                        $xliffService->addLang($langKey);
                    }
                }
                $labels = &$xliffService->getData();
                if (empty($labels)) {
                    $this->addFlashMessage('No labels found.', 'Warning', AbstractMessage::WARNING);
                }
                $max_input_vars = (int)ini_get('max_input_vars');
                $fieldcount = count($labels) * (count($langKeys) + 1) + count($langKeys) + 4;
                if ($fieldcount > $max_input_vars) {
                    $this->addFlashMessage('Too many labels, max_input_vars too small. Do not press save!', 'Warning', AbstractMessage::WARNING);
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
    public function saveAction($keys, $labels, $extension, $file, $langKeys) {
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
        $xliffService->init($extension, $file, $this->conf['defaultLangKey'], $this->conf['useL10n']);
        $xliffService->setData($labels);

        //handle keychanges
        $keychanges = [];
        foreach($keys as $key => $keyvalue) {
            if ($key !== $keyvalue) {
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
            if ($xliffService->fileExists($langKey) || $xliffService->getLabelCount($langKey)) {
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
     * @param bool $defaultOnly
     * @return void
     */
    public function exportCsvAction($extension, $file, $defaultOnly = FALSE) {
        if (!isset($this->conf['extensions'][$extension])) {
            throw new \UnexpectedValueException('Extension not allowed: ' . $extension);
        }
        $files = TranslateUtility::getFileList($extension);
        if (!isset($files[$file])) {
            throw new \UnexpectedValueException('File not allowed: ' . $file);
        }

        $xliffService = GeneralUtility::makeInstance('Undefined\TranslateLocallang\Service\XliffService');
        $xliffService->init($extension, $file, $this->conf['defaultLangKey'], $this->conf['useL10n']);

        $langKeys = ($defaultOnly) ? ['default' => $this->conf['defaultLangKey'] . ' (default)'] : $this->conf['langKeys'];
        foreach($langKeys as $langKey => $dummy) {
            if (!$xliffService->loadLang($langKey)) {
                continue;
            }
        }

        $data = &$xliffService->getData();
        $hrow = ['key'];
        foreach($this->conf['langKeys'] as $langKey => $langKeyValue) {
            $hrow[] = $langKeyValue;
        }

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
     * @param string $msg
     * @param int $error (0 = message, 1 = User Error, 2 = System Error, 3 = security notice)
     * @return void
     */
    protected function log($msg, $error = 0) {
        if ($this->conf['sysLog'] || $error) {
            $GLOBALS['BE_USER']->simplelog($msg, 'translate_locallang', $error);
        }
        if ($this->conf['debug'] || $error) {
            $this->addFlashMessage($msg, ($error) ? 'Error' : 'Debug', ($error) ? AbstractMessage::ERROR : AbstractMessage::NOTICE);
        }
    }
}
