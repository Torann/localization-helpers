<?php

namespace Torann\LocalizationHelpers;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Torann\LocalizationHelpers\Contracts\Client;

/**
 * @mixin Client
 */
class ClientManager
{
    protected array $custom_clients = [];
    protected array $clients = [];
    protected array $config = [];

    /**
     * Create a new manager instance.
     *
     * @param array $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get a filesystem instance.
     *
     * @param string|null $name
     *
     * @return Client
     */
    public function client(string $name = null): Client
    {
        $name = $name ?: $this->getDefaultClient();

        return $this->clients[$name] = $this->get($name);
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param string $name
     *
     * @return Client
     */
    protected function get(string $name): Client
    {
        return $this->clients[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param string $name
     *
     * @return Client
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name)
    {
        $config = $this->getConfig($name);

        if (empty($config['driver'])) {
            throw new InvalidArgumentException(
                "Client [{$name}] does not have a configuration."
            );
        }

        $name = $config['driver'];

        if (isset($this->custom_clients[$name])) {
            return $this->custom_clients[$config['driver']]($config);
        }

        $client_class = 'Torann\\LocalizationHelpers\\Clients\\' . Str::studly($name);

        if (class_exists($client_class) === false) {
            throw new InvalidArgumentException("Driver [{$name}] is not supported.");
        }

        return new $client_class($config);
    }

    /**
     * Set the given disk instance.
     *
     * @param string $name
     * @param mixed  $disk
     *
     * @return self
     */
    public function set(string $name, string $disk): self
    {
        $this->clients[$name] = $disk;

        return $this;
    }

    /**
     * Get the client configuration.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getConfig(string $name): array
    {
        return Arr::get($this->config, "clients.{$name}", []);
    }

    /**
     * Get the default client name.
     *
     * @return string
     */
    public function getDefaultClient(): string
    {
        return $this->config['default_client'] ?? '';
    }

    /**
     * Unset the given disk instances.
     *
     * @param array|string $client
     *
     * @return self
     */
    public function forgetClient($client): self
    {
        foreach ((array) $client as $name) {
            unset($this->clients[$name]);
        }

        return $this;
    }

    /**
     * Disconnect the given disk and remove from local cache.
     *
     * @param string|null $name
     *
     * @return void
     */
    public function purge(string $name = null)
    {
        $this->forgetClient(
            $name ?? $this->getDefaultClient()
        );
    }

    /**
     * Register a custom client Closure.
     *
     * @param string   $client
     * @param Closure $callback
     *
     * @return self
     */
    public function extend(string $client, Closure $callback): self
    {
        $this->custom_clients[$client] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->client()->$method(...$parameters);
    }
}
