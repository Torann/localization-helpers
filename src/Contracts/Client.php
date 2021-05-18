<?php

namespace Torann\LocalizationHelpers\Contracts;

interface Client
{
    /**
     * Add an error message.
     *
     * @param string|array $messages
     *
     * @return self
     */
    public function addError($messages): self;

    /**
     * Add an error message.
     *
     * @param string|array $messages
     * @param string       $key
     *
     * @return self
     */
    public function addMessage($messages, string $key = 'message'): self;

    /**
     * Get the error messages.
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Get messages for the provided key.
     *
     * @return array
     */
    public function getMessages(string $key = 'message'): array;

    /**
     * Determine if there were any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * Get the first error message.
     *
     * @param string $default
     *
     * @return string
     */
    public function getErrorMessage(string $default = ''): string;

    /**
     * @param string $locale
     * @param array  $groups
     *
     * @return bool
     * @throws \Torann\LocalizationHelpers\Exceptions\ClientException
     */
    public function put(string $locale, array $groups): bool;

    /**
     * @param string $locale
     * @param array  $groups
     *
     * @return string
     * @throws \Torann\LocalizationHelpers\Exceptions\ClientException
     */
    public function get(string $locale, array $groups);
}
