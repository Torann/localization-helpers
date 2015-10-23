<?php

namespace Torann\LocalizationHelpers\Commands;

use Exception;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ImportCommand extends LocalizationAbstract
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'localization:import';

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
    protected $import_path = '';

    /**
     * Create a new command instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->import_path = array_get($config, 'import_path');

        parent::__construct($config);
    }

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$locale = $this->argument('locale');
		$group  = $this->argument('group');

		$path      = $this->option('path');
		$delimiter = $this->option('delimiter');
		$enclosure = $this->option('enclosure');
		$escape    = $this->option('escape');

		$strings = [];

        // Create storage dir
        if (file_exists($path) == false) {
            mkdir($path, 0755, true);
        }

		// Create output device and write CSV.
		if (($input_fp = fopen("{$path}/{$group}.csv", 'r')) === FALSE) {
			$this->error('Can\'t open the input file!');
            exit;
		}

		// Write CSV lintes
		while (($data = fgetcsv($input_fp, 0, $delimiter, $enclosure, $escape)) !== FALSE) {
			$strings[$data[0]] = $data[1];
		}

		fclose($input_fp);

        $this->writeLangList($locale, $group, $strings);

        $this->line('');
        $this->info("Successfully imported file:");
        $this->info("{$path}/{$group}.csv");
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

        foreach($new_translations as $key => $value) {
            array_set($translations, $key, $value);
        }

        $header = "<?php\n\nreturn ";

        $language_file = $this->getLangPath("{$locale}/{$group}.php");

        if (is_writable($language_file) && ($fp = fopen($language_file, 'w')) !== FALSE)
        {
            fputs($fp, $header.var_export($translations[$group], TRUE).";\n");
            fclose($fp);
        }
        else {
            throw new Exception("Cannot open language file: {$language_file}");
        }
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
            ['group', InputArgument::REQUIRED, 'The group (which is the name of the language file without the extension)'],
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
            ['delimiter', 'd', InputOption::VALUE_OPTIONAL, 'The optional delimiter parameter sets the field delimiter (one character only).', ','],
            ['enclosure', 'c', InputOption::VALUE_OPTIONAL, 'The optional enclosure parameter sets the field enclosure (one character only).', '"'],
            ['escape', 'e', InputOption::VALUE_OPTIONAL, 'The escape character (one character only). Defaults as a backslash.', '\\'],
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'The CSV file path to be imported', $this->import_path],
        ];
    }
}