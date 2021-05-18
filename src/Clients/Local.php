<?php

namespace Torann\LocalizationHelpers\Clients;

use Illuminate\Support\Arr;
use Torann\LocalizationHelpers\Concerns\MangeFiles;
use Torann\LocalizationHelpers\Exceptions\ClientException;

class Local extends AbstractClient
{
    use MangeFiles;

    /**
     * {@inheritDoc}
     */
    public function put(string $locale, array $groups): bool
    {
        $path = $this->config('export_path');

        if (file_exists($path) == false) {
            mkdir($path, 0755, true);
        }

        foreach ($groups as $group) {
            $this->exportGroup($path, $locale, $group);
        }

        $this->addMessage(
            "Successfully exported to [{$path}]"
        );

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $locale, array $groups)
    {
        $path = $this->config('import_path');

        foreach ($groups as $group) {
            $this->importGroup($path, $locale, $group);
        }

        $this->addMessage(
            "Successfully imported from [{$path}]"
        );

        return true;
    }

    /**
     * Import CSV file.
     *
     * @param string $path
     * @param string $locale
     * @param string $group
     *
     * @return bool
     * @throws ClientException
     */
    protected function importGroup(string $path, string $locale, string $group): bool
    {
        // Create output device and write CSV.
        if (($input_fp = fopen("{$path}/{$group}.csv", 'r')) === false) {
            throw new ClientException('Can\'t open the export file!');
        }

        // Set CSV options
        $delimiter = $this->config('delimiter', ',');
        $enclosure = $this->config('enclosure', '"');
        $escape = $this->config('escape', '\\');

        $strings = [];

        // Write CSV lines
        while (($data = fgetcsv($input_fp, 0, $delimiter, $enclosure, $escape)) !== false) {
            $strings[$data[0]] = trim($data[1]);
        }

        fclose($input_fp);

        $this->writeLangList($locale, $group, $strings);

        return true;
    }

    /**
     * Create CSV export file.
     *
     * @param string $path
     * @param string $locale
     * @param string $group
     *
     * @return bool
     * @throws ClientException
     */
    protected function exportGroup(string $path, string $locale, string $group): bool
    {
        if (($out = fopen("{$path}/{$group}.csv", 'w')) === false) {
            throw new ClientException('Can\'t open the import file!');
        }

        // Set CSV options
        $delimiter = $this->config('delimiter', ',');
        $enclosure = $this->config('enclosure', '"');

        foreach ($this->getLocaleValues($locale, $group) as $key => $value) {
            fputcsv($out, [$key, $value], $delimiter, $enclosure);
        }

        fclose($out);

        return true;
    }

    /**
     * Get list of languages
     *
     * @param string $locale
     * @param string $group
     * @param array  $new_translations
     *
     * @return void
     * @throws ClientException
     */
    protected function writeLangList(string $locale, string $group, array $new_translations)
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
        $language_file = $this->getLangPath("{$locale}/{$group}.php");

        // Sanity check for new language files
        $this->ensureFileExists($language_file);

        if (is_writable($language_file) && ($fp = fopen($language_file, 'w')) !== false) {
            // Export values
            $content = var_export($translations[$group], true);

            // Decode all keys
            $content = $this->decodeKey($content);

            fputs($fp, $this->dumpLangArray("<?php\n\nreturn {$content};"));
            fclose($fp);
        } else {
            throw new ClientException(
                "Cannot open language file: {$language_file}"
            );
        }
    }

    /**
     * Create the language file if one does not exist.
     *
     * @param string $path
     */
    protected function ensureFileExists(string $path)
    {
        if (is_file($path) === false) {
            // Create directory
            @mkdir(dirname($path), 0777, true);

            // Make the language file
            touch($path);
        }
    }
}
