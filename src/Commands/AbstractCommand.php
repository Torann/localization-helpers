<?php

namespace Torann\LocalizationHelpers\Commands;

use RegexIterator;
use Illuminate\Support\Arr;
use RecursiveRegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Console\Command;

abstract class AbstractCommand extends Command
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Should commands display something
     *
     * @var bool
     */
    protected $display = true;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->config = config('localization-helpers');

        parent::__construct();
    }

    /**
     * Get the lang directory path
     *
     * @param  string $path
     *
     * @return string
     */
    protected function getLangPath($path = null)
    {
        $lang_folder_path = $this->config('lang_folder_path', null);

        if (empty($lang_folder_path)) {
            $directories = [
                app_path() . DIRECTORY_SEPARATOR . 'lang',
                base_path() . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang',
            ];

            foreach ($directories as $directory) {
                if (file_exists($directory)) {
                    return $directory . ($path ? DIRECTORY_SEPARATOR . $path : $path);
                }
            }

            $this->error("No lang folder found in these paths:");

            foreach ($directories as $directory) {
                $this->error("- " . $directory);
            }

            $this->line('');

            die();
        }
        else {
            if (file_exists($lang_folder_path)) {
                return $lang_folder_path;
            }

            $this->error("No lang folder found in your custom path: \"{$lang_folder_path}\"");
            $this->line('');

            die();
        }
    }

    /**
     * Display console message
     *
     * @param  string          $string
     * @param  string          $style
     * @param  null|int|string $verbosity
     *
     * @return  void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        if ($this->display) {
            parent::line($string, $style, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return  void
     */
    public function info($string, $verbosity = null)
    {
        if ($this->display) {
            parent::info($string, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return  void
     */
    public function comment($string, $verbosity = null)
    {
        if ($this->display) {
            parent::comment($string, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return  void
     */
    public function question($string, $verbosity = null)
    {
        if ($this->display) {
            parent::question($string, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        if ($this->display) {
            parent::error($string, $verbosity);
        }
    }


    /**
     * Return an absolute path without predefined variables
     *
     * @param string $path the relative path
     *
     * @return string|array
     */
    protected function getPath($path)
    {
        return str_replace(
            [
                '%APP',
                '%BASE',
                '%PUBLIC',
                '%STORAGE',
            ],
            [
                app_path(),
                base_path(),
                public_path(),
                storage_path(),
            ],
            $path
        );
    }

    /**
     * Return an relative path to the laravel directory
     *
     * @param string $path the absolute path
     *
     * @return string the relative path
     */
    protected function getShortPath($path)
    {
        return str_replace(base_path(), '', $path);
    }

    /**
     * return an iterator of php files in the provided paths and subpaths
     *
     * @param string $path a source path
     *
     * @return array a list of php file paths
     */
    protected function getPhpFiles($path)
    {
        if (is_dir($path)) {
            return new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST,
                    RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
                ),
                '/^.+\.php$/i',
                RecursiveRegexIterator::GET_MATCH
            );
        }
        else {
            return [];
        }
    }

    /**
     * Extract all translations from the provided file
     * Remove all translations containing :
     * - $  -> auto-generated translation cannot be supported
     * - :: -> package translations are not taken in account
     *
     * @param string $path the file path
     *
     * @return array
     */
    protected function extractTranslationFromFile($path)
    {
        $result = [];
        $string = file_get_contents($path);

        foreach (array_flatten($this->config('trans_methods', [])) as $method) {
            preg_match_all($method, $string, $matches);

            foreach ($matches[1] as $k => $v) {
                if (strpos($v, '$') !== false) {
                    unset($matches[1][$k]);
                }
                if (strpos($v, '::') !== false) {
                    unset($matches[1][$k]);
                }
            }

            $result = array_merge($result, array_flip($matches[1]));
        }

        return $result;
    }

    /**
     * Encode the key so that the array set function doesn't
     * go crazy when it sets the values.
     *
     * @param string $string
     *
     * @return string
     */
    protected function encodeKey($string)
    {
        return preg_replace_callback('/(\.\s|\.$)/', function ($matches) {
            return str_replace('.', '&#46;', $matches[0]);
        }, $string);
    }

    /**
     * Decode the key so it looks normal again.
     *
     * @param string $string
     *
     * @return string
     */
    protected function decodeKey($string)
    {
        return str_replace('&#46;', '.', $string);
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}