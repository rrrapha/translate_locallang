<?php

defined('TYPO3_MODE') or die();

(function () {
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
})();
