.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

.. _configuration-extconf:

Extension Configuration
=======================

Click :guilabel:`Settings > Extension Configuration` in the backend to change the options.

Configuration Options:

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
      Option
   :Description:
      Description
   :Default:
      Default value

 - :Property:
      defaultLangKey
   :Description:
      Default language key
   :Default:
      en

 - :Property:
      langKeys
   :Description:
      Translation language keys
   :Default:
      de,fr,it

 - :Property:
      xliffVersion
   :Description:
      XLIFF version used when saving files. Both 1.2 and 2.0 files are read regardless of
      this setting, so an existing 1.2 file is converted to 2.0 the next time it is saved.
   :Default:
      1.2

 - :Property:
      sortOnSave
   :Description:
      Sort labels on save
   :Default:
      0

 - :Property:
      clearCache
   :Description:
      Clear l10n cache on save
   :Default:
      0

 - :Property:
      extFilter
   :Description:
      Filter for extension names (wildcard patterns, comma separated, e.g. acme_*)
   :Default:
      \*

 - :Property:
      translatorInfo
   :Description:
      Provide an information for translators
   :Default:
      [empty]

 - :Property:
      autoTranslate
   :Description:
      Enable automatic translation via MyMemory (experimental)
   :Default:
      0

 - :Property:
      allowedExts
   :Description:
      Allowed extensions for non-admin users (wildcard patterns, comma separated, e.g. acme_*)
   :Default:
      \*

 - :Property:
      modifyDefaultLang
   :Description:
      Allow non-admin users to modify default language
   :Default:
      0

 - :Property:
      modifyKeys
   :Description:
      Allow non-admin users to modify keys and sorting (implies modifyDefaultLang)
   :Default:
      0

.. _configuration-xliffversion:

XLIFF version
=============

Reading is always version agnostic: the version of every file is detected individually
(via the XLIFF namespace or the ``version`` attribute), so 1.2 and 2.0 files can even be
mixed within one extension - for example a 2.0 default file with 1.2 translations.

The :guilabel:`xliffVersion` option only controls what is written. Saving always rewrites
the complete file, so switching the option to ``2.0`` converts a file on its next save.
New files created via :guilabel:`Create new file` also use the configured version.

In 2.0 files the translation state is written to the ``state`` attribute of ``<segment>``:
``final`` for a filled translation and ``initial`` for an empty one. TYPO3 ignores segments
that are not approved (unless ``$GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations']``
is disabled), so untranslated labels fall back to the default language instead of resolving
to an empty string as they do in 1.2.

The 1.2-only ``<file>`` attributes ``date``, ``product-name`` and ``datatype`` have no
counterpart in XLIFF 2.0 and are therefore omitted from 2.0 output.
