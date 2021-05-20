<?php

namespace Torann\LocalizationHelpers;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Torann\LocalizationHelpers\Contracts\Driver;

class DriverManager
{
    protected array $custom_drivers = [];
    protected array $drivers = [];
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
     * @return Driver
     */
    public function driver(string $name = null): Driver
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param string $name
     *
     * @return Driver
     */
    protected function get(string $name): Driver
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param string $name
     *
     * @return Driver
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name)
    {
        $config = $this->getConfig($name);

        if (empty($config['driver'])) {
            throw new InvalidArgumentException(
                "Driver [{$name}] does not have a configuration."
            );
        }

        $name = $config['driver'];

        if (isset($this->custom_drivers[$name])) {
            return $this->custom_drivers[$name]($config);
        }

        $driver_class = 'Torann\\LocalizationHelpers\\Drivers\\' . Str::studly($name);

        if (class_exists($driver_class) === false) {
            throw new InvalidArgumentException("Driver [{$name}] is not supported.");
        }

        return new $driver_class($config);
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
        $this->drivers[$name] = $disk;

        return $this;
    }

    /**
     * Get the driver configuration.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getConfig(string $name): array
    {
        return Arr::get($this->config, "drivers.{$name}", []);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config['default_driver'] ?? '';
    }

    /**
     * Unset the given disk instances.
     *
     * @param array|string $driver
     *
     * @return self
     */
    public function forgetDriver($driver): self
    {
        foreach ((array) $driver as $name) {
            unset($this->drivers[$name]);
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
        $this->forgetDriver(
            $name ?? $this->getDefaultDriver()
        );
    }

    /**
     * Register a custom driver Closure.
     *
     * @param string   $driver
     * @param Closure $callback
     *
     * @return self
     */
    public function extend(string $driver, Closure $callback): self
    {
        $this->custom_drivers[$driver] = $callback;

        return $this;
    }
}
