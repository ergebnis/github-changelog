<?php

namespace Localheinz\GitHub\ChangeLog\Console;

use Github\Client;
use Github\HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

class ChangeLogCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    protected function configure()
    {
        $this
            ->setName('localheinz:changelog')
            ->setDescription('Creates a changelog based on references')
            ->addArgument(
                'vendor',
                Input\InputArgument::REQUIRED,
                'The name of the vendor, e.g., "localheinz"'
            )
            ->addArgument(
                'package',
                Input\InputArgument::REQUIRED,
                'The name of the package, e.g. "github-changelog"'
            )
            ->addArgument(
                'start',
                Input\InputArgument::REQUIRED,
                'The start reference, e.g. "1.0.0"'
            )
            ->addArgument(
                'end',
                Input\InputArgument::REQUIRED,
                'The end reference, e.g. "1.1.0"'
            )
            ->addOption(
                'token',
                't',
                Input\InputOption::VALUE_OPTIONAL,
                'The GitHub token'
            )
        ;
    }

    /**
     * @param Input\InputInterface $input
     * @param Output\OutputInterface $output
     */
    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $this->client();
    }

    /**
     * @return Client
     */
    private function client()
    {
        if (null === $this->client) {
            $this->client = new Client(new HttpClient\CachedHttpClient());
        }

        return $this->client;
    }
}
