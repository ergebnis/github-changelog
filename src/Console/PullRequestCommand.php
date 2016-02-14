<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Console;

use Exception;
use Github\Api;
use Github\Client;
use Github\HttpClient;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

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

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(Client $client = null, Repository\PullRequestRepository $pullRequestRepository = null)
    {
        parent::__construct();

        $this->client = $client ?: new Client(new HttpClient\CachedHttpClient());
        $this->pullRequestRepository = $pullRequestRepository ?: new Repository\PullRequestRepository(
            new Api\PullRequest($this->client),
            new Repository\CommitRepository(new Api\Repository\Commits($this->client))
        );
        $this->stopwatch = new Stopwatch();
    }

    protected function configure()
    {
        $this
            ->setName('pull-request')
            ->setDescription('Creates a changelog from pull requests merged between references')
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
                Input\InputArgument::OPTIONAL,
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
        $this->stopwatch->start('changelog');

        $authToken = $input->getOption('auth-token');
        if (null !== $authToken) {
            $this->client->authenticate(
                $authToken,
                Client::AUTH_HTTP_TOKEN
            );
        }

        $owner = $input->getArgument('owner');
        $repository = $input->getArgument('repository');
        $startReference = $input->getArgument('start-reference');
        $endReference = $input->getArgument('end-reference');

        try {
            $pullRequests = $this->pullRequestRepository->items(
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

        if ($endReference === null) {
            $range = sprintf(
                'since <info>%s</info>',
                $startReference
            );
        } else {
            $range = sprintf(
                'between <info>%s</info> and <info>%s</info>',
                $startReference,
                $endReference
            );
        }

        if (!count($pullRequests)) {
            $output->writeln(sprintf(
                'Could not find any pull requests merged for <info>%s/%s</info> %s.',
                $owner,
                $repository,
                $range
            ));
        } else {
            $output->writeln(sprintf(
                'Found <info>%s</info> pull request(s) merged for <info>%s/%s</info> %s.',
                count($pullRequests),
                $owner,
                $repository,
                $range
            ));

            $output->writeln('');

            $template = $input->getOption('template');

            array_walk($pullRequests, function (Resource\PullRequestInterface $pullRequest) use ($output, $template) {

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

        $event = $this->stopwatch->stop('changelog');

        $output->writeln('');
        $output->writeln($this->formatStopwatchEvent($event));

        return 0;
    }

    /**
     * @param StopwatchEvent $event
     *
     * @return string
     */
    private function formatStopwatchEvent(StopwatchEvent $event)
    {
        return sprintf(
            'Time: %s, Memory: %s.',
            Helper::formatTime($event->getDuration() / 1000),
            Helper::formatMemory($event->getMemory())
        );
    }
}
