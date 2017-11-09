<?php

namespace Torann\LocalizationHelpers\Commands;

use Illuminate\Support\Arr;

class ExportCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:export
                                {locale : The locale to be exported}
                                {group : The group or comma separated groups}
                                {--d|delimiter=, : The optional delimiter parameter sets the field delimiter.}
                                {--c|enclosure=" : The optional enclosure parameter sets the field enclosure.}
                                {--p|path= : Save the output to this path.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Exports the language files to CSV files";

    /**
     * Export path.
     *
     * @var string
     */
    protected $export_path;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->export_path = array_get($this->config, 'export_path');
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

        // Get path for the CSV.
        $this->export_path = $this->option('path') ?: $this->export_path;

        // Create storage dir
        if (file_exists($this->export_path) == false) {
            mkdir($this->export_path, 0755, true);
        }

        // Process all of the locales
        foreach ($this->getGroupArgument() as $group) {
            $this->createExport($locale, $group, $delimiter, $enclosure);
        }
    }

    /*
     * Create export file.
     *
     * @param string $locale
     * @param string $group
     * @param string $delimiter
     * @param string $enclosure
     */
    protected function createExport($locale, $group, $delimiter = ',', $enclosure ='"')
    {
        $strings = $this->loadLangList($locale, $group);

        // Can't write to file
        if (!($out = fopen("{$this->export_path}/{$group}.csv", 'w'))) {
            $this->error('Can\'t open the input file!');
            exit;
        }

        // Write CSV file
        foreach ($strings as $key => $value) {
            fputcsv($out, [$key, $value], $delimiter, $enclosure);
        }

        fclose($out);

        $this->line('');
        $this->info("Successfully created export file:");
        $this->info("{$this->export_path}/{$group}.csv");
        $this->line('');
    }

    /*
     * Get list of languages.
     *
     * @return array
     */
    protected function loadLangList($locale, $group)
    {
        $translations = app('translator')->getLoader()->load($locale, $group);

        return Arr::dot([$group => $translations]);
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
