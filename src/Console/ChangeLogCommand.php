<?php

namespace Localheinz\GitHub\ChangeLog\Console;

use Github\Api;
use Github\Client;
use Github\HttpClient;
use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Repository;
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
     * @var Repository\PullRequest
     */
    private $pullRequestRepository;

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param Repository\PullRequest $pullRequestRepository
     */
    public function setPullRequestRepository(Repository\PullRequest $pullRequestRepository)
    {
        $this->pullRequestRepository = $pullRequestRepository;
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
                'start-reference',
                Input\InputArgument::REQUIRED,
                'The start reference, e.g. "1.0.0"'
            )
            ->addArgument(
                'end-reference',
                Input\InputArgument::REQUIRED,
                'The end reference, e.g. "1.1.0"'
            )
            ->addOption(
                'auth-token',
                'a',
                Input\InputOption::VALUE_OPTIONAL,
                'The GitHub token'
            )
            ->addOption(
                'template',
                't',
                Input\InputOption::VALUE_OPTIONAL,
                'The template to use for rendering a pull request',
                '- %title% (#%id%)'
            )
        ;
    }

    /**
     * @param Input\InputInterface $input
     * @param Output\OutputInterface $output
     */
    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $client = $this->client();

        $authToken = $input->getOption('auth-token');
        if (null !== $authToken) {
            $client->authenticate(
                $authToken,
                Client::AUTH_HTTP_TOKEN
            );
        }

        $pullRequests = $this->pullRequestRepository()->items(
            $input->getArgument('vendor'),
            $input->getArgument('package'),
            $input->getArgument('start-reference'),
            $input->getArgument('end-reference')
        );

        $template = $input->getOption('template');

        array_walk($pullRequests, function (Entity\PullRequest $pullRequest) use ($output, $template) {
            $message = str_replace(
                [
                    '%title%',
                    '%id%',
                ],
                [
                    $pullRequest->title(),
                    $pullRequest->id(),
                ],
                $template
            );

            $output->writeln($message);
        });
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

    /**
     * @return Repository\PullRequest
     */
    private function pullRequestRepository()
    {
        if (null === $this->pullRequestRepository) {
            $client = $this->client();

            $pullRequestApi = new Api\PullRequest($client);
            $commitApi = new Api\Repository\Commits($client);

            $this->pullRequestRepository = new Repository\PullRequest(
                $pullRequestApi,
                new Repository\Commit($commitApi)
            );
        }

        return $this->pullRequestRepository;
    }
}
