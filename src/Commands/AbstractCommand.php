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

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->config = (array) config('localization-helpers', []);
        $this->default_locale = (string) config('app.locale');

        parent::__construct();
    }

    /**
     * Display messages from the driver in the console
     *
     * @param Driver          $driver
     * @param string|null     $style
     * @param null|int|string $verbosity
     *
     * @return  void
     */
    public function displayMessages(Driver $driver, string $style = null, $verbosity = null)
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
     * Display console message
     *
     * @param string          $string
     * @param string          $style
     * @param null|int|string $verbosity
     *
     * @return  void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        if ($this->display) {
            parent::line($string, $style, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return  void
     */
    public function info($string, $verbosity = null)
    {
        if ($this->display) {
            parent::info($string, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return  void
     */
    public function comment($string, $verbosity = null)
    {
        if ($this->display) {
            parent::comment($string, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return  void
     */
    public function question($string, $verbosity = null)
    {
        if ($this->display) {
            parent::question($string, $verbosity);
        }
    }

    /**
     * Display console message
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return void
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
    public function config(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}
