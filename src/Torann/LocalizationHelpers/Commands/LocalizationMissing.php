<?php

namespace Torann\LocalizationHelpers\Commands;

use Symfony\Component\Console\Input\InputOption;

class LocalizationMissing extends LocalizationAbstract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'localization:missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse all translations in app directory and build all lang files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $folders = $this->getPath($this->folders);
        $this->display = !$this->option('silent');

        //////////////////////////////////////////////////
        // Display where translations are searched in //
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
        $lemmas = [];

        foreach ($folders as $path) {
            foreach ($this->getPhpFiles($path) as $php_file_path => $dumb) {
                $lemma = [];
                foreach ($this->extractTranslationFromFile($php_file_path) as $k => $v) {
                    $real_value = eval("return $k;");
                    $lemma[$real_value] = $php_file_path;
                }

                $lemmas = array_merge($lemmas, $lemma);
            }
        }

        if (count($lemmas) === 0) {
            $this->comment("No lemma have been found in code.");
            $this->line("I have searched recursively in PHP files in these directories:");

            foreach ($this->getPath($this->folders) as $path) {
                $this->line("\t{$path}");
            }

            $this->line("for these functions/methods:");

            foreach ($this->trans_methods as $k => $v) {
                $this->line("\t{$k}");
            }

            die();
        }

        $this->line((count($lemmas) > 1) ? count($lemmas) . " lemmas have been found in code" : "1 lemma has been found in code");

        if ($this->option('verbose')) {
            foreach ($lemmas as $key => $value) {
                if (strpos($key, '.') !== false) {
                    $this->line('    <info>' . $key . '</info> in file <comment>' . $this->getShortPath($value) . '</comment>');
                }
            }
        }

        /////////////////////////////////////////////
        // Convert dot lemmas to structured lemmas //
        /////////////////////////////////////////////
        $lemmas_structured = [];

        foreach ($lemmas as $key => $value) {
            if (strpos($key, '.') === false) {
                $this->line('    <error>' . $key . '</error> in file <comment>' . $this->getShortPath($value) . '</comment> <error>will not be included because it has no parent</error>');
            }
            else {
                array_set(
                    $lemmas_structured,
                    $key,
                    str_replace('&period;', '.', $value) // @deprecated in version 1.5
                );
            }
        }

        $this->line('');

        /////////////////////////////////////
        // Generate lang files :           //
        // - add missing lemmas on top     //
        // - keep already defined lemmas   //
        // - add obsolete lemmas on bottom //
        /////////////////////////////////////
        $dir_lang = $this->getLangPath();
        $job = [];
        $there_are_new = false;

        $this->line('Scan files:');
        foreach (scandir($dir_lang) as $lang) {
            if (!in_array($lang, [".", ".."])) {
                if (is_dir($dir_lang . DIRECTORY_SEPARATOR . $lang)) {
                    foreach ($lemmas_structured as $family => $array) {
                        if (in_array($family, $this->ignore_lang_files)) {
                            if ($this->option('verbose')) {
                                $this->line('');
                                $this->info("\t! Skip lang file '{$family}' !");
                            }

                            continue;
                        }

                        $file_lang_path = $dir_lang . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $family . '.php';

                        if ($this->option('verbose')) {
                            $this->line('');
                        }
                        $this->line('    ' . $this->getShortPath($file_lang_path));

                        if (!is_writable(dirname($file_lang_path))) {
                            $this->error("\t> Unable to write file in directory " . dirname($file_lang_path));
                            die();
                        }

                        if (!file_exists($file_lang_path)) {
                            $this->info("\t> File has been created");
                        }

                        if (!touch($file_lang_path)) {
                            $this->error("\t> Unable to touch file {$file_lang_path}");
                            die();
                        }

                        if (!is_readable($file_lang_path)) {
                            $this->error("\t> Unable to read file {$file_lang_path}");
                            die();
                        }

                        if (!is_writable($file_lang_path)) {
                            $this->error("\t> Unable to write in file {$file_lang_path}");
                            die();
                        }

                        $a = include($file_lang_path);
                        $old_lemmas = (is_array($a)) ? array_dot($a) : [];

                        $new_lemmas = array_dot($array);
                        $final_lemmas = [];
                        $display_already_comment = false;
                        $something_to_do = false;
                        $i = 0;

                        $obsolete_lemmas = array_diff_key($old_lemmas, $new_lemmas);
                        $welcome_lemmas = array_diff_key($new_lemmas, $old_lemmas);
                        $already_lemmas = array_intersect_key($old_lemmas, $new_lemmas);

                        ksort($obsolete_lemmas);
                        ksort($welcome_lemmas);
                        ksort($already_lemmas);

                        //////////////////////////
                        // Deal with new lemmas //
                        //////////////////////////
                        if (count($welcome_lemmas) > 0) {
                            $display_already_comment = true;
                            $something_to_do = true;
                            $there_are_new = true;
                            $this->info("\t" . count($welcome_lemmas) . " new strings to translate");
                            $final_lemmas["TRANS___NEW___TRANS"] = "TRANS___NEW___TRANS";

                            foreach ($welcome_lemmas as $key => $path) {
                                $value = $key;

//                                // Clean up the key value
//                                if (substr($key, 0, strpos($key, '.'))) {
//                                    $value = substr($key, strpos($key, '.') + 1);
//                                }

                                if ($this->option('verbose')) {
                                    $this->line("\t\t<info>{$key}</info> in " . $this->getShortPath($path));
                                }

                                if (!$this->option('no-comment')) {
                                    $final_lemmas['TRANS___COMMENT___TRANS' . $i] = "Defined in file $path";
                                    $i = $i + 1;
                                }

                                // Clean up sentence period punctuation
                                if ($this->isMultidimensional($key)) {
                                    array_set($final_lemmas,
                                        $key,
                                        str_replace('%LEMMA', $value, $this->option('new-value'))
                                    );
                                }
                                else {
                                    $final_lemmas[$key] = str_replace('%LEMMA', $value, $this->option('new-value'));
                                }
                            }
                        }

                        ///////////////////////////////
                        // Deal with existing lemmas //
                        ///////////////////////////////
                        if (count($already_lemmas) > 0) {
                            if ($this->option('verbose')) {
                                $this->line("\t\t\t" . count($already_lemmas) . " already translated strings");
                            }

                            $final_lemmas["TRANS___OLD___TRANS"] = "TRANS___OLD___TRANS";

                            foreach ($already_lemmas as $key => $value) {
                                array_set(
                                    $final_lemmas,
                                    $key,
                                    str_replace('&period;', '.', $value) // @deprecated in version 1.5
                                );
                            }
                        }

                        ///////////////////////////////
                        // Deal with obsolete lemmas //
                        ///////////////////////////////
                        if (count($obsolete_lemmas) > 0) {
                            // Remove all dynamic fields
                            foreach ($obsolete_lemmas as $key => $value) {
                                foreach ($this->never_obsolete_keys as $remove) {
                                    $remove = "{$remove}.";

                                    if (substr($key, 0, strlen($remove)) === $remove
                                        || strpos($key, ".{$remove}") !== false
                                    ) {

                                        array_set($final_lemmas,
                                            $key,
                                            str_replace('%LEMMA', str_replace('&period;', '.', $value), // @deprecated in version 1.5
                                                $this->option('new-value'))
                                        );

                                        unset($obsolete_lemmas[$key]);
                                    }
                                }
                            }
                        }

                        if (count($obsolete_lemmas) > 0) {
                            $display_already_comment = true;
                            $something_to_do = true;

                            $this->comment($this->option('no-obsolete')
                                ? "\t" . count($obsolete_lemmas) . " obsolete strings (will be deleted)"
                                : "\t" . count($obsolete_lemmas) . " obsolete strings (can be deleted manually in the generated file)"
                            );

                            $final_lemmas["TRANS___OBSOLETE___TRANS"] = "TRANS___OBSOLETE___TRANS";

                            foreach ($obsolete_lemmas as $key => $value) {
                                if ($this->option('verbose')) {
                                    $this->line("\t\t\t<comment>{$key}</comment>");
                                }

                                if (!$this->option('no-obsolete')) {
                                    array_set(
                                        $final_lemmas,
                                        $key,
                                        str_replace('&period;', '.', $value) // @deprecated in version 1.5
                                    );
                                }
                            }
                        }

                        if (($something_to_do === true) || ($this->option('force'))) {
                            $content = var_export($final_lemmas, true);
                            $content = preg_replace("@'TRANS___COMMENT___TRANS[0-9]*' => '(.*)',@", '// $1', $content);
                            $content = str_replace(
                                [
                                    "'TRANS___NEW___TRANS' => 'TRANS___NEW___TRANS',",
                                    "'TRANS___OLD___TRANS' => 'TRANS___OLD___TRANS',",
                                    "'TRANS___OBSOLETE___TRANS' => 'TRANS___OBSOLETE___TRANS',",
                                ],
                                [
                                    '//============================== New strings to translate ==============================//',
                                    ($display_already_comment === true) ? "\n  //==================================== Translations ====================================//" : '',
                                    "\n  //================================== Obsolete strings ==================================//",
                                ],
                                $content
                            );

                            // Set file content
                            $job[$file_lang_path] = "<?php\n\nreturn " . $content . ";";
                        }
                        else {
                            if ($this->option('verbose')) {
                                $this->line("\t\t> <comment>Nothing to do for this file</comment>");
                            }
                        }
                    }
                }
            }
        }

        ///////////////////////////////////////////
        // Silent mode                           //
        // only return an exit code on new lemma //
        ///////////////////////////////////////////
        if ($this->option('silent')) {
            if ($there_are_new === true) {
                return false;
            }
            else {
                return true;
            }
        }

        ///////////////////////////////////////////
        // Normal mode                           //
        ///////////////////////////////////////////
        if (count($job) > 0) {
            if ($this->option('no-interaction')) {
                $do = true;
            }
            else {
                $this->line('');
                $do = ($this->ask('Do you wish to apply these changes now? [yes|no]') === 'yes');
                $this->line('');
            }

            if ($do === true) {
                if (!$this->option('no-backup')) {
                    $this->line('Backup files:');

                    foreach ($job as $file_lang_path => $file_content) {
                        $backup_path = preg_replace('/\..+$/', '.' . date("Ymd_His") . '.php', $file_lang_path);

                        if (!$this->option('dry-run')) {
                            rename($file_lang_path, $backup_path);
                        }

                        $this->line("\t<info>" . $this->getShortPath($file_lang_path) . "</info> -> <info>" . $this->getShortPath($backup_path) . "</info>");
                    }

                    $this->line('');
                }

                $this->line('Save files:');
                $open_files = '';
                foreach ($job as $file_lang_path => $file_content) {
                    if (!$this->option('dry-run')) {
                        file_put_contents($file_lang_path, $file_content);
                    }

                    $this->line("\t<info>" . $this->getShortPath($file_lang_path));

                    if ($this->option('editor')) {
                        $open_files .= ' ' . escapeshellarg($file_lang_path);
                    }
                }
                $this->line('');

                $this->info('Process done!');

                if ($this->option('editor')) {
                    exec($this->editor . $open_files);
                }

            }
            else {
                $this->line('');
                $this->comment('Process aborted. No file have been changed.');
            }
        }
        else {
            if ($this->option('silent')) {
                return true;
            }

            $this->line('');
            $this->info('All translations are up to date.');
        }

        $this->line('');
    }

    /**
     * Determine if the given string is ment to be a
     * multidimensional array.
     *
     * @param string $string
     *
     * @return bool
     */
    protected function isMultidimensional($string)
    {
        return str_contains(preg_replace('/(\.\s|\.$)/', '&period;', $string), '.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['dry-run', 'r', InputOption::VALUE_NONE, 'Dry run : run process but do not write anything'],
            ['editor', 'e', InputOption::VALUE_NONE, 'Open files which need to be edited at the end of the process'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force file rewrite even if there is nothing to do'],
            [
                'new-value',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Value of new found lemmas (use %LEMMA for the lemma value)',
                '%LEMMA',
            ],
            ['no-backup', 'b', InputOption::VALUE_NONE, 'Do not backup lang file (be careful, I am not a good coder)'],
            ['no-comment', 'c', InputOption::VALUE_NONE, 'Do not add comments in lang files for lemma definition'],
            ['no-obsolete', 'o', InputOption::VALUE_NONE, 'Do not write obsolete lemma'],
            [
                'silent',
                's',
                InputOption::VALUE_NONE,
                'Use this option to only return the exit code (use $? in shell to know whether there are missing lemma)',
            ],
        ];
    }

}
