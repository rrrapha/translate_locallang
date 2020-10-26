<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
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
}
