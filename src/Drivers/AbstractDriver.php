<?php

namespace Torann\LocalizationHelpers\Drivers;

use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Torann\LocalizationHelpers\Contracts\Driver;
use Torann\LocalizationHelpers\Concerns\ManageTranslationFiles;

abstract class AbstractDriver implements Driver
{
    use ManageTranslationFiles;

    protected array $config;
    protected MessageBag $messages;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->messages = new MessageBag;
    }

    /**
     * {@inheritDoc}
     */
    public function addError($messages): self
    {
        return $this->addMessage($messages, 'errors');
    }

    /**
     * {@inheritDoc}
     */
    public function addMessage($messages, string $key = 'message'): self
    {
        foreach ((array) $messages as $message) {
            $this->messages->add($key, $message);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(): array
    {
        return $this->getMessages('errors');
    }

    /**
     * {@inheritDoc}
     */
    public function getMessages(string $key = 'message'): array
    {
        return $this->messages->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function hasErrors(): bool
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorMessage(string $default = ''): string
    {
        return $this->messages->first('errors') ?: $default;
    }

    /**
     * Get list of languages.
     *
     * @param string $locale
     * @param string $group
     *
     * @return array
     */
    protected function loadLangList(string $locale, string $group): array
    {
        $translations = app('translator')->getLoader()->load($locale, $group);

        return Arr::dot([$group => $translations]);
    }

    /**
     * @param string $locale
     * @param string $group
     *
     * @return Generator
     */
    protected function getLocaleValues(string $locale, string $group): Generator
    {
        foreach ($this->loadLangList($locale, $group) as $key => $value) {
            yield $key => trim($value);
        }
    }

    /**
     * Get a configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function config(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }
}
