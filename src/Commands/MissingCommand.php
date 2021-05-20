<?php

namespace Torann\LocalizationHelpers\Commands;

use RegexIterator;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use RecursiveRegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Torann\LocalizationHelpers\Concerns\ManageTranslationFiles;

class MissingCommand extends AbstractCommand
{
    use ManageTranslationFiles;

    protected array $final_lemmas = [];
    protected string $obsolete_regex;
    protected bool $is_dirty = false;
    protected bool $has_new = false;
    protected array $jobs = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:missing
                                {--f|force : Force file rewrite even if there is nothing to do}
                                {--l|new-value=%LEMMA : Value of new found lemmas (use %LEMMA for the lemma value)}
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
     * @return int
     */
    public function handle()
    {
        $this->obsolete_regex = implode('|', array_map(function ($key) {
            return preg_quote($key, '/');
        }, $this->config('never_obsolete_keys', [])));

        // Should commands display something
        $this->display = $this->option('dirty') === false;

        // Get all lemmas in a structured array
        $lemmas_structured = $this->getLemmasStructured();

        $this->line('');
        $this->line('Scan files:');

        foreach ($this->getLanguages() as $lang => $lang_path) {
            foreach ($lemmas_structured as $family => $array) {
                if ($this->option('verbose')) {
                    $this->line('');
                }

                // Set path to family file
                $file_lang_path = "{$lang_path}/{$family}.php";

                $this->line('    ' . $this->getShortPath($file_lang_path));

                // Get existing and new lemmas
                $old_lemmas = $this->getOldLemmas($file_lang_path);
                $new_lemmas = Arr::dot($array);

                // Reset the final lemma array
                $this->final_lemmas = [];
                $this->is_dirty = false;

                // Lemma processing
                $this->processNewLemmas($family, $new_lemmas, $old_lemmas);
                $this->processExistingLemmas($old_lemmas, $new_lemmas);
                $this->processObsoleteLemmas($old_lemmas, $new_lemmas);

                if ($this->is_dirty === true || $this->option('force')) {
                    // Sort final lemmas array by key
                    ksort($this->final_lemmas);

                    // Create a dumpy-dump
                    $this->jobs[$file_lang_path] = $this->dumpLemmas($this->final_lemmas);
                } else {
                    if ($this->option('verbose')) {
                        $this->line("        > <comment>Nothing to do for this file</comment>");
                    }
                }
            }
        }

        // For dirty mode, all the user wants is a return value from the
        // command. This is usually used for continuous integration.
        if ($this->option('dirty')) {
            return $this->has_new ? 1 : 0;
        }

        // Save all lemmas
        $this->saveChanges();

        return 0;
    }

    /**
     * Dump the final lemmas into a string for storing in a PHP file.
     *
     * @param array $lemmas
     *
     * @return string
     */
    protected function dumpLemmas(array $lemmas): string
    {
        // Create a dumpy-dump
        $content = var_export($lemmas, true);

        // Decode all keys
        $content = $this->decodeKey($content);

        return $this->dumpLangArray("<?php\n\nreturn {$content};");
    }

    /**
     * Save all changes made to the lemmas.
     *
     * @return void
     */
    protected function saveChanges()
    {
        $this->line('');

        if (count($this->jobs) > 0) {
            $do = true;

            if ($this->config('ask_for_value') === false) {
                $do = ($this->ask('Do you wish to apply these changes now? [yes|no]') === 'yes');
            }

            if ($do === true) {
                $this->line('');
                $this->line('Save files:');

                foreach ($this->jobs as $file_lang_path => $file_content) {
                    file_put_contents($file_lang_path, $file_content);
                    $this->line("    <info>" . $this->getShortPath($file_lang_path));
                }

                $this->line('');
                $this->info('Process done!');
            } else {
                $this->comment('Process aborted. No file have been changed.');
            }
        } else {
            if ($this->has_new && ($this->is_dirty === true || $this->option('force')) === false) {
                $this->comment('Not all translations are up to date.');
            } else {
                $this->info('All translations are up to date.');
            }
        }

        $this->line('');
    }

    /**
     * Process obsolete lemmas.
     *
     * @param array $old_lemmas
     * @param array $new_lemmas
     *
     * @return bool
     */
    protected function processObsoleteLemmas(array $old_lemmas = [], array $new_lemmas = []): bool
    {
        // Get obsolete lemmas
        $lemmas = array_diff_key($old_lemmas, $new_lemmas);

        // Process all of the obsolete lemmas
        if (count($lemmas) > 0) {
            // Sort lemmas by key
            ksort($lemmas);

            // Remove all dynamic fields
            foreach ($lemmas as $key => $value) {
                $id = $this->decodeKey($key);

                // Remove any keys that can never be obsolete
                if ($this->neverObsolete($id)) {
                    Arr::set(
                        $this->final_lemmas,
                        $key, str_replace('%LEMMA', $value, $this->option('new-value'))
                    );

                    unset($lemmas[$key]);
                }
            }
        }

        // Check for obsolete lemmas
        if (count($lemmas) > 0) {
            $this->is_dirty = true;

            $this->comment("    " . count($lemmas) . " obsolete strings (will be deleted)");

            if ($this->option('verbose')) {
                foreach ($lemmas as $key => $value) {
                    $this->line("            <comment>" . $this->decodeKey($key) . "</comment>");
                }
            }
        }

        return true;
    }

    /**
     * Process existing lemmas.
     *
     * @param array $old_lemmas
     * @param array $new_lemmas
     *
     * @return bool
     */
    protected function processExistingLemmas(array $old_lemmas = [], array $new_lemmas = []): bool
    {
        // Get existing lemmas
        $lemmas = array_intersect_key($old_lemmas, $new_lemmas);

        if (count($lemmas) > 0) {
            // Sort lemmas by key
            ksort($lemmas);

            if ($this->option('verbose')) {
                $this->line("            " . count($lemmas) . " already translated strings");
            }

            foreach ($lemmas as $key => $value) {
                Arr::set(
                    $this->final_lemmas, $key, $value
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Process new lemmas.
     *
     * @param string $family
     * @param array  $new_lemmas
     * @param array  $old_lemmas
     *
     * @return bool
     */
    protected function processNewLemmas(string $family, array $new_lemmas = [], array $old_lemmas = []): bool
    {
        // Get new lemmas
        $lemmas = array_diff_key($new_lemmas, $old_lemmas);

        // Remove any never obsolete values
        if ($this->config('ask_for_value') === false) {
            $lemmas = array_filter($lemmas, function ($key) {
                if ($this->neverObsolete($key)) {
                    $this->line("        <comment>Manually add:</comment> <info>{$key}</info>");
                    $this->has_new = true;

                    return false;
                }

                return true;
            }, ARRAY_FILTER_USE_KEY);
        }

        // Process new lemmas
        if (count($lemmas) > 0) {
            $this->is_dirty = true;
            $this->has_new = true;

            // Sort lemmas by key
            ksort($lemmas);

            $this->info("    " . count($lemmas) . " new strings to translate");

            foreach ($lemmas as $key => $path) {
                $value = $this->decodeKey($key);

                // Only ask for feedback when it's not a dirty check
                if ($this->option('dirty') === false && $this->config('ask_for_value') === true) {
                    $value = $this->ask(
                        "{$family}.{$value}", $this->createSuggestion($value)
                    );
                }

                if ($this->option('verbose')) {
                    $this->line("        <info>{$key}</info> in " . $this->getShortPath($path));
                }

                Arr::set(
                    $this->final_lemmas,
                    $key, str_replace('%LEMMA', $value, $this->option('new-value'))
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Get all languages and their paths.
     *
     * @param array $paths
     *
     * @return array
     */
    protected function getLanguages(array $paths = []): array
    {
        // Get language path
        $dir_lang = $this->getLangPath();

        // Only use the default locale
        if ($this->config('default_locale_only')) {
            return [
                $this->default_locale => "{$dir_lang}/{$this->default_locale}",
            ];
        }

        // Get all language paths
        foreach (glob("{$dir_lang}/*", GLOB_ONLYDIR) as $path) {
            $paths[basename($path)] = $path;
        }

        return $paths;
    }

    /**
     * Create a key value suggestion.
     *
     * @param string $value
     *
     * @return string
     */
    protected function createSuggestion(string $value): string
    {
        // Strip the obsolete regex keys
        if (empty($this->obsolete_regex) === false) {
            $value = preg_replace("/^({$this->obsolete_regex})\./i", '', $value);
        }

        return Str::title(str_replace('_', ' ', $value));
    }

    /**
     * Check key to ensure it isn't a never obsolete key.
     *
     * @param string $value
     *
     * @return bool
     */
    protected function neverObsolete(string $value): bool
    {
        // Remove any keys that can never be obsolete
        foreach ($this->config('never_obsolete_keys', []) as $remove) {
            $remove = "{$remove}.";

            if (substr($value, 0, strlen($remove)) === $remove
                || strpos($value, ".{$remove}") !== false
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert dot lemmas to structured lemmas.
     *
     * @param array $structured
     *
     * @return array
     */
    protected function getLemmasStructured(array $structured = []): array
    {
        foreach ($this->getLemmas() as $key => $value) {
            // Get the lemma family
            $family = substr($key, 0, strpos($key, '.'));

            // Check key against the ignore list
            if (in_array($family, $this->config('ignore_lang_files', []))) {
                if ($this->option('verbose')) {
                    $this->line('');
                    $this->info("    ! Skip lang file '{$family}' !");
                }

                continue;
            }

            // Sanity check
            if (strpos($key, '.') === false) {
                $this->line('    <error>' . $key . '</error> in file <comment>' . $this->getShortPath($value) . '</comment> <error>will not be included because it has no parent</error>');
            } else {
                Arr::set(
                    $structured, $this->encodeKey($key), $value
                );
            }
        }

        return $structured;
    }

    /**
     * Get the lemmas values from the provided directories.
     *
     * @param array $lemmas
     *
     * @return array
     */
    protected function getLemmas(array $lemmas = []): array
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
    protected function getOldLemmas(string $file_lang_path, array $values = []): array
    {
        if (! is_writable(dirname($file_lang_path))) {
            $this->error("    > Unable to write file in directory " . dirname($file_lang_path));
            die();
        }

        if (! file_exists($file_lang_path)) {
            $this->info("    > File has been created");
        }

        if (! touch($file_lang_path)) {
            $this->error("    > Unable to touch file {$file_lang_path}");
            die();
        }

        if (! is_readable($file_lang_path)) {
            $this->error("    > Unable to read file {$file_lang_path}");
            die();
        }

        if (! is_writable($file_lang_path)) {
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

    /**
     * Return an absolute path without predefined variables
     *
     * @param array|string $subject
     *
     * @return string|array
     */
    protected function getPath($subject)
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
            $subject
        );
    }

    /**
     * Extract all translations from the provided file
     * Remove all translations containing :
     * - $  -> auto-generated translation cannot be supported
     * - :: -> package translations are not taken in account
     *
     * @param string $path
     *
     * @return array
     */
    protected function extractTranslationFromFile(string $path): array
    {
        $result = [];
        $string = file_get_contents($path);

        foreach (Arr::flatten($this->config('trans_methods', [])) as $method) {
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
     * Return an iterator of php files in the provided paths and sub-paths
     *
     * @param string $path
     *
     * @return mixed
     */
    protected function getPhpFiles(string $path)
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
        } else {
            return [];
        }
    }

    /**
     * Return an relative path to the laravel directory
     *
     * @param string $path
     *
     * @return string
     */
    protected function getShortPath(string $path): string
    {
        return str_replace(base_path(), '', $path);
    }
}
