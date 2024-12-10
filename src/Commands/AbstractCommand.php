<?php

namespace Torann\LocalizationHelpers\Commands;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Torann\LocalizationHelpers\Contracts\Driver;

abstract class AbstractCommand extends Command
{
    protected string $default_locale;
    protected bool $display = true;
    protected array $config = [];

    public function __construct()
    {
        $this->config = (array) config('localization-helpers', []);
        $this->default_locale = (string) config('app.locale');

        parent::__construct();
    }

    /**
     * Display messages from the driver in the console
     *
     * @param Driver      $driver
     * @param string|null $style
     * @param mixed       $verbosity
     *
     * @return void
     */
    public function displayMessages(Driver $driver, string|null $style = null, mixed $verbosity = null): void
    {
        if ($this->display) {
            foreach ($driver->getMessages('*') as $type => $messages) {
                foreach ($messages as $message) {
                    switch ($type) {
                        case 'error':
                            $this->error($message, $verbosity);
                            break;
                        default:
                            $this->line($message, $style, $verbosity);
                            break;
                    }
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function line($string, $style = null, $verbosity = null)
    {
        if ($this->display) {
            parent::line($string, $style, $verbosity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function info($string, $verbosity = null)
    {
        if ($this->display) {
            parent::info($string, $verbosity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function comment($string, $verbosity = null)
    {
        if ($this->display) {
            parent::comment($string, $verbosity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function question($string, $verbosity = null)
    {
        if ($this->display) {
            parent::question($string, $verbosity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function error($string, $verbosity = null)
    {
        if ($this->display) {
            parent::error($string, $verbosity);
        }
    }

    /**
     * Get configuration value.
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
