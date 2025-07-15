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
