<?php

namespace Torann\LocalizationHelpers\Drivers;

use Exception;
use Illuminate\Support\Str;
use Torann\LocalizationHelpers\Exceptions\DriverException;

class Local extends AbstractDriver
{
    /**
     * {@inheritDoc}
     */
    public function put(string $locale, array $groups): bool
    {
        $path = $this->config('export_path');

        if (is_dir($path) == false) {
            mkdir($path, 0755, true);
        }

        $export_method = 'export' . Str::studly($this->config('format'));

        // Validate format
        if (method_exists($this, $export_method) === false) {
            throw new DriverException(
                '[' . $this->config('format') . '] is not a valid export format.'
            );
        }

        foreach ($groups as $group) {
            $this->{$export_method}($path, $locale, $group);
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

        $import_method = 'import' . Str::studly($this->config('format'));

        // Validate format
        if (method_exists($this, $import_method) === false) {
            throw new DriverException(
                '[' . $this->config('format') . '] is not a valid import format.'
            );
        }

        foreach ($groups as $group) {
            $this->{$import_method}($path, $locale, $group);
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
     * @throws DriverException
     * @throws Exception
     */
    protected function importJson(string $path, string $locale, string $group): bool
    {
        $file_path = "{$path}/{$group}.json";

        if (is_file($file_path) === false) {
            throw new DriverException("Import file [{$group}.json] not found");
        }

        $strings = json_decode(file_get_contents($file_path), true);

        // Validate json decoding
        if ($strings === false) {
            throw new DriverException('JSON decoding failed: ' . json_last_error_msg());
        }

        $this->saveLangGroup($locale, $group, $strings);

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
     * @throws DriverException
     * @throws Exception
     */
    protected function importCsv(string $path, string $locale, string $group): bool
    {
        // Create output device and write CSV.
        if (($input_fp = fopen("{$path}/{$group}.csv", 'r')) === false) {
            throw new DriverException('Can\'t open the export file!');
        }

        // Set CSV options
        $delimiter = $this->config('options.delimiter', ',');
        $enclosure = $this->config('options.enclosure', '"');
        $escape = $this->config('options.escape', '\\');

        $strings = [];

        // Write CSV lines
        while (($data = fgetcsv($input_fp, 0, $delimiter, $enclosure, $escape)) !== false) {
            $strings[$data[0]] = trim($data[1]);
        }

        fclose($input_fp);

        $this->saveLangGroup($locale, $group, $strings);

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
     * @throws DriverException
     */
    protected function exportJson(string $path, string $locale, string $group): bool
    {
        $json = [];

        foreach ($this->getLocaleValues($locale, $group) as $key => $value) {
            $json[$key] = $value;
        }

        $json = json_encode(
            $json, $this->config('options.flags', 0)
        );

        // Validate json encoding
        if ($json === false) {
            throw new DriverException('JSON encoding failed: ' . json_last_error_msg());
        }

        return file_put_contents("{$path}/{$group}.json", $json) !== false;
    }

    /**
     * Create CSV export file.
     *
     * @param string $path
     * @param string $locale
     * @param string $group
     *
     * @return bool
     * @throws DriverException
     */
    protected function exportCsv(string $path, string $locale, string $group): bool
    {
        if (($out = fopen("{$path}/{$group}.csv", 'w')) === false) {
            throw new DriverException('Can\'t open the import file!');
        }

        // Set CSV options
        $delimiter = $this->config('options.delimiter', ',');
        $enclosure = $this->config('options.enclosure', '"');
        $escape = $this->config('options.escape', '\\');

        foreach ($this->getLocaleValues($locale, $group) as $key => $value) {
            fputcsv($out, [$key, $value], $delimiter, $enclosure, $escape);
        }

        fclose($out);

        return true;
    }
}
