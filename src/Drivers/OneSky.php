<?php

namespace Torann\LocalizationHelpers\Drivers;

use Exception;
use Onesky\Api\Client as OneSkyClient;
use Torann\LocalizationHelpers\Exceptions\DriverException;

class OneSky extends AbstractDriver
{
    protected OneSkyClient|null $one_sky_client = null;

    /**
     * {@inheritDoc}
     */
    public function put(string $locale, array $groups): bool
    {
        foreach ($groups as $group) {
            $this->uploadFile($locale, $group);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $locale, array $groups): bool
    {
        foreach ($groups as $group) {
            $this->downloadFile($locale, $group);
        }

        return true;
    }

    /**
     * @param string $locale
     * @param string $group
     *
     * @throws Exception
     */
    protected function downloadFile(string $locale, string $group)
    {
        $response = $this->getOneSkyClient()->translations('export', [
            'project_id' => $this->config('project_id'),
            'locale' => $this->toRemoteLocale($locale),
            'source_file_name' => "{$group}.json",
        ]);

        $strings = json_decode($response, true);

        if ($strings === false || is_array($strings) === false) {
            $this->addError(
                "File [{$group}] JSON decoding failed: " . json_last_error_msg()
            );
        } else {
            $this->saveLangGroup($locale, $group, $strings);

            $this->addMessage(
                "File [{$group}] imported successfully"
            );
        }
    }

    /**
     * @param string $locale
     * @param string $group
     *
     * @throws DriverException
     */
    protected function uploadFile(string $locale, string $group)
    {
        $path = $this->createUploadFile($locale, $group);

        $json_response = $this->getOneSkyClient()->files('upload', [
            'project_id' => $this->config('project_id'),
            'file' => $path,
            'file_format' => 'HIERARCHICAL_JSON',
            'locale' => $this->toRemoteLocale($locale),
        ]);

        $json_data = json_decode($json_response, true);
        $status = $json_data['meta']['status'] ?? 500;

        if ($status !== 201) {
            $this->addError(
                "File [{$group}] upload response status: {$status}"
            );
        } else {
            $this->addMessage(
                "File [{$group}] uploaded successfully"
            );
        }
    }

    /**
     * Create CSV export file.
     *
     * @param string $locale
     * @param string $group
     *
     * @return string
     * @throws DriverException
     */
    protected function createUploadFile(string $locale, string $group): string
    {
        $path = $this->getTempPath("{$group}.json");

        if (($out = fopen($path, 'w')) === false) {
            throw new DriverException('Can\'t open the temporary file!');
        }

        $data = [];

        foreach ($this->getLocaleValues($locale, $group) as $key => $value) {
            $data[$key] = $value;
        }

        fwrite($out, json_encode($data));
        fclose($out);

        return $path;
    }

    /**
     * @string $path
     *
     * @return string
     */
    protected function getTempPath(string $path = ''): string
    {
        $base_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'localization_helpers';

        // Sanity check
        if (is_dir($base_path) == false) {
            @mkdir($base_path, 0777, true);
        }

        return $base_path . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    protected function toRemoteLocale(string $locale): string
    {
        return $this->config('locales', [])[$locale] ?? $locale;
    }

    /**
     * @return OneSkyClient
     */
    protected function getOneSkyClient(): OneSkyClient
    {
        if ($this->one_sky_client === null) {
            $this->one_sky_client = (new OneSkyClient())
                ->setApiKey($this->config('api_key'))
                ->setSecret($this->config('secret'));
        }

        return $this->one_sky_client;
    }
}
