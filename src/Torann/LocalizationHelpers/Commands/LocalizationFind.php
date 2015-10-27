<?php

namespace Torann\LocalizationHelpers\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class LocalizationFind extends LocalizationAbstract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'localization:find';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display all files where the argument is used as a lemma';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $lemma = $this->argument('lemma');
        $folders = $this->getPath($this->folders);

        //////////////////////////////////////////////////
        // Display where translatations are searched in //
        //////////////////////////////////////////////////
        if ($this->option('verbose')) {
            $this->line("Lemmas will be searched in the following directories:");

            foreach ($folders as $path) {
                $this->line('    <info>' . $path . '</info>');
            }

            $this->line('');
        }

        ////////////////////////////////
        // Parse all lemmas from code //
        ////////////////////////////////
        $files = [];
        foreach ($folders as $path) {
            foreach ($this->getPhpFiles($path) as $php_file_path => $dumb) {
                foreach ($this->extractTranslationFromFile($php_file_path) as $k => $v) {
                    $real_value = eval("return $k;");
                    $found = false;

                    if ($this->option('regex')) {
                        try {
                            $r = preg_match($lemma, $real_value);
                        } catch (\Exception $e) {
                            $this->line("<error>The argument is not a valid regular expression:</error>" . str_replace('preg_match():',
                                    '', $e->getMessage()));
                            die();
                        }

                        if ($r === 1) {
                            $found = true;
                        }
                        else {
                            if ($r === false) {
                                $this->error("The argument is not a valid regular expression");
                                die();
                            }
                        }
                    }
                    else {
                        if (strpos($real_value, $lemma)) {
                            $found = true;
                        }
                    }


                    if ($found === true) {
                        if ($this->option('short')) {
                            $php_file_path = $this->getShortPath($php_file_path);
                        }

                        $files[] = $php_file_path;
                        break;
                    }
                }
            }
        }

        if (count($files) > 0) {
            $this->line('Lemma <info>' . $lemma . '</info> has been found in:');

            foreach ($files as $file) {
                $this->line('    <info>' . $file . '</info>');
            }
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
            ['lemma', InputArgument::REQUIRED, 'Lemma'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['regex', 'r', InputOption::VALUE_NONE, 'Argument is a regular expression'],
            ['short', 's', InputOption::VALUE_NONE, 'Short path relative to the laravel project'],
        ];
    }

}
