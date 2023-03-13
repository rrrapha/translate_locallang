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

namespace Undefined\TranslateLocallang\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
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
    * @var array
    */
    protected $extension = [];

    /**
    * @var string
    */
    protected $file = '';

    /**
    * @var bool
    */
    protected $useL10n = TRUE;

    /**
    * @var bool
    */
    protected $lockSourceLang = TRUE;

    /**
    * @var string
    */
    protected $sourcelang = 'en';

    /**
    * @var int
    */
    protected $labelcount = 0;

    /**
    * @var array
    */
    protected $languageLoaded = [];

    /**
     * @param array $extension
     * @param string $file
     * @param string $sourcelang
     * @param bool $useL10n
     * @param bool $lockSourceLang
     * @return void
     */
    public function init(array $extension, string $file, string $sourcelang = 'en', bool $useL10n = TRUE, bool $lockSourceLang = FALSE): void
    {
        $this->extension = $extension;
        $this->file = $file;
        $this->sourcelang = $sourcelang;
        $this->useL10n = $useL10n;
        $this->lockSourceLang = $lockSourceLang;
    }

    /**
     * load data from file(s)
     *
     * @param string $langKey
     * @return bool
     */
    public function loadLang(string $langKey = 'default'): bool
    {
        //Note: This does not use TYPO3\CMS\Core\Localization\Parser\XliffParser because of CDATA support
        //load default data first
        if (!$this->isLanguageLoaded('default')) {
            $this->data = [];
            $fileref = TranslateUtility::getXlfPath($this->extension, $this->file, 'default', FALSE);
            if(!$this->loadFile($fileref, 'default', TRUE)) {
                return FALSE;
            }
        }

        //load overlay data
        if ($langKey !== 'default') {
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
            foreach($this->data as $key => $value) {
                if (!isset($value[$langKey])) {
                    $this->data[$key][$langKey] = '';
                }
            }
        }

        return TRUE;
    }

    /**
     * @param string $langKey
     * @return bool
     */
    public function saveLang(string $langKey): bool
    {
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
    public function &getData(): array
    {
        //return a reference to the array
        return $this->data;
    }

    /**
     * @param array $data
     * @param array $langKeys
     * @return void
     */
    public function mergeData(array $data, array $langKeys): void
    {
        if (!$this->lockSourceLang) {
            //overwrite all data
            $this->data = $data;
        } else {
            //change existing labels only
            if (!$this->isLanguageLoaded('default')) {
                $this->loadLang('default');
            }
            foreach($this->data as $key => $value) {
                foreach($langKeys as $langKey => $langLabel) {
                    if (isset($data[$key][$langKey])) {
                        $this->data[$key][$langKey] = $data[$key][$langKey];
                    }
                }
            }
        }
        foreach($langKeys as $langKey => $langLabel) {
            $this->languageLoaded[$langKey] = TRUE;
        }
        $this->labelcount = count($this->data);
    }

    /**
     * add blank language data
     *
     * @param string $langKey
     * @return void
     */
    public function addLang(string $langKey): void
    {
        foreach($this->data as $key => $dummy) {
            $this->data[$key][$langKey] = '';
        }
        $this->languageLoaded[$langKey] = TRUE;
    }

    /**
     * @param string $oldKey
     * @param string $newKey
     * @return void
     */
    public function changeKey(string $oldKey, string $newKey): void
    {
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
    public function sortByKey(): void
    {
        if (!$this->lockSourceLang){
            ksort($this->data);
        }
    }

    /**
     * @param string $langKey
     * @return string
     */
    public function getFilename(string $langKey): string
    {
        return TranslateUtility::getXlfPath($this->extension, $this->file, $langKey, $this->useL10n);
    }

    /**
     * @return int
     */
    public function getLabelCount(): int
    {
        return $this->labelcount;
    }

    /**
     * @param string $langKey
     * @return bool
     */
    public function isLanguageLoaded(string $langKey): bool
    {
        return (isset($this->languageLoaded[$langKey]) && $this->labelcount > 0); //XXX
    }

    /**
     * @param string $langKey
     * @return bool
     */
    public function fileExists(string $langKey): bool
    {
        return is_file($this->getFilename($langKey));
    }

    /**
     * load data from file
     *
     * @param string $fileref
     * @param string $langKey
     * @param bool $addkeys
     * @return bool
     */
    protected function loadFile(string $fileref, string $langKey = 'default', bool $addkeys = TRUE): bool
    {
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
            // @extensionScannerIgnoreLine
            } else if (isset($transunit->target)) {
                $value = (string)$transunit->target;
                $str = $transunit->target->asXML();
            }
            if ($str && strpos($str, static::CDATA_START, 8) !== FALSE) {
                $value = static::CDATA_START . $value . static::CDATA_END;
            }
            $this->data[$key][$langKey] = $value;
        }
        $this->labelcount = count($this->data);
        $this->languageLoaded[$langKey] = TRUE;

        return TRUE;
    }

    /**
     * @param string $langKey
     * @return string
     */
    protected function renderLang(string $langKey): string
    {
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
        $xliffview = GeneralUtility::makeInstance(StandaloneView::class); 
        $xliffview->setTemplatePathAndFilename(Environment::getPublicPath() . '/typo3conf/ext/translate_locallang/Resources/Private/Templates/Xliff.html');

        date_default_timezone_set('UTC');
        $xliffview->assignMultiple([
            'labels' => $labels,
            'sourcelang' => $this->sourcelang,
            'targetlang' => ($langKey === 'default') ? NULL: $langKey,
            'productname' => $this->extension['key'],
            'date' => date('Y-m-d\TH:i:s\Z'), //date('c')
            'original' => 'EXT:' . $this->extension['key'] . '/' . TranslateUtility::getXlfRelPath($this->file, 'default'),
        ]);
        return $xliffview->render();
    }

    /**
     * @param string $str
     * @return string
     */
    protected function encodeValue(string $str): string
    {
        if (strpos(ltrim($str), static::CDATA_START) === 0) {
            return ltrim($str);
        } else {
            return htmlspecialchars($str);
        }
    }
}
