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
            $this->createMock(Client::class),
            $this->createMock(Repository\PullRequestRepositoryInterface::class),
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        self::assertSame('generate', $command->getName());
    }

    public function testHasDescription(): void
    {
        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $this->createMock(Repository\PullRequestRepositoryInterface::class),
            $this->createMock(Util\RepositoryResolverInterface::class)
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
            $this->createMock(Client::class),
            $this->createMock(Repository\PullRequestRepositoryInterface::class),
            $this->createMock(Util\RepositoryResolverInterface::class)
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
            $this->createMock(Client::class),
            $this->createMock(Repository\PullRequestRepositoryInterface::class),
            $this->createMock(Util\RepositoryResolverInterface::class)
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

    public function testExecuteAuthenticatesIfTokenOptionIsGiven(): void
    {
        $authToken = $this->faker()->password();

        $client = $this->createMock(Client::class);

        $client
            ->expects(self::once())
            ->method('authenticate')
            ->with(
                self::identicalTo($authToken),
                self::identicalTo(Client::AUTH_HTTP_TOKEN)
            );

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('pullRequests')
            ->willReturn([]);

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::any())
            ->method('items')
            ->willReturn($range);

        $command = new Console\GenerateCommand(
            $client,
            $pullRequestRepository,
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'start-reference' => '0.1.0',
            '--auth-token' => $authToken,
            '--repository' => 'localheinz/github-changelog',
        ]);
    }

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
            $this->createMock(Client::class),
            $this->createMock(Repository\PullRequestRepositoryInterface::class),
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => '0.1.0',
            '--repository' => $repository,
        ]);

        self::assertSame(1, $exitCode);
        self::assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteFailsIfRepositoryCannotBeResolved(): void
    {
        $repositoryResolver = $this->createMock(Util\RepositoryResolverInterface::class);

        $repositoryResolver
            ->expects(self::once())
            ->method('resolve')
            ->with(
                self::identicalTo('upstream'),
                self::identicalTo('origin')
            )
            ->willThrowException(new Exception\RuntimeException());

        $expectedMessage = 'Unable to resolve repository, please specify using --repository option.';

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $this->createMock(Repository\PullRequestRepositoryInterface::class),
            $repositoryResolver
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => '0.1.0',
        ]);

        self::assertSame(1, $exitCode);
        self::assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteDelegatesToPullRequestRepositoryUsingRepositoryResolvedFromGitMetaData(): void
    {
        $faker = $this->faker();

        $owner = $faker->slug();
        $name = $faker->slug();

        $repository = Resource\Repository::fromOwnerAndName(
            $owner,
            $name
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('pullRequests')
            ->willReturn([]);

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::once())
            ->method('items')
            ->with(
                self::identicalTo($repository),
                self::identicalTo($startReference),
                self::identicalTo($endReference)
            )
            ->willReturn($range);

        $repositoryResolver = $this->createMock(Util\RepositoryResolverInterface::class);

        $repositoryResolver
            ->expects(self::once())
            ->method('resolve')
            ->with(
                self::identicalTo('upstream'),
                self::identicalTo('origin')
            )
            ->willReturn($repository);

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $pullRequestRepository,
            $repositoryResolver
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ]);
    }

    public function testExecuteDelegatesToPullRequestRepositoryUsingRepositorySpecifiedInOptions(): void
    {
        $faker = $this->faker();

        $owner = $faker->unique()->slug();
        $name = $faker->unique()->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('pullRequests')
            ->willReturn([]);

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::once())
            ->method('items')
            ->with(
                self::logicalAnd(
                    self::isInstanceOf(Resource\RepositoryInterface::class),
                    self::callback(static function (Resource\RepositoryInterface $repository) use ($owner, $name) {
                        return $repository->owner() === $owner
                            && $repository->name() === $name;
                    })
                ),
                self::identicalTo($startReference),
                self::identicalTo($endReference)
            )
            ->willReturn($range);

        $repositoryResolver = $this->createMock(Util\RepositoryResolverInterface::class);

        $repositoryResolver
            ->expects(self::never())
            ->method(self::anything());

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $pullRequestRepository,
            $repositoryResolver
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

    public function testExecuteRendersMessageIfNoPullRequestsWereFound(): void
    {
        $faker = $this->faker();

        $expectedMessage = 'Could not find any pull requests';

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('pullRequests')
            ->willReturn([]);

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::any())
            ->method('items')
            ->willReturn($range);

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $pullRequestRepository,
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $faker->sha1,
            'end-reference' => $faker->sha1,
            '--repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
        ]);

        self::assertSame(0, $exitCode);
        self::assertRegExp('@' . $expectedMessage . '@', $tester->getDisplay());
    }

    public function testExecuteRendersDifferentMessageIfNoPullRequestsWereFoundAndNoEndReferenceWasGiven(): void
    {
        $faker = $this->faker();

        $expectedMessage = 'Could not find any pull requests';

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('pullRequests')
            ->willReturn([]);

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::any())
            ->method('items')
            ->willReturn($range);

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $pullRequestRepository,
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $faker->sha1,
            'end-reference' => null,
            '--repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
        ]);

        self::assertSame(0, $exitCode);
        self::assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteRendersPullRequestsWithTemplate(): void
    {
        $faker = $this->faker();

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

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('pullRequests')
            ->willReturn($pullRequests);

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::any())
            ->method('items')
            ->willReturn($range);

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $pullRequestRepository,
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $faker->sha1,
            'end-reference' => $faker->sha1,
            '--repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
            '--template' => $template,
        ]);

        self::assertSame(0, $exitCode);

        foreach ($expectedMessages as $expectedMessage) {
            self::assertContains($expectedMessage, $tester->getDisplay());
        }
    }

    public function testExecuteRendersDifferentMessageWhenNoEndReferenceWasGiven(): void
    {
        $faker = $this->faker();

        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $expectedMessage = \sprintf(
            'Found %d pull request%s',
            $count,
            1 === $count ? '' : 's'
        );

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('pullRequests')
            ->willReturn($pullRequests);

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::any())
            ->method('items')
            ->willReturn($range);

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $pullRequestRepository,
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $faker->sha1,
            'end-reference' => null,
            '--repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
        ]);

        self::assertSame(0, $exitCode);
        self::assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteHandlesExceptionsThrownWhenFetchingPullRequests(): void
    {
        $faker = $this->faker();

        $exception = new \Exception('Wait, this should not happen!');

        $pullRequestRepository = $this->createMock(Repository\PullRequestRepositoryInterface::class);

        $pullRequestRepository
            ->expects(self::any())
            ->method('items')
            ->willThrowException($exception);

        $expectedMessage = \sprintf(
            'An error occurred: %s',
            $exception->getMessage()
        );

        $command = new Console\GenerateCommand(
            $this->createMock(Client::class),
            $pullRequestRepository,
            $this->createMock(Util\RepositoryResolverInterface::class)
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => $faker->sha1,
            'end-reference' => $faker->sha1,
            '--repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
        ]);

        self::assertSame(1, $exitCode);
        self::assertContains($expectedMessage, $tester->getDisplay());
    }

    private function pullRequest(): Resource\PullRequestInterface
    {
        $faker = $this->faker();

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
