<?php

namespace Torann\LocalizationHelpers\Commands;

use Illuminate\Support\Arr;

class LocalizationMissing extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:missing
                                {--f|force : Force file rewrite even if there is nothing to do}
                                {--l|new-value=%LEMMA : Value of new found lemmas (use %LEMMA for the lemma value)}
                                {--b|backup : Backup lang file.}
                                {--d|dirty : Only return the exit code (use $? in shell to know whether there are missing lemma)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse all translations in app directory and build all lang files.';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->display = !$this->option('dirty');

        $lemmas = $this->getLemmas();

        /////////////////////////////////////////////
        // Convert dot lemmas to structured lemmas //
        /////////////////////////////////////////////
        $lemmas_structured = [];

        foreach ($lemmas as $key => $value) {
            if (strpos($key, '.') === false) {
                $this->line('    <error>' . $key . '</error> in file <comment>' . $this->getShortPath($value) . '</comment> <error>will not be included because it has no parent</error>');
            }
            else {
                Arr::set(
                    $lemmas_structured,
                    $this->encodeKey($key),
                    $value
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
                        if (in_array($family, $this->config('ignore_lang_files', []))) {
                            if ($this->option('verbose')) {
                                $this->line('');
                                $this->info("    ! Skip lang file '{$family}' !");
                            }

                            continue;
                        }

                        $file_lang_path = $dir_lang . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $family . '.php';

                        if ($this->option('verbose')) {
                            $this->line('');
                        }

                        $this->line('    ' . $this->getShortPath($file_lang_path));

                        $old_lemmas = $this->getOldLemmas($file_lang_path);

                        $new_lemmas = Arr::dot($array);
                        $final_lemmas = [];
                        $something_to_do = false;

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
                            $something_to_do = true;
                            $there_are_new = true;
                            $this->info("    " . count($welcome_lemmas) . " new strings to translate");

                            foreach ($welcome_lemmas as $key => $path) {
                                $value = $this->decodeKey($key);

                                if ($this->option('verbose')) {
                                    $this->line("        <info>{$key}</info> in " . $this->getShortPath($path));
                                }

                                Arr::set($final_lemmas,
                                    $key,
                                    str_replace('%LEMMA', $value, $this->option('new-value'))
                                );
                            }
                        }

                        ///////////////////////////////
                        // Deal with existing lemmas //
                        ///////////////////////////////
                        if (count($already_lemmas) > 0) {
                            if ($this->option('verbose')) {
                                $this->line("            " . count($already_lemmas) . " already translated strings");
                            }

                            foreach ($already_lemmas as $key => $value) {
                                Arr::set(
                                    $final_lemmas,
                                    $key,
                                    $value
                                );
                            }
                        }

                        ///////////////////////////////
                        // Deal with obsolete lemmas //
                        ///////////////////////////////
                        if (count($obsolete_lemmas) > 0) {
                            // Remove all dynamic fields
                            foreach ($obsolete_lemmas as $key => $value) {
                                $id = $this->decodeKey($key);

                                foreach ($this->config('never_obsolete_keys', []) as $remove) {
                                    $remove = "{$remove}.";

                                    if (substr($id, 0, strlen($remove)) === $remove
                                        || strpos($id, ".{$remove}") !== false
                                    ) {

                                        Arr::set($final_lemmas,
                                            $key,
                                            str_replace('%LEMMA', $value, $this->option('new-value'))
                                        );

                                        unset($obsolete_lemmas[$key]);
                                    }
                                }
                            }
                        }

                        if (count($obsolete_lemmas) > 0) {
                            $something_to_do = true;

                            $this->comment("    " . count($obsolete_lemmas) . " obsolete strings (will be deleted)");
                            if ($this->option('verbose')) {
                                foreach ($obsolete_lemmas as $key => $value) {
                                    $this->line("            <comment>" . $this->decodeKey($key) . "</comment>");
                                }
                            }
                        }

                        if (($something_to_do === true) || ($this->option('force'))) {
                            $content = var_export($final_lemmas, true);

                            // Decode all keys
                            $content = $this->decodeKey($content);

                            $job[$file_lang_path] = "<?php\n\nreturn {$content};";
                        }
                        else {
                            if ($this->option('verbose')) {
                                $this->line("        > <comment>Nothing to do for this file</comment>");
                            }
                        }
                    }
                }
            }
        }

        ///////////////////////////////////////////
        // Dirty mode                           //
        ///////////////////////////////////////////
        if ($this->option('dirty')) {
            return $there_are_new;
        }

        ///////////////////////////////////////////
        // Normal mode                           //
        ///////////////////////////////////////////
        if (count($job) > 0) {
            $this->line('');
            $do = ($this->ask('Do you wish to apply these changes now? [yes|no]') === 'yes');
            $this->line('');

            if ($do === true) {
                if ($this->option('backup')) {
                    $this->line('Backup files:');

                    foreach ($job as $file_lang_path => $file_content) {
                        $backup_path = preg_replace('/\..+$/', '.' . date("Ymd_His") . '.php', $file_lang_path);

                        rename($file_lang_path, $backup_path);

                        $this->line("    <info>" . $this->getShortPath($file_lang_path)
                            . "</info> -> <info>" . $this->getShortPath($backup_path) . "</info>");
                    }

                    $this->line('');
                }

                $this->line('Save files:');

                foreach ($job as $file_lang_path => $file_content) {
                    file_put_contents($file_lang_path, $file_content);
                    $this->line("    <info>" . $this->getShortPath($file_lang_path));
                }

                $this->line('');

                $this->info('Process done!');
            }
            else {
                $this->line('');
                $this->comment('Process aborted. No file have been changed.');
            }
        }
        else {
            $this->line('');
            $this->info('All translations are up to date.');
        }

        $this->line('');

        return false;
    }

    /**
     * Determine if the given string is meant to be a
     * multidimensional array.
     *
     * @param string $string
     *
     * @return bool
     */
    protected function isMultidimensional($string)
    {
        return str_contains($this->encodeKey($string), '.');
    }

    /**
     * Get the lemmas values from the provided directories.
     *
     * @param array  $lemmas
     *
     * @return array
     */
    protected function getLemmas(array $lemmas = [])
    {
        // Get folders
        $folders = $this->getPath($this->config('folders', []));

        foreach ($folders as $path) {
            if ($this->option('verbose')) {
                $this->line('    <info>' . $path . '</info>');
            }

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
            $this->comment("No lemma have been found in the code.");
            $this->line("In these directories:");

            foreach ($this->config('folders', []) as $path) {
                $path = $this->getPath($path);
                $this->line("    {$path}");
            }

            $this->line("For these functions/methods:");

            foreach ($this->config('trans_methods', []) as $k => $v) {
                $this->line("    {$k}");
            }

            die();
        }

        $this->line((count($lemmas) > 1) ? count($lemmas)
            . " lemmas have been found in the code"
            : "1 lemma has been found in the code");

        if ($this->option('verbose')) {
            foreach ($lemmas as $key => $value) {
                if (strpos($key, '.') !== false) {
                    $this->line('    <info>' . $key . '</info> in file <comment>'
                        . $this->getShortPath($value) . '</comment>');
                }
            }
        }

        return $lemmas;
    }

    /**
     * Get the old lemmas values.
     *
     * @param string $file_lang_path
     * @param array  $values
     *
     * @return array
     */
    protected function getOldLemmas($file_lang_path, array $values = [])
    {
        if (!is_writable(dirname($file_lang_path))) {
            $this->error("    > Unable to write file in directory " . dirname($file_lang_path));
            die();
        }

        if (!file_exists($file_lang_path)) {
            $this->info("    > File has been created");
        }

        if (!touch($file_lang_path)) {
            $this->error("    > Unable to touch file {$file_lang_path}");
            die();
        }

        if (!is_readable($file_lang_path)) {
            $this->error("    > Unable to read file {$file_lang_path}");
            die();
        }

        if (!is_writable($file_lang_path)) {
            $this->error("    > Unable to write in file {$file_lang_path}");
            die();
        }

        // Get lang file values
        $lang = include($file_lang_path);

        // Parse values
        $lang = is_array($lang) ? Arr::dot($lang) : [];

        foreach ($lang as $key => $value) {
            $values[$this->encodeKey($key)] = $value;
        }

        return $values;
    }
}
