<?php

namespace Torann\LocalizationHelpers\Commands;

use Torann\LocalizationHelpers\ClientManager;

class ImportCommand extends AbstractCommand
{
    protected ClientManager $client_manager;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:import
                                {locale : The locale to be imported}
                                {group : The group or comma separated groups}
                                {--client=local : Client to use for exporting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Exports the language files to CSV files";

    /**
     * @param ClientManager $client_manager
     */
    public function __construct(ClientManager $client_manager)
    {
        parent::__construct();

        $this->client_manager = $client_manager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Torann\LocalizationHelpers\Exceptions\ClientException
     */
    public function handle()
    {
        $client = $this->client_manager->client(
            $this->option('client')
        );

        $client->get(
            $this->argument('locale'),
            $this->getGroupArgument()
        );

        $this->displayMessages($client);

        return 0;
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
