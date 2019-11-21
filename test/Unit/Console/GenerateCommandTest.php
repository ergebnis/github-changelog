<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Console;

use Github\Client;
use Localheinz\GitHub\ChangeLog\Console;
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\GitHub\ChangeLog\Util;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;
use Prophecy\Argument;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \Localheinz\GitHub\ChangeLog\Console\GenerateCommand
 */
final class GenerateCommandTest extends Framework\TestCase
{
    use Helper;

    public function testHasName(): void
    {
        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(Repository\PullRequestRepositoryInterface::class)->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        self::assertSame('generate', $command->getName());
    }

    public function testHasDescription(): void
    {
        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(Repository\PullRequestRepositoryInterface::class)->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        self::assertSame('Generates a changelog from merged pull requests found between commit references', $command->getDescription());
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerGenerateCommandArgument
     *
     * @param string $name
     * @param bool   $isRequired
     * @param string $description
     */
    public function testArgument(string $name, bool $isRequired, string $description): void
    {
        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(Repository\PullRequestRepositoryInterface::class)->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        self::assertTrue($command->getDefinition()->hasArgument($name));

        /** @var Input\InputArgument $argument */
        $argument = $command->getDefinition()->getArgument($name);

        self::assertSame($name, $argument->getName());
        self::assertSame($isRequired, $argument->isRequired());
        self::assertSame($description, $argument->getDescription());
    }

    public function providerArgument(): \Generator
    {
        $arguments = [
            'start-reference' => [
                true,
                'The start reference, e.g. "1.0.0"',
            ],
            'end-reference' => [
                false,
                'The end reference, e.g. "1.1.0"',
            ],
        ];

        foreach ($arguments as $name => [$isRequired, $description]) {
            yield $name => [
                $name,
                $isRequired,
                $description,
            ];
        }
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerGenerateCommandOption
     *
     * @param string $name
     * @param string $shortcut
     * @param bool   $isValueRequired
     * @param string $description
     * @param mixed  $default
     */
    public function testOption(string $name, string $shortcut, bool $isValueRequired, string $description, $default): void
    {
        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(Repository\PullRequestRepositoryInterface::class)->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        self::assertTrue($command->getDefinition()->hasOption($name));

        /** @var Input\InputOption $option */
        $option = $command->getDefinition()->getOption($name);

        self::assertSame($name, $option->getName());
        self::assertSame($shortcut, $option->getShortcut());
        self::assertSame($isValueRequired, $option->isValueRequired());
        self::assertSame($description, $option->getDescription());
        self::assertSame($default, $option->getDefault());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testExecuteAuthenticatesIfTokenOptionIsGiven(): void
    {
        $faker = self::faker();

        $startReference = $faker->sha1;
        $endReference = null;
        $owner = $faker->slug;
        $name = $faker->slug;

        $authToken = $faker->password();

        $client = $this->prophesize(Client::class);

        $client
            ->authenticate(
                Argument::is($authToken),
                Argument::is(Client::AUTH_HTTP_TOKEN)
            )
            ->shouldBeCalled();

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->pullRequests()
            ->shouldBeCalled()
            ->willReturn([]);

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::that(static function (Resource\Repository $repository) use ($owner, $name) {
                    return $repository->owner() === $owner
                        && $repository->name() === $name;
                }),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $command = new Console\GenerateCommand(
            $client->reveal(),
            $pullRequestRepository->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $startReference,
            '--auth-token' => $authToken,
            '--repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
        ]);

        self::assertSame(0, $exitCode);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testExecuteFailsIfRepositoryIsInvalid(): void
    {
        $repository = $this->repositoryFrom(
            '🤓',
            '🤣'
        );

        $expectedMessage = \sprintf(
            'Repository "%s" appears to be invalid.',
            $repository
        );

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(Repository\PullRequestRepositoryInterface::class)->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => '0.1.0',
            '--repository' => $repository,
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteFailsIfRepositoryCannotBeResolved(): void
    {
        $repositoryResolver = $this->prophesize(Util\RepositoryResolverInterface::class);

        $repositoryResolver
            ->resolve(
                Argument::is('upstream'),
                Argument::is('origin')
            )
            ->shouldBeCalled()
            ->willThrow(new Exception\RuntimeException());

        $expectedMessage = 'Unable to resolve repository, please specify using --repository option.';

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(Repository\PullRequestRepositoryInterface::class)->reveal(),
            $repositoryResolver->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => '0.1.0',
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString($expectedMessage, $tester->getDisplay());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testExecuteDelegatesToPullRequestRepositoryUsingRepositoryResolvedFromGitMetaData(): void
    {
        $faker = self::faker();

        $owner = $faker->slug();
        $name = $faker->slug();

        $repository = Resource\Repository::fromOwnerAndName(
            $owner,
            $name
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->pullRequests()
            ->shouldBeCalled()
            ->willReturn([]);

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::is($repository),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $repositoryResolver = $this->prophesize(Util\RepositoryResolverInterface::class);

        $repositoryResolver
            ->resolve(
                Argument::is('upstream'),
                Argument::is('origin')
            )
            ->shouldBeCalled()
            ->willReturn($repository);

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $pullRequestRepository->reveal(),
            $repositoryResolver->reveal()
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ]);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testExecuteDelegatesToPullRequestRepositoryUsingRepositorySpecifiedInOptions(): void
    {
        $faker = self::faker();

        $owner = $faker->unique()->slug();
        $name = $faker->unique()->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->pullRequests()
            ->shouldBeCalled()
            ->willReturn([]);

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::that(static function (Resource\Repository $repository) use ($owner, $name) {
                    return $repository->owner() === $owner
                        && $repository->name() === $name;
                }),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $repositoryResolver = $this->prophesize(Util\RepositoryResolverInterface::class);

        $repositoryResolver
            ->resolve()
            ->shouldNotBeCalled();

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $pullRequestRepository->reveal(),
            $repositoryResolver->reveal()
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
        ]);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testExecuteRendersMessageIfNoPullRequestsWereFound(): void
    {
        $faker = self::faker();

        $owner = $faker->slug();
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $expectedMessage = 'Could not find any pull requests';

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->pullRequests()
            ->shouldBeCalled()
            ->willReturn([]);

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::that(static function (Resource\Repository $repository) use ($owner, $name) {
                    return $repository->owner() === $owner
                        && $repository->name() === $name;
                }),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $pullRequestRepository->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
        ]);

        self::assertSame(0, $exitCode);
        self::assertRegExp('@' . $expectedMessage . '@', $tester->getDisplay());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testExecuteRendersDifferentMessageIfNoPullRequestsWereFoundAndNoEndReferenceWasGiven(): void
    {
        $faker = self::faker();

        $owner = $faker->slug();
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = null;

        $expectedMessage = 'Could not find any pull requests';

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->pullRequests()
            ->shouldBeCalled()
            ->willReturn([]);

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::that(static function (Resource\Repository $repository) use ($owner, $name) {
                    return $repository->owner() === $owner
                        && $repository->name() === $name;
                }),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $pullRequestRepository->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString($expectedMessage, $tester->getDisplay());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\PullRequest
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     * @uses \Localheinz\GitHub\ChangeLog\Resource\User
     */
    public function testExecuteRendersPullRequestsWithTemplate(): void
    {
        $faker = self::faker();

        $owner = $faker->slug();
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $template = '- %pullrequest.title% (#pullrequest.%number%), submitted by [%pullrequest.author.login%](%pullrequest.author.htmlUrl%)';

        $expectedMessages = [
            \sprintf(
                'Found %d pull request%s',
                $count,
                1 === $count ? '' : 's'
            ),
        ];

        \array_walk($pullRequests, static function (Resource\PullRequestInterface $pullRequest) use (&$expectedMessages, $template): void {
            $expectedMessages[] = \str_replace(
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
        });

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->pullRequests()
            ->shouldBeCalled()
            ->willReturn($pullRequests);

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::that(static function (Resource\Repository $repository) use ($owner, $name) {
                    return $repository->owner() === $owner
                        && $repository->name() === $name;
                }),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $pullRequestRepository->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
            '--template' => $template,
        ]);

        self::assertSame(0, $exitCode);

        foreach ($expectedMessages as $expectedMessage) {
            self::assertStringContainsString($expectedMessage, $tester->getDisplay());
        }
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\PullRequest
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     * @uses \Localheinz\GitHub\ChangeLog\Resource\User
     */
    public function testExecuteRendersDifferentMessageWhenNoEndReferenceWasGiven(): void
    {
        $faker = self::faker();

        $owner = $faker->slug();
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = null;

        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $expectedMessage = \sprintf(
            'Found %d pull request%s',
            $count,
            1 === $count ? '' : 's'
        );

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->pullRequests()
            ->shouldBeCalled()
            ->willReturn($pullRequests);

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::that(static function (Resource\Repository $repository) use ($owner, $name) {
                    return $repository->owner() === $owner
                        && $repository->name() === $name;
                }),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $pullRequestRepository->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString($expectedMessage, $tester->getDisplay());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testExecuteHandlesExceptionsThrownWhenFetchingPullRequests(): void
    {
        $faker = self::faker();

        $owner = $faker->slug();
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $exception = new \Exception('Wait, this should not happen!');

        $pullRequestRepository = $this->prophesize(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->items(
                Argument::that(static function (Resource\Repository $repository) use ($owner, $name) {
                    return $repository->owner() === $owner
                        && $repository->name() === $name;
                }),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willThrow($exception);

        $expectedMessage = \sprintf(
            'An error occurred: %s',
            $exception->getMessage()
        );

        $command = new Console\GenerateCommand(
            $this->prophesize(Client::class)->reveal(),
            $pullRequestRepository->reveal(),
            $this->prophesize(Util\RepositoryResolverInterface::class)->reveal()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString($expectedMessage, $tester->getDisplay());
    }

    private function pullRequest(): Resource\PullRequestInterface
    {
        $faker = self::faker();

        $number = $faker->unique()->numberBetween(1);
        $title = $faker->unique()->sentence();
        $author = new Resource\User($faker->slug());

        return new Resource\PullRequest(
            $number,
            $title,
            $author
        );
    }

    /**
     * @param int $count
     *
     * @return Resource\PullRequestInterface[]
     */
    private function pullRequests(int $count): array
    {
        $pullRequests = [];

        for ($i = 0; $i < $count; ++$i) {
            $pullRequests[] = $this->pullRequest();
        }

        return $pullRequests;
    }

    private function repositoryFrom(string $owner, string $name): string
    {
        return \sprintf(
            '%s/%s',
            $owner,
            $name
        );
    }
}
