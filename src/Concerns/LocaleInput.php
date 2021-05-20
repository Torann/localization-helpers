<?php

namespace Torann\LocalizationHelpers\Concerns;

use Torann\LocalizationHelpers\DriverManager;
use Torann\LocalizationHelpers\Contracts\Driver;

trait LocaleInput
{
    /**
     * Get group argument.
     *
     * @return Driver
     */
    protected function resolveDriver(): Driver
    {
        return app(DriverManager::class)->driver($this->option('driver'));
    }

    /**
     * Get group argument.
     *
     * @return array
     */
    protected function getGroupArgument(): array
    {
        $groups = explode(',', preg_replace('/\s+/', '', $this->argument('group')));

        return array_map(function ($group) {
            return preg_replace('/\\.[^.\\s]{3,4}$/', '', $group);
        }, $groups);
    }
}
