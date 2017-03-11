<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Folders where to search for lemmas
    |--------------------------------------------------------------------------
    |
    | Localization::Missing will search recursively for lemmas in all php files
    | included in these folders. You can use these keywords:
    |
    | - %APP     : the laravel app folder of your project
    | - %BASE    : the laravel base folder of your project
    | - %PUBLIC  : the laravel public folder of your project
    | - %STORAGE : the laravel storage folder of your project
    |
    | No error or exception is thrown when a folder does not exist.
    |
    */

    'folders' => [
        '%BASE/resources/views',
        '%APP/Http/Controllers',
    ],


    /*
    |--------------------------------------------------------------------------
    | Lang file to ignore
    |--------------------------------------------------------------------------
    |
    | These lang files will not be written
    |
    */

    'ignore_lang_files' => [
        'validation',
    ],


    /*
    |--------------------------------------------------------------------------
    | Lang folder
    |--------------------------------------------------------------------------
    |
    | You can overwrite where your lang folder is located
    |
    | If null or missing, Localization::Missing will search
    |
    | - first in app_path() . DIRECTORY_SEPARATOR . 'lang',
    | - then  in base_path() . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang',
    |
    */

    'lang_folder_path' => null,


    /*
    |--------------------------------------------------------------------------
    | Methods or functions to search for
    |--------------------------------------------------------------------------
    |
    | Localization::Missing will search lemmas by using these regular expressions
    | Several regular expressions can be used for a single method or function.
    |
    */

    'trans_methods' => [
        'trans' => [
            '@trans\(\s*(\'.*\')\s*(,.*)*\)@U',
            '@trans\(\s*(".*")\s*(,.*)*\)@U',
        ],
        'Lang::Get' => [
            '@Lang::Get\(\s*(\'.*\')\s*(,.*)*\)@U',
            '@Lang::Get\(\s*(".*")\s*(,.*)*\)@U',
            '@Lang::get\(\s*(\'.*\')\s*(,.*)*\)@U',
            '@Lang::get\(\s*(".*")\s*(,.*)*\)@U',
        ],
        'trans_choice' => [
            '@trans_choice\(\s*(\'.*\')\s*,.*\)@U',
            '@trans_choice\(\s*(".*")\s*,.*\)@U',
        ],
        'Lang::choice' => [
            '@Lang::choice\(\s*(\'.*\')\s*,.*\)@U',
            '@Lang::choice\(\s*(".*")\s*,.*\)@U',
        ],
        '@lang' => [
            '@\@lang\(\s*(\'.*\')\s*(,.*)*\)@U',
            '@\@lang\(\s*(".*")\s*(,.*)*\)@U',
        ],
        '@choice' => [
            '@\@choice\(\s*(\'.*\')\s*,.*\)@U',
            '@\@choice\(\s*(".*")\s*,.*\)@U',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Keywords for obsolete check
    |--------------------------------------------------------------------------
    |
    | Localization::Missing will search lemmas in existing lang files.
    | Then it searches in all PHP source files.
    |
    | When using dynamic or auto-generated lemmas, you must tell Localization::Missing
    | that there are dynamic because it cannot guess them.
    |
    | Example :
    |   - in PHP blade code : <span>{!! trans("message.user.dynamic.$s") !!}</span>
    |   - in lang/en.message.php :
    |     - 'user' => [
    |         'dynamic' => [
    |           'lastname'  => 'Family name',
    |           'firstname' => 'Name',
    |           'email'     => 'Email address',
    |           ...
    |
    |   Then you can define in this parameter value dynamo for example so that
    |   Localization::Missing will not exclude lastname, firstname and email from
    |   translation files.
    |
    */

    'never_obsolete_keys' => [
        'dynamic',
        'fields',
    ],


    /*
    |--------------------------------------------------------------------------
    | Import/Export File Path
    |--------------------------------------------------------------------------
    |
    | Set this to the location of the import and export CSV files.
    |
    */

    'import_path' => storage_path('localization/import'),

    'export_path' => storage_path('localization/export'),

];
