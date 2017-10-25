<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Console;

use Github\Client;
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\GitHub\ChangeLog\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

final class GenerateCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Repository\PullRequestRepositoryInterface
     */
    private $pullRequestRepository;

    /**
     * @var Util\RepositoryResolverInterface
     */
    private $repositoryResolver;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(
        Client $client,
        Repository\PullRequestRepositoryInterface $pullRequestRepository,
        Util\RepositoryResolverInterface $repositoryResolver
    ) {
        parent::__construct();

        $this->client = $client;
        $this->pullRequestRepository = $pullRequestRepository;
        $this->repositoryResolver = $repositoryResolver;
        $this->stopwatch = new Stopwatch();
    }

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generates a changelog from merged pull requests found between commit references')
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
                Input\InputOption::VALUE_REQUIRED,
                'The GitHub token'
            )
            ->addOption(
                'repository',
                'r',
                Input\InputOption::VALUE_REQUIRED,
                'The repository, e.g. "localheinz/github-changelog"'
            )
            ->addOption(
                'template',
                't',
                Input\InputOption::VALUE_REQUIRED,
                'The template to use for rendering a pull request',
                '- %pullrequest.title% (#%pullrequest.number%), by @%pullrequest.author.login%'
            );
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
    {
        $this->stopwatch->start('changelog');

        $io = new SymfonyStyle(
            $input,
            $output
        );

        $io->title('Localheinz GitHub Changelog');

        $authToken = $input->getOption('auth-token');

        if (null !== $authToken) {
            $this->client->authenticate(
                $authToken,
                Client::AUTH_HTTP_TOKEN
            );
        }

        $repository = $input->getOption('repository');

        if (null !== $repository) {
            try {
                $repository = Resource\Repository::fromString($repository);
            } catch (Exception\InvalidArgumentException $exception) {
                $io->error(\sprintf(
                    'Repository "%s" appears to be invalid.',
                    $repository
                ));

                return 1;
            }
        } else {
            try {
                $repository = $this->repositoryResolver->resolve(
                    'upstream',
                    'origin'
                );
            } catch (Exception\RuntimeException $exception) {
                $io->error('Unable to resolve repository, please specify using --repository option.');

                return 1;
            }
        }

        $startReference = $input->getArgument('start-reference');
        $endReference = $input->getArgument('end-reference');

        $range = $this->range(
            $startReference,
            $endReference
        );

        $io->section(\sprintf(
            'Pull Requests merged in %s %s',
            $repository,
            $range
        ));

        try {
            $range = $this->pullRequestRepository->items(
                $repository,
                $startReference,
                $endReference
            );
        } catch (\Exception $exception) {
            $io->error(\sprintf(
                'An error occurred: %s',
                $exception->getMessage()
            ));

            return 1;
        }

        $pullRequests = $range->pullRequests();

        if (!\count($pullRequests)) {
            $io->warning('Could not find any pull requests');
        } else {
            $template = $input->getOption('template');

            $pullRequests = \array_reverse($pullRequests);

            \array_walk($pullRequests, function (Resource\PullRequestInterface $pullRequest) use ($output, $template) {
                $message = \str_replace(
                    [
                        '%pullrequest.title%',
                        '%pullrequest.number%',
                        '%pullrequest.author.login%',
                        '%pullrequest.author.htmlUrl%',
                    ],
                    [
                        $pullRequest->title(),
                        $pullRequest->number(),
                        $pullRequest->author()->login(),
                        $pullRequest->author()->htmlUrl(),
                    ],
                    $template
                );

                $output->writeln($message);
            });

            $io->newLine();

            $io->success(\sprintf(
                'Found %d pull request%s.',
                \count($pullRequests),
                1 === \count($pullRequests) ? '' : 's'
            ));
        }

        $event = $this->stopwatch->stop('changelog');

        $io->writeln($this->formatStopwatchEvent($event));

        return 0;
    }

    private function range(string $startReference, string $endReference = null): string
    {
        if (null === $endReference) {
            return \sprintf(
                'since %s',
                $startReference
            );
        }

        return \sprintf(
            'between %s and %s',
            $startReference,
            $endReference
        );
    }

    private function formatStopwatchEvent(StopwatchEvent $event): string
    {
        return \sprintf(
            'Time: %s, Memory: %s.',
            Helper::formatTime($event->getDuration() / 1000),
            Helper::formatMemory($event->getMemory())
        );
    }
}
