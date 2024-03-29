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

    'extension' => 'php',

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
    | Values to Ignore
    |--------------------------------------------------------------------------
    |
    | String partials that should be ignored when processing lang functions.
    |
    */

    'ignore_values' => [],

    /*
    |--------------------------------------------------------------------------
    | Lang folder
    |--------------------------------------------------------------------------
    |
    | You can overwrite where your lang folder is located
    |
    */

    'lang_folder_path' => base_path('resources/lang'),

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
    | Only Process the System Locale
    |--------------------------------------------------------------------------
    |
    | Sometimes all we'll need to do is process application's default locale
    | because all foreign languages are managed by a third-party site and
    | imported, or in some other method.
    |
    */

    'default_locale_only' => false,

    /*
    |--------------------------------------------------------------------------
    | Ask the User for the Value
    |--------------------------------------------------------------------------
    |
    | For each new value ask the user to verify or enter the value.
    |
    */

    'ask_for_value' => false,

    /*
    |--------------------------------------------------------------------------
    | Use Array Short Syntax
    |--------------------------------------------------------------------------
    |
    | For a more modern and cleaner code enable array short syntax.
    |
    */

    'array_shorthand' => true,


    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default driver that should be used by when
    | managing translations remoting. The "local" driver, is just a simple
    | export and import driver.
    |
    */

    'default_driver' => 'json',


    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many drivers as you wish, and you may even
    | configure multiple drivers of the same driver. Defaults have been setup
    | for each driver as an example of the required options.
    |
    | Locales array: are used to map local locales to remote locales ('en' => 'en-US')
    |
    */

    'drivers' => [

        'csv' => [
            'driver' => 'local',
            'import_path' => storage_path('localization/import'),
            'export_path' => storage_path('localization/export'),
            'format' => 'csv',
            'options' => [
                'delimiter' => ',',
                'enclosure' => '"',
                'escape' => '\\',
            ],
        ],

        'json' => [
            'driver' => 'local',
            'import_path' => storage_path('localization/import'),
            'export_path' => storage_path('localization/export'),
            'format' => 'json',
            'options' => [
                'flags' => JSON_PRETTY_PRINT,
            ],
        ],

        'one_sky' => [
            'driver' => 'one_sky',
            'project_id' => env('ONESKY_PROJECT_ID'),
            'api_key' => env('ONESKY_API_KEY'),
            'secret' => env('ONESKY_SECRET'),
            'locales' => [
                'en' => 'en-US',
            ],
        ],

    ],
];
