<?php
namespace Undefined\TranslateLocallang\Service;

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
use Undefined\TranslateLocallang\Utility\TranslateUtility;

class XliffService
{
    const CDATA_START = '<![CDATA[';
    const CDATA_END = ']]>';

    /**
    * @var array
    */
    protected $data = [];

    /**
    * @var string
    */
    protected $extension = '';

    /**
    * @var string
    */
    protected $file = '';

    /**
    * @var bool
    */
    protected $useL10n = TRUE;

    /**
    * @var string
    */
    protected $sourcelang = 'en';

    /**
    * @var array
    */
    protected $labelcount = [];

    /**
     * @param string $extension
     * @param string $file
     * @param string $sourcelang
     * @param bool $useL10n
     * @return void
     */
    public function init($extension, $file, $sourcelang = 'en', $useL10n = TRUE) {
        $this->extension = $extension;
        $this->file = $file;
        $this->sourcelang = $sourcelang;
        $this->useL10n = $useL10n;
    }

    /**
     * load data from file(s)
     *
     * @param string $langKey
     * @return bool
     */
    public function loadLang($langKey) {
        //Note: This does not use TYPO3\CMS\Core\Localization\Parser\XliffParser because of CDATA support
        //load default data first
        if ($this->getLabelCount('default') === NULL) {
            $this->labelcount[$langKey] = 0;
            $this->data = [];
            $fileref = TranslateUtility::getXlfPath($this->extension, $this->file, 'default', FALSE);
            if(!$this->loadFile($fileref, 'default', TRUE)) {
                return FALSE;
            }
        }

        //load overlay data
        if ($langKey !== 'default') {
            $this->labelcount[$langKey] = 0;
            //check for file in typo3conf/ext first
            $fileref = TranslateUtility::getXlfPath($this->extension, $this->file, $langKey, FALSE);

            $addkeys = !$this->useL10n;
            $success = $this->loadFile($fileref, $langKey, $addkeys);

            if ($this->useL10n) {
                $fileref = TranslateUtility::getXlfPath($this->extension, $this->file, $langKey, TRUE);
                $success = $this->loadFile($fileref, $langKey, TRUE) || $success;
            }

            if (!$success) {
                return FALSE;
            }
            //set missing labels to ''
            $count = 0;
            foreach($this->data as $key => $value) {
                if (!isset($value[$langKey])) {
                    $this->data[$key][$langKey] = '';
                } else {
                    $count++;
                }
            }
            $this->labelcount[$langKey] = $count;
        }

        return TRUE;
    }

    /**
     * @param string $langKey
     * @return bool
     */
    public function saveLang($langKey) {
        $xliff = $this->renderLang($langKey);
        if (!$xliff) {
            return FALSE;
        }
        $fileref = TranslateUtility::getXlfPath($this->extension, $this->file, $langKey, $this->useL10n);
        if ($this->useL10n && !is_dir(dirname($fileref))) {
            try {
                GeneralUtility::mkdir_deep(dirname($fileref), '');
            } catch (\RuntimeException $e) {
                return FALSE;
            }
        }
        return GeneralUtility::writeFile($fileref, $xliff);
    }

    /**
     * @return array
     */
    public function &getData() {
        //return a reference to the array
        return $this->data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
        $first = reset($this->data);
        if ($first) {
            foreach($first as $langKey => $label) {
                $this->labelcount[$langKey] = 0;
            }
        }
        foreach($this->data as $value) {
            foreach($value as $langKey => $label) {
                if ($label) {
                    $this->labelcount[$langKey]++;
                }
            }
        }
    }

    /**
     * add blank language data
     *
     * @param string $langKey
     * @return void
     */
    public function addLang($langKey) {
        foreach($this->data as $key => $dummy) {
            $this->data[$key][$langKey] = '';
        }
        $this->labelcount[$langKey] = 0;
    }

    /**
     * @param string $oldKey
     * @param string $newKey
     * @return void
     */
    public function changeKey($oldKey, $newKey) {
        //replace the array key
        if(!isset($this->data[$oldKey])) {
            return;
        }

        if ($newKey == '') {
            unset($this->data[$oldKey]);
            return;
        }

        $keys = array_keys($this->data);
        $keys[array_search($oldKey, $keys)] = $newKey;

        $newData = array_combine($keys, $this->data);
        $this->data = $newData;
    }

    /**
     * @return void
     */
    public function sortByKey() {
        ksort($this->data);
    }

    /**
     * @param string $langKey
     * @return string
     */
    public function getFilename($langKey) {
        return TranslateUtility::getXlfPath($this->extension, $this->file, $langKey, $this->useL10n);
    }

    /**
     * @param string $langKey
     * @return int
     */
    public function getLabelCount($langKey) {
        if (isset($this->labelcount[$langKey])) {
            return $this->labelcount[$langKey];
        }
        return NULL;
    }

    /**
     * @param string $langKey
     * @return int
     */
    public function fileExists($langKey) {
        return is_file($this->getFilename($langkey));
    }

    /**
     * load data from file
     *
     * @param string $fileref
     * @param string $langKey
     * @param bool $addkeys
     * @return bool
     */
    protected function loadFile($fileref, $langKey = 'default', $addkeys = TRUE) {
        if (!is_file($fileref)) {
            return FALSE;
        }
        libxml_use_internal_errors(TRUE);
        $xml = simplexml_load_file($fileref, 'SimpleXMLElement');
        if ($xml === FALSE) {
            echo '<pre>Error parsing file: ' . $fileref . "\n";
            print_r(libxml_get_errors());
            echo '</pre>';
            return FALSE;
        }
        $children = [];

        if (isset($xml->file) && isset($xml->file->body)) {
            $children = $xml->file->body->children();
        }

        foreach ($children as $transunit) {
            if (!isset($transunit['id'])) {
                continue;
            }
            $key = (string)$transunit['id'];
            if (!isset($this->data[$key]) && !$addkeys) {
                continue;
            }
            if (!isset($this->data[$key])) {
                $this->data[$key] = [];
            }
            $value = $str = '';
            if ($langKey === 'default' && isset($transunit->source)) {
                $value = (string)$transunit->source;
                $str = $transunit->source->asXML();
            } else if (isset($transunit->target)) {
                $value = (string)$transunit->target;
                $str = $transunit->target->asXML();
            }
            if ($str && strpos($str, static::CDATA_START, 8) !== FALSE) {
                $value = static::CDATA_START . $value . static::CDATA_END;
            }
            $this->data[$key][$langKey] = $value;
        }

        return TRUE;
    }

    /**
     * @param string $langKey
     * @return string
     */
    protected function renderLang($langKey) {
        //matches the format used by TYPO3\CMS\Core\Localization\Parser\XliffParser
        $labels = [];
        foreach($this->data as $key => $dummy) {
            if (isset($this->data[$key]['default'])) {
                $labels[$key] = [
                    0 => [
                        'source' => $this->encodeValue($this->data[$key]['default']),
                        'target' => $this->encodeValue($this->data[$key][$langKey])
                ]];
            }
        }
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $xliffview = $objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
        $xliffview->setTemplatePathAndFilename(PATH_typo3conf . 'ext/translate_locallang/Resources/Private/Templates/Xliff.html');

        date_default_timezone_set('UTC');
        $xliffview->assignMultiple([
            'labels' => $labels,
            'sourcelang' => $this->sourcelang,
            'targetlang' => ($langKey === 'default') ? NULL: $langKey,
            'productname' => $this->extension,
            'date' => date('Y-m-d\TH:i:s\Z'), //date('c')
        ]);
        return $xliffview->render();
    }

    /**
     * @param string $str
     * @return string
     */
    protected function encodeValue($str) {
        if (strpos(ltrim($str), static::CDATA_START) === 0) {
            return ltrim($str);
        } else {
            return htmlspecialchars($str);
        }
    }
}
