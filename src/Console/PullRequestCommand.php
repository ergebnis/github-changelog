<?php

namespace Localheinz\GitHub\ChangeLog\Console;

use Exception;
use Github\Api;
use Github\Client;
use Github\HttpClient;
use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

class PullRequestCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Repository\PullRequestRepository
     */
    private $pullRequestRepository;

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function setPullRequestRepository(Repository\PullRequestRepository $pullRequestRepository)
    {
        $this->pullRequestRepository = $pullRequestRepository;
    }

    protected function configure()
    {
        $this
            ->setName('pull-request')
            ->setDescription('Creates a changelog from merged pull requests between references')
            ->addArgument(
                'owner',
                Input\InputArgument::REQUIRED,
                'The owner, e.g., "localheinz"'
            )
            ->addArgument(
                'repository',
                Input\InputArgument::REQUIRED,
                'The repository, e.g. "github-changelog"'
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

        $owner = $input->getArgument('owner');
        $repository = $input->getArgument('repository');
        $startReference = $input->getArgument('start-reference');
        $endReference = $input->getArgument('end-reference');

        try {
            $pullRequests = $this->pullRequestRepository()->items(
                $owner,
                $repository,
                $startReference,
                $endReference
            );
        } catch (Exception $exception) {
            $output->writeln(sprintf(
                '<error>An error occurred: %s</error>',
                $exception->getMessage()
            ));

            return 1;
        }

        if (!count($pullRequests)) {
            $output->writeln(sprintf(
                'Could not find any pull requests for <info>%s/%s</info> between <info>%s</info> and <info>%s</info>.',
                $owner,
                $repository,
                $startReference,
                $endReference
            ));

            return 0;
        }

        $output->writeln(sprintf(
            'Found <info>%s</info> pull request(s) for <info>%s/%s</info> between <info>%s</info> and <info>%s</info>.',
            count($pullRequests),
            $owner,
            $repository,
            $startReference,
            $endReference
        ));

        $output->writeln('');

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

        return 0;
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
     * @return Repository\PullRequestRepository
     */
    private function pullRequestRepository()
    {
        if (null === $this->pullRequestRepository) {
            $client = $this->client();

            $pullRequestApi = new Api\PullRequest($client);
            $commitApi = new Api\Repository\Commits($client);

            $this->pullRequestRepository = new Repository\PullRequestRepository(
                $pullRequestApi,
                new Repository\CommitRepository($commitApi)
            );
        }

        return $this->pullRequestRepository;
    }
}
