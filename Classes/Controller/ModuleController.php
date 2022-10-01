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

namespace Undefined\TranslateLocallang\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Undefined\TranslateLocallang\Utility\TranslateUtility;


class ModuleController extends ActionController
{
    /**
     * @var array
     */
    private $conf = [];
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected IconFactory $iconFactory;

    public function __construct(ModuleTemplateFactory $moduleTemplateFactory, IconFactory $iconFactory)
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->iconFactory = $iconFactory;
    }

    /**
     * @return void
     */
    public function initializeAction(): void
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('translate_locallang');
        $this->conf['defaultLangKey'] = (trim($extConf['defaultLangKey'])) ? trim($extConf['defaultLangKey']) : 'en';
        $langKeys = GeneralUtility::trimExplode(',', $extConf['langKeys'], TRUE);
        $this->conf['langKeys'] = array_merge(['default' => $this->conf['defaultLangKey'] . ' (default)'], array_combine($langKeys, $langKeys));
        $this->conf['allowedFiles'] = $GLOBALS['BE_USER']->isAdmin() ? [] : GeneralUtility::trimExplode(',', $extConf['allowedFiles'], TRUE);
        $allowedExts = $GLOBALS['BE_USER']->isAdmin() ? [] : GeneralUtility::trimExplode(',', $extConf['allowedExts'], TRUE);
        $this->conf['extFilter'] = trim((string)$extConf['extFilter']);
        $patterns = GeneralUtility::trimExplode(',', $this->conf['extFilter'], TRUE);
        $this->conf['extensions'] = TranslateUtility::getExtList($allowedExts, $this->conf['allowedFiles'], $patterns);
        $this->conf['modifyKeys'] = (bool)$extConf['modifyKeys'] || $GLOBALS['BE_USER']->isAdmin();
        $this->conf['useL10n'] = (bool)$extConf['useL10n'];
        $this->conf['clearCache'] = (bool)$extConf['clearCache'];
        $this->conf['langKeysAllowed'] = $this->conf['langKeys'];
        $this->conf['translatorInfo'] = (string)$extConf['translatorInfo'];
        if (!((bool)$extConf['modifyDefaultLang'] || $GLOBALS['BE_USER']->isAdmin() || $this->conf['modifyKeys'])) {
            unset($this->conf['langKeysAllowed']['default']);
        }
    }

    /**
     * @param string $extension
     * @param string $file
     * @param array $langKeys
     * @param bool $sort
     * @param array $overrideLabels
     * @return ResponseInterface
     */
    public function listAction(string $extension = '', string $file = '', array $langKeys = ['default'], bool $sort = FALSE, array $overrideLabels = []): ResponseInterface
    {
        $moduledata = TranslateUtility::getModuleData();
        $sessid = $GLOBALS['BE_USER']->getSession()->getIdentifier();
        if (!empty($moduledata) && $extension !== '0' ) {
            if (!$extension && $moduledata['extension'] && isset($this->conf['extensions'][$moduledata['extension']])) {
                //restore from moduledata
                $extension = $moduledata['extension'];
                $file = $moduledata['file'];
                $langKeys = $moduledata['langKeys'];
            }
            if ($moduledata['sessid'] !== $sessid && $moduledata['extension'] === $extension) {
                $timediff = time()- $moduledata['time'];
                if ($timediff < 600) {
                    $minutes = (int)(($timediff + 30) / 60);
                    $this->addFlashMessage('Someone else started editing this extension ' . $minutes . ' minutes ago.', 'Warning', AbstractMessage::WARNING);
                }
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->addMenu($moduleTemplate);

        //default is always shown
        if (!in_array('default', $langKeys)) {
            array_unshift($langKeys, 'default');
        }

        $disableSaveButton = FALSE;
        $formChanged = FALSE;
        $files = [];
        $labels = [];

        if ($extension) {
            if (!isset($this->conf['extensions'][$extension])) {
                throw new \UnexpectedValueException('Extension not allowed: ' . $extension);
            }
            $l = next($this->conf['langKeys']);
            $l10ndir = Environment::getLabelsPath() . '/' . $l . '/' . $extension;
            if (!$this->conf['useL10n'] && is_dir($l10ndir)) {
                $this->addFlashMessage(
                    $l10ndir . ' directory exists. (You are currently editing the files in typo3conf/ext).', 'Notice', AbstractMessage::NOTICE
                );
            }
            $files = TranslateUtility::getFileList($extension, $this->conf['allowedFiles']);

            if ($file && !isset($files[$file])) {
                $file = '';
            }
            if ($file) {
                if (empty($overrideLabels)) {
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
                        $formChanged = TRUE;
                    }
                    $labels = &$xliffService->getData();
                } else {
                    $labels = $overrideLabels;
                    $formChanged = TRUE;
                }
                if (empty($labels)) {
                    $this->addFlashMessage('No labels found.', 'Warning', AbstractMessage::WARNING);
                }

                $max_input_vars = (int)ini_get('max_input_vars');
                $fieldcount = (count($labels) + 1) * (count($langKeys) + 1) + count($langKeys) + 10;
                if ($fieldcount > $max_input_vars) {
                    $this->addFlashMessage('Too many labels, max_input_vars too small. Set max_input_vars to at least: ' . $fieldcount, 'Warning', AbstractMessage::WARNING);
                    $disableSaveButton = TRUE;
                }

                $this->addButtons($moduleTemplate, $disableSaveButton, $formChanged);
            }
        }

        $this->view->assignMultiple([
            'extension' => $extension,
            'files' => $files,
            'file' => $file,
            'langKeys' => $langKeys,
            'labels' => $labels,
            'conf' => $this->conf,
            'isAdmin' => $GLOBALS['BE_USER']->isAdmin(),
			'time' => time(),
        ]);

        TranslateUtility::setModuleData([
            'extension' => $extension,
            'file' => $file,
            'langKeys' => $langKeys,
            'time' => time(),
            'sessid' => $sessid,
        ]);

        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @param array $keys
     * @param array $labels
     * @param string $extension
     * @param string $file
     * @param array $langKeys
     * @return ResponseInterface
     */
    public function saveAction(array $keys, array $labels, string $extension, string $file, array $langKeys): ResponseInterface
    {
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
                    $this->addFlashMessage('Write failed: ' . $xliffService->getFilename($langKey), 'Error', AbstractMessage::ERROR);
                }
            }
        }

        if ($this->conf['clearCache']) {
            /** @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cacheFrontend */
            $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n');
            $cacheFrontend->flush();
        }

        return (new ForwardResponse('list'))
            ->withArguments(['extension' => $extension, 'file' => $file, 'langKeys' => $langKeys]);
    }

    /**
     * @param string $extension
     * @param string $file
     * @param array $langKeys
     * @return ResponseInterface
     */
    public function exportCsvAction(string $extension, string $file, array $langKeys): ResponseInterface
    {
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
        fputcsv($output, $hrow, ',');
        foreach($data as $key => $labels) {
            $row = [$key];
            foreach($labels as $langKey => $dummy) {
                if (isset($labels[$langKey])) {
                    $row[] = $labels[$langKey];
                } else {
                    $row[] = '';
                }
            }
            fputcsv($output, $row, ',');
        }
        fclose($output);

        return $this->htmlResponse('');
    }

    /**
     * @param string $extension
     * @param string $file
     * @param array  $langKeys
     * @param array  $importFile
     *
     * @return ResponseInterface
     */
    public function importCsvAction(string $extension, string $file, array $langKeys, array $importFile): ResponseInterface
    {
        $labels = [];
        if (is_uploaded_file($importFile['tmp_name'])) {
            $fp = @fopen($importFile['tmp_name'], 'r');
            if ($fp === FALSE) {
                throw new \RuntimeException('Could not open file');
            }

            // check and skip BOM
            if (fgets($fp, 4) !== "\xef\xbb\xbf") {
                rewind($fp);
            }
            // check header row
            $hrow = fgetcsv($fp, 0, ',');
            if (!$hrow || $hrow[0] !== 'key' || count($hrow) < 2) {
                $this->addFlashMessage('Invalid file format', 'Error', AbstractMessage::ERROR);
                return (new ForwardResponse('list'))
                    ->withArguments(['extension' => $extension, 'file' => $file, 'langKeys' => $langKeys]);
            }

            $langKeys = [];
            for ($i = 1; $i < count($hrow); $i++) {
                $langKey = (strpos($hrow[$i], '(default)') !== FALSE) ? 'default' : $hrow[$i];
                $langKeys[$langKey] = $langKey;
            }
            $langKeys = array_intersect_key($langKeys, $this->conf['langKeysAllowed']);
            while ($row = fgetcsv($fp, 0, ',')) {
                $key = $row[0];
                $i = 1;
                foreach($langKeys as $langKey) {
                    $labels[$key][$langKey] = isset($row[$i]) ? $row[$i] : '';
                    $i++;
                }
            }
        } else {
            $this->addFlashMessage('No file uploaded', 'Error', AbstractMessage::ERROR);
        }

        return (new ForwardResponse('list'))
            ->withArguments(['extension' => $extension, 'file' => $file, 'langKeys' => $langKeys, 'sort' => FALSE, 'overrideLabels' => $labels]);
    }

    /**
     * @param string $word
     * @return ResponseInterface
     */
    public function searchAction(string $word = ''): ResponseInterface
    {
        $results = [];
        if ($word) {
            foreach($this->conf['extensions'] as $extension) {
                $files = TranslateUtility::getFileList($extension, $this->conf['allowedFiles']);
                foreach($files as $file) {
                    $langKeys = [];
                    foreach($this->conf['langKeysAllowed'] as $langKey => $dummy) {
                        $path = TranslateUtility::getXlfPath($extension, $file, $langKey, $this->conf['useL10n']);
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
        }
        $this->view->assignMultiple([
            'word' => $word,
            'results' => $results,
            'conf' => $this->conf,
            'search' => TRUE,
        ]);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->addMenu($moduleTemplate);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @param string $extension
     * @param string $newFile
     *
     * @return ResponseInterface
     */
    public function createFileAction(string $extension, string $newFile): ResponseInterface
    {
        if (!isset($this->conf['extensions'][$extension])) {
            throw new \UnexpectedValueException('Extension not allowed: ' . $extension);
        }
        if (!$this->conf['modifyKeys']) {
            throw new \UnexpectedValueException('Not allowed to modify keys');
        }
        if ($newFile) {
            $ok = TRUE;
            $parts = explode('.', $newFile);
            if (count($parts) !== 2 || $parts[0] === '' || end($parts) !== 'xlf') {
                $this->addFlashMessage('Illegal filename', 'Error', AbstractMessage::ERROR);
                $ok = FALSE;
            }
            $path = TranslateUtility::getXlfPath($extension, $newFile, 'default', FALSE);
            if (is_file($path)) {
                 $this->addFlashMessage('The file already exists', 'Error', AbstractMessage::ERROR);
                 $ok = FALSE;
            }
            if ($ok) {
                /* copy the template file */
                $src = realpath(__DIR__ . '/../../Resources/Private/Templates/Empty.xlf');
                if (!@copy($src, $path)) {
                    $this->addFlashMessage('Could not create file', 'Error', AbstractMessage::ERROR);
                }
            }
        }

        return (new ForwardResponse('list'))
            ->withArguments(['extension' => $extension, 'file' => $newFile, 'sort' => FALSE]);
    }

    /**
     * @param ModuleTemplate $moduleTemplate
     * @return void
     */
    private function addMenu($moduleTemplate)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        $menuRegistry = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $menu = $menuRegistry->makeMenu();
        $menu->setIdentifier('actionmenu');

        $menuItems = ['list', 'search'];
        foreach($menuItems as $key => $action) {
            $uri = $uriBuilder->reset()->uriFor($action, [], 'Module');
            $isActive = $this->request->getControllerActionName() === $action ? true : false;
            $title = LocalizationUtility::translate('actionmenu.' . $action, 'TranslateLocallang');
            $menuItem = $menu->makeMenuItem()
              ->setTitle($title)
              ->setHref($uri)
              ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }
        $menuRegistry->addMenu($menu);
    }

    /**
     * @param ModuleTemplate $moduleTemplate
     * @param bool $disableSaveButton
     * @param bool $highlightSaveButton
     * @return void
     */
    private function addButtons($moduleTemplate, $disableSaveButton = FALSE, $highlightSaveButton = FALSE)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $buttonTitle = LocalizationUtility::translate('save', 'TranslateLocallang');
        $saveButton = $buttonBar->makeInputButton()
          ->setForm('translate_labels')
          ->setName('translate_save')
          ->setValue('yes')
          ->setTitle($buttonTitle)
          ->setShowLabelText($buttonTitle)
          ->setDisabled($disableSaveButton)
          ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
        if ($highlightSaveButton)
            $saveButton->setClasses('btn-danger');
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);

        $buttonTitle = LocalizationUtility::translate('export', 'TranslateLocallang');
        $exportButton = $buttonBar->makeInputButton()
          ->setForm('translate_export')
          ->setName('translate_export')
          ->setValue('yes')
          ->setTitle($buttonTitle)
          ->setShowLabelText($buttonTitle)
          ->setIcon($this->iconFactory->getIcon('actions-download', Icon::SIZE_SMALL));
        $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 2);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        $uri = $uriBuilder->reset()->uriFor('list', [], 'Module');
        $buttonTitle = LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload');
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($buttonTitle)
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }
}
