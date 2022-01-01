<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2022 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Console;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Repository;
use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\GitHub\Changelog\Util;
use Github\Client;
use Symfony\Component\Console;
use Symfony\Component\Stopwatch;

final class GenerateCommand extends Console\Command\Command
{
    private Client $client;

    private Repository\PullRequestRepositoryInterface $pullRequestRepository;

    private Util\RepositoryResolverInterface $repositoryResolver;

    private Stopwatch\Stopwatch $stopwatch;

    public function __construct(
        Client $client,
        Repository\PullRequestRepositoryInterface $pullRequestRepository,
        Util\RepositoryResolverInterface $repositoryResolver,
        Stopwatch\Stopwatch $stopwatch
    ) {
        parent::__construct();

        $this->client = $client;
        $this->pullRequestRepository = $pullRequestRepository;
        $this->repositoryResolver = $repositoryResolver;
        $this->stopwatch = $stopwatch;
    }

    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setDescription('Generates a changelog from merged pull requests found between commit references')
            ->addArgument(
                'start-reference',
                Console\Input\InputArgument::REQUIRED,
                'The start reference, e.g. "1.0.0"'
            )
            ->addArgument(
                'end-reference',
                Console\Input\InputArgument::OPTIONAL,
                'The end reference, e.g. "1.1.0"'
            )
            ->addOption(
                'auth-token',
                'a',
                Console\Input\InputOption::VALUE_REQUIRED,
                'The GitHub token'
            )
            ->addOption(
                'repository',
                'r',
                Console\Input\InputOption::VALUE_REQUIRED,
                'The repository, e.g. "ergebnis/github-changelog"'
            )
            ->addOption(
                'template',
                't',
                Console\Input\InputOption::VALUE_REQUIRED,
                'The template to use for rendering a pull request',
                '- %pullrequest.title% (#%pullrequest.number%), by @%pullrequest.author.login%'
            );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $this->stopwatch->start('changelog');

        $io = new Console\Style\SymfonyStyle(
            $input,
            $output
        );

        $io->title('Localheinz GitHub Changelog');

        $authToken = $input->getOption('auth-token');

        if (\is_string($authToken)) {
            $this->client->authenticate(
                $authToken,
                Client::AUTH_ACCESS_TOKEN
            );
        }

        $repositoryName = $input->getOption('repository');

        if (\is_string($repositoryName)) {
            try {
                $repository = Resource\Repository::fromString($repositoryName);
            } catch (Exception\InvalidArgumentException $exception) {
                $io->error(\sprintf(
                    'Repository "%s" appears to be invalid.',
                    $repositoryName
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

        /** @var string $startReference */
        $startReference = $input->getArgument('start-reference');

        /** @var string $endReference */
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

        if (0 === \count($pullRequests)) {
            $io->warning('Could not find any pull requests');
        } else {
            /** @var string $template */
            $template = $input->getOption('template');

            $pullRequests = \array_reverse($pullRequests);

            \array_walk($pullRequests, static function (Resource\PullRequestInterface $pullRequest) use ($output, $template): void {
                $message = \str_replace(
                    [
                        '%pullrequest.title%',
                        '%pullrequest.number%',
                        '%pullrequest.author.login%',
                        '%pullrequest.author.htmlUrl%',
                    ],
                    [
                        $pullRequest->title(),
                        (string) $pullRequest->number(),
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

    private function range(string $startReference, ?string $endReference = null): string
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

    private function formatStopwatchEvent(Stopwatch\StopwatchEvent $event): string
    {
        return \sprintf(
            'Time: %s, Memory: %s.',
            Console\Helper\Helper::formatTime($event->getDuration() / 1000),
            Console\Helper\Helper::formatMemory($event->getMemory())
        );
    }
}
