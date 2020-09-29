<?php

namespace Torann\LocalizationHelpers\Commands;

use Exception;
use Illuminate\Support\Arr;

class ImportCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:import
                                {locale : The locale to be imported}
                                {group : The group or comma separated groups}
                                {--d|delimiter=, : The optional delimiter parameter sets the field delimiter.}
                                {--c|enclosure=" : The optional enclosure parameter sets the field enclosure.}
                                {--e|escape=\\ : The escape character (one character only). Defaults as a backslash.}
                                {--p|path= : The CSV file path to be imported.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Exports the language files to CSV files";

    /**
     * Import path.
     *
     * @var string
     */
    protected $import_path;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->import_path = Arr::get($this->config, 'import_path');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Set locale to use
        $locale = $this->argument('locale');

        // Set CSV options
        $delimiter = $this->option('delimiter');
        $enclosure = $this->option('enclosure');
        $escape = $this->option('escape');

        // Get path for the CSV.
        $this->import_path = $this->option('path') ?: $this->import_path;

        // Process all of the locales
        foreach ($this->getGroupArgument() as $group) {
            $this->import($locale, $group, $delimiter, $enclosure, $escape);
        }
    }

    /*
     * Import CSV file.
     *
     * @param string $locale
     * @param string $group
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    protected function import($locale, $group, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        // Create output device and write CSV.
        if (($input_fp = fopen("{$this->import_path}/{$group}.csv", 'r')) === false) {
            $this->error('Can\'t open the input file!');
            exit;
        }

        $strings = [];

        // Write CSV lintes
        while (($data = fgetcsv($input_fp, 0, $delimiter, $enclosure, $escape)) !== false) {
            $strings[$data[0]] = $data[1];
        }

        fclose($input_fp);

        $this->writeLangList($locale, $group, $strings);

        $this->line('');
        $this->info("Successfully imported file:");
        $this->info("{$this->import_path}/{$group}.csv");
        $this->line('');
    }

    /*
     * Get list of languages
     *
     * @return array
     * @throws \Exception
     */
    protected function writeLangList($locale, $group, $new_translations)
    {
        $translations = app('translator')->getLoader()->load($locale, $group);

        // Process translations
        foreach ($new_translations as $key => $value) {
            Arr::set(
                $translations,
                $this->encodeKey($key),
                $value
            );
        }

        // Get the language file path
        $language_file = $this->getLangPath() . "/{$locale}/{$group}.php";

        // Sanity check for new language files
        $this->ensureFileExists($language_file);

        if (is_writable($language_file) && ($fp = fopen($language_file, 'w')) !== false) {
            // Export values
            $content = var_export($translations[$group], true);

            // Decode all keys
            $content = $this->decodeKey($content);

            fputs($fp, $this->dumpLangArray("<?php\n\nreturn {$content};"));
            fclose($fp);
        }
        else {
            throw new Exception("Cannot open language file: {$language_file}");
        }
    }

    /**
     * Create the language file if one does not exist.
     *
     * @param string $path
     */
    protected function ensureFileExists($path)
    {
        if (file_exists($path) === false) {
            // Create directory
            @mkdir(dirname($path), 0777, true);

            // Make the language file
            touch($path);
        }
    }

    /**
     * Get group argument.
     *
     * @return array
     */
    protected function getGroupArgument()
    {
        $groups = explode(',', preg_replace('/\s+/', '', $this->argument('group')));

        return array_map(function ($group) {
            return preg_replace('/\\.[^.\\s]{3,4}$/', '', $group);
        }, $groups);
    }
}
