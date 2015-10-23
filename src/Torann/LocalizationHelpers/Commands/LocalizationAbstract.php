<?php

namespace Torann\LocalizationHelpers\Commands;

use RegexIterator;
use RecursiveRegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Console\Command;

abstract class LocalizationAbstract extends Command
{
    /**
     * functions and method to catch translations
     *
     * @var  array
     */
    protected $trans_methods = [];

    /**
     * functions and method to catch translations
     *
     * @var  array
     */
    protected $editor = '';

    /**
     * Folders to parse for missing translations
     *
     * @var  array
     */
    protected $folders = [];

    /**
     * Never make lemmas containing these keys obsolete
     *
     * @var  array
     */
    protected $never_obsolete_keys = [];

    /**
     * Never manage these lang files
     *
     * @var  array
     */
    protected $ignore_lang_files = [];

    /**
     * Should comands display something
     *
     * @var  boolean
     */
    protected $display = true;

    /**
     * Create a new command instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->trans_methods = array_get($config, 'trans_methods');
        $this->folders = array_get($config, 'folders');
        $this->ignore_lang_files = array_get($config, 'ignore_lang_files');
        $this->lang_folder_path = array_get($config, 'lang_folder_path');
        $this->never_obsolete_keys = array_get($config, 'never_obsolete_keys');
        $this->editor = array_get($config, 'editor_command_line');

        parent::__construct();
    }

    /**
     * Get the lang directory path
     *
     * @return string the path
     */
    protected function getLangPath()
    {
        if (empty($this->lang_folder_path))
        {
            $paths = [
                app_path() . DIRECTORY_SEPARATOR . 'lang',
                base_path() . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang',
            ];

            foreach ($paths as $path) 
            {
                if (file_exists($path)) {
                    return $path;
                }
            }

            $this->error("No lang folder found in these paths:");

            foreach ($paths as $path) {
                $this->error("- " . $path);
            }

            $this->line('');

            die();
        }
        else {
            if (file_exists($this->lang_folder_path)) {
                return $this->lang_folder_path;
            }

            $this->error('No lang folder found in your custom path: "' . $this->lang_folder_path . '"');
            $this->line('');

            die();
        }
    }

    /**
     * Display console message
     *
     * @param   string $s the message to display
     *
     * @return  void
     */
    public function line($s)
    {
        if ($this->display) {
            parent::line($s);
        }
    }

    /**
     * Display console message
     *
     * @param   string $s the message to display
     *
     * @return  void
     */
    public function info($s)
    {
        if ($this->display) {
            parent::info($s);
        }
    }

    /**
     * Display console message
     *
     * @param   string $s the message to display
     *
     * @return  void
     */
    public function comment($s)
    {
        if ($this->display) {
            parent::comment($s);
        }
    }

    /**
     * Display console message
     *
     * @param   string $s the message to display
     *
     * @return  void
     */
    public function question($s)
    {
        if ($this->display) {
            parent::question($s);
        }
    }

    /**
     * Display console message
     *
     * @param   string $s the message to display
     *
     * @return  void
     */
    public function error($s)
    {
        if ($this->display) {
            parent::error($s);
        }
    }


    /**
     * Return an absolute path without predefined variables
     *
     * @param string $path the relative path
     *
     * @return string the absolute path
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
                storage_path()
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
        if (is_dir($path))
        {
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

        foreach (array_flatten($this->trans_methods) as $method)
        {
            preg_match_all($method, $string, $matches);

            foreach ($matches[1] as $k => $v) {
                if (strpos($v, '$') !== false) unset($matches[1][$k]);
                if (strpos($v, '::') !== false) unset($matches[1][$k]);
            }

            $result = array_merge($result, array_flip($matches[1]));
        }

        return $result;
    }
}