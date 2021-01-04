<?php
defined('TYPO3_MODE') or die();



(function () {
    $typo3version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
    if ($typo3version->getMajorVersion() < 10) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Undefined.TranslateLocallang',
            'tools',
            'm1',
            '',
            [
                'Module' => 'list, save, exportCsv, importCsv, createFile, deleteFile, search'
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:translate_locallang/Resources/Public/Icons/Extension.svg',
                'labels' => 'LLL:EXT:translate_locallang/Resources/Private/Language/locallang_mod.xlf',
            ]
        );
    } else {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'TranslateLocallang',
            'tools',
            'm1',
            '',
            [
                \Undefined\TranslateLocallang\Controller\ModuleController::class => 'list, save, exportCsv, importCsv, createFile, deleteFile, search'
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:translate_locallang/Resources/Public/Icons/Extension.svg',
                'labels' => 'LLL:EXT:translate_locallang/Resources/Private/Language/locallang_mod.xlf',
            ]
        );
    }
})();