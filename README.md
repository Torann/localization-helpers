# Localization Helpers for Laravel 5

Localization Helpers is a set of tools to help you manage translations in your Laravel project.

## Installation

- [Localization Helpers on Packagist](https://packagist.org/packages/torann/localization-helpers)
- [Localization Helpers on GitHub](https://github.com/Torann/localization-helpers)

From the command line run:

```
$ composer require torann/localization-helpers
```

Once installed you need to register the service provider with the application. Open up `config/app.php` and find the `providers` key.

```php
'providers' => [

    \Torann\LocalizationHelpers\LocalizationHelpersServiceProvider::class,

]
```

### Publish the configurations

Run this on the command line from the root of your project:

```
$ php artisan vendor:publish --provider="Torann\LocalizationHelpers\LocalizationHelpersServiceProvider"
```

A configuration file will be publish to `config/localization-helpers.php`.

## Usage

### Command `localization:missing`

This command parses all your code and generate according lang files in all `lang/XXX/` directories.

Use `php artisan help localization:missing` for more information about options.

#### Examples

##### Generate all lang files

```
php artisan localization:missing
```

##### Generate all lang files without prompt

```
php artisan localization:missing -n
```

##### Generate all lang files without backuping old files

```
php artisan localization:missing -b
```

##### Generate all lang files without keeping obsolete lemmas

```
php artisan localization:missing -o
```

##### Generate all lang files without any comment for new found lemmas

```
php artisan localization:missing -c
```

##### Generate all lang files without header comment

```
php artisan localization:missing -d
```

##### Generate all lang files and set new lemma values

3 commands below produce the same output:
```
php artisan localization:missing
php artisan localization:missing -l
php artisan localization:missing -l "%LEMMA"
```

You can customize the default generated values for unknown lemmas.

The following command let new values empty:

```
php artisan localization:missing -l ""
```

The following command prefixes all lemmas values with "Please translate this : "

```
php artisan localization:missing -l "Please translate this : %LEMMA"
```

The following command prefixes all lemmas values with "Please translate this !"

```
php artisan localization:missing -l 'Please translate this !'
```

##### Silent option for shell integration

```
#!/bin/bash

php artisan localization:missing -s
if [ $? -eq 0 ]; then
  echo "Nothing to do dude, GO for release"
else
  echo "I will not release in production, lang files are not clean"
fi
```

##### Simulate all operations (do not write anything) with a dry run

```
php artisan localization:missing -r
```

##### Open all must-edit files at the end of the process

```
php artisan localization:missing -e
```

You can edit the editor path in your configuration file. By default, editor is *Sublime Text* on *Mac OS X* :

```
'editor_command_line' => '/Applications/Sublime\\ Text.app/Contents/SharedSupport/bin/subl'
```

### Command `localization:find`

This command will search in all your code for the argument as a lemma.

Use `php artisan help localization:find` for more information about options.

#### Examples

##### Find regular lemma

```
php artisan localization:find Search
```

##### Find regular lemma with verbose

```
php artisan localization:find -v Search
```

##### Find regular lemma with short path displayed

```
php artisan localization:find -s "Search me"
```

##### Find lemma with a regular expression

```
php artisan localization:find -s -r "@Search.*@"
php artisan localization:find -s -r "/.*me$/"
```

> PCRE functions are used

### Command `localization:export`

This command will create a CSV file based on the given locale and group. You have to pass the **locale** and the **group** as arguments. The group is the name of the language file without its extension. You may define options for your desired CSV format.

#### Examples

##### Export the navigation translation for english (en) 

```
php artisan localization:export en navigation
```

##### Optional example 

```
php artisan localization:export en navigation --path=/some/file
php artisan localization:export en navigation --delimiter=";" --enclosure='"' --path=/some/file
```

### Command `localization:import`

This command import a CSV file based on the given locale and group. You have to pass  the **locale**, the **group** and the **path to the CSV file** as arguments. The group is the name of the language file without its extension. You may define options to match the CSV format of your input file.

#### Examples

##### Import the navigation translation for english (en) 

```
php artisan localization:import en navigation
```

##### Optional example 

```
php artisan localization:import en navigation --path=/some/file
php artisan localization:import en navigation --delimiter=";" --enclosure='"' --escape='\\' --path=/some/file
```

> Importing and exporting is helpful when using a third party service such as [OneSkyApp.com](http://www.oneskyapp.com) 

## Change Log

### v1.3.3

- Add the ability to import and export language files in CSV format

### v1.3.2

- Added the ability to support periods in sentences using the `&period;`
- Converted to PSR-4 format
- Removed support for Laravel 4
- Rorked from `potsky/laravel-localization-helpers`

### v1.3.1

- add resource folder for Laravel 5

### v1.3

- add full support for Laravel 5

### v1.2.2

- add support for @lang and @choice in Blade templates (by Jesper Ekstrand)

### v1.2.1

- add `lang_folder_path` parameter in configuration file to configure the custom location of your lang files
- check lang files in `app/lang` by default for Laravel 4.x
- check lang files in `app/resources/lang` by default for Laravel 5

### v1.2

- support for Laravel 5 (4.3)
- add `ignore_lang_files` parameter in configuration file to ignore lang files (useful for `validation` file for example)

