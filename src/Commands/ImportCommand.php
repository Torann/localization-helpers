<?php

namespace Torann\LocalizationHelpers\Commands;

use Torann\LocalizationHelpers\Concerns\LocaleInput;

class ImportCommand extends AbstractCommand
{
    use LocaleInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:import
                                {locale : The application locale to be exported}
                                {group : The group or comma separated groups}
                                {--driver= : Driver to use for exporting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Exports the language groups";

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Torann\LocalizationHelpers\Exceptions\DriverException
     */
    public function handle()
    {
        $driver = $this->resolveDriver();

        $driver->get(
            $this->argument('locale'),
            $this->getGroupArgument()
        );

        $this->displayMessages($driver);

        return 0;
    }
}
