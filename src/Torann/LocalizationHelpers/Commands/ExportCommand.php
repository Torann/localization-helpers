<?php

namespace Torann\LocalizationHelpers\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExportCommand extends LocalizationAbstract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'localization:export';

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
    protected $export_path = '';

    /**
     * Create a new command instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->export_path = array_get($config, 'export_path');

        parent::__construct($config);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $locale = $this->argument('locale');
        $group = $this->argument('group');

        $delimiter = $this->option('delimiter');
        $enclosure = $this->option('enclosure');

        $strings = $this->loadLangList($locale, $group);

        // Create path device and write CSV.
        $path = $this->option('path');

        // Create storage dir
        if (file_exists($path) == false) {
            mkdir($path, 0755, true);
        }

        // Can't write to file
        if (!($out = fopen("{$path}/{$group}.csv", 'w'))) {
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
        $this->info("{$path}/{$group}.csv");
        $this->line('');
    }

    /*
     * Get list of languages
     *
     * @return array
     */
    protected function loadLangList($locale, $group)
    {
        $translations = app('translator')->getLoader()->load($locale, $group);
        $translations_with_prefix = array_dot([$group => $translations]);

        return $translations_with_prefix;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['locale', InputArgument::REQUIRED, 'The locale to be exported.'],
            [
                'group',
                InputArgument::REQUIRED,
                'The group (which is the name of the language file without the extension)'
            ],
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'delimiter',
                'd',
                InputOption::VALUE_OPTIONAL,
                'The optional delimiter parameter sets the field delimiter (one character only).',
                ','
            ],
            [
                'enclosure',
                'c',
                InputOption::VALUE_OPTIONAL,
                'The optional enclosure parameter sets the field enclosure (one character only).',
                '"'
            ],
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'Save the output to this path', $this->export_path],
        ];
    }
}