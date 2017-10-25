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

final class GenerateCommandTest extends Framework\TestCase
{
    use Helper;

    public function testHasName()
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock(),
            $this->createRepositoryResolverMock()
        );

        $this->assertSame('generate', $command->getName());
    }

    public function testHasDescription()
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock(),
            $this->createRepositoryResolverMock()
        );

        $this->assertSame('Generates a changelog from merged pull requests found between commit references', $command->getDescription());
    }

    /**
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerGenerateCommandArgument
     *
     * @param string $name
     * @param bool   $isRequired
     * @param string $description
     */
    public function testArgument(string $name, bool $isRequired, string $description)
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock(),
            $this->createRepositoryResolverMock()
        );

        $this->assertTrue($command->getDefinition()->hasArgument($name));

        /* @var Input\InputArgument $argument */
        $argument = $command->getDefinition()->getArgument($name);

        $this->assertSame($name, $argument->getName());
        $this->assertSame($isRequired, $argument->isRequired());
        $this->assertSame($description, $argument->getDescription());
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

        foreach ($arguments as $name => list($isRequired, $description)) {
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
    public function testOption(string $name, string $shortcut, bool $isValueRequired, string $description, $default)
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock(),
            $this->createRepositoryResolverMock()
        );

        $this->assertTrue($command->getDefinition()->hasOption($name));

        /* @var Input\InputOption $option */
        $option = $command->getDefinition()->getOption($name);

        $this->assertSame($name, $option->getName());
        $this->assertSame($shortcut, $option->getShortcut());
        $this->assertSame($isValueRequired, $option->isValueRequired());
        $this->assertSame($description, $option->getDescription());
        $this->assertSame($default, $option->getDefault());
    }

    public function testExecuteAuthenticatesIfTokenOptionIsGiven()
    {
        $authToken = $this->faker()->password();

        $client = $this->createClientMock();

        $client
            ->expects($this->once())
            ->method('authenticate')
            ->with(
                $this->identicalTo($authToken),
                $this->identicalTo(Client::AUTH_HTTP_TOKEN)
            );

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock());

        $command = new Console\GenerateCommand(
            $client,
            $pullRequestRepository,
            $this->createRepositoryResolverMock()
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'start-reference' => '0.1.0',
            '--auth-token' => $authToken,
            '--repository' => 'localheinz/github-changelog',
        ]);
    }

    public function testExecuteFailsIfRepositoryIsInvalid()
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
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock(),
            $this->createRepositoryResolverMock()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => '0.1.0',
            '--repository' => $repository,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteFailsIfRepositoryCannotBeResolved()
    {
        $repositoryResolver = $this->createRepositoryResolverMock();

        $repositoryResolver
            ->expects($this->once())
            ->method('resolve')
            ->with(
                $this->identicalTo('upstream'),
                $this->identicalTo('origin')
            )
            ->willThrowException(new Exception\RuntimeException());

        $expectedMessage = 'Unable to resolve repository, please specify using --repository option.';

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock(),
            $repositoryResolver
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'start-reference' => '0.1.0',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteDelegatesToPullRequestRepositoryUsingRepositoryResolvedFromGitMetaData()
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

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->identicalTo($repository),
                $this->identicalTo($startReference),
                $this->identicalTo($endReference)
            )
            ->willReturn($this->createRangeMock());

        $repositoryResolver = $this->createRepositoryResolverMock();

        $repositoryResolver
            ->expects($this->once())
            ->method('resolve')
            ->with(
                $this->identicalTo('upstream'),
                $this->identicalTo('origin')
            )
            ->willReturn($repository);

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository,
            $repositoryResolver
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ]);
    }

    public function testExecuteDelegatesToPullRequestRepositoryUsingRepositorySpecifiedInOptions()
    {
        $faker = $this->faker();

        $owner = $faker->unique()->slug();
        $name = $faker->unique()->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf(Resource\RepositoryInterface::class),
                    $this->callback(function (Resource\RepositoryInterface $repository) use ($owner, $name) {
                        return $repository->owner() === $owner
                            && $repository->name() === $name;
                    })
                ),
                $this->identicalTo($startReference),
                $this->identicalTo($endReference)
            )
            ->willReturn($this->createRangeMock());

        $repositoryResolver = $this->createRepositoryResolverMock();

        $repositoryResolver
            ->expects($this->never())
            ->method($this->anything());

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
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

    public function testExecuteRendersMessageIfNoPullRequestsWereFound()
    {
        $faker = $this->faker();

        $expectedMessage = 'Could not find any pull requests';

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock());

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository,
            $this->createRepositoryResolverMock()
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

        $this->assertSame(0, $exitCode);
        $this->assertRegExp('@' . $expectedMessage . '@', $tester->getDisplay());
    }

    public function testExecuteRendersDifferentMessageIfNoPullRequestsWereFoundAndNoEndReferenceWasGiven()
    {
        $faker = $this->faker();

        $expectedMessage = 'Could not find any pull requests';

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock());

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository,
            $this->createRepositoryResolverMock()
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

        $this->assertSame(0, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteRendersPullRequestsWithTemplate()
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

        \array_walk($pullRequests, function (Resource\PullRequestInterface $pullRequest) use (&$expectedMessages, $template) {
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

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock($pullRequests));

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository,
            $this->createRepositoryResolverMock()
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

        $this->assertSame(0, $exitCode);

        foreach ($expectedMessages as $expectedMessage) {
            $this->assertContains($expectedMessage, $tester->getDisplay());
        }
    }

    public function testExecuteRendersDifferentMessageWhenNoEndReferenceWasGiven()
    {
        $faker = $this->faker();

        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $expectedMessage = \sprintf(
            'Found %d pull request%s',
            $count,
            1 === $count ? '' : 's'
        );

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock($pullRequests));

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository,
            $this->createRepositoryResolverMock()
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

        $this->assertSame(0, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteHandlesExceptionsThrownWhenFetchingPullRequests()
    {
        $faker = $this->faker();

        $exception = new \Exception('Wait, this should not happen!');

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willThrowException($exception);

        $expectedMessage = \sprintf(
            'An error occurred: %s',
            $exception->getMessage()
        );

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository,
            $this->createRepositoryResolverMock()
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

        $this->assertSame(1, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    /**
     * @return Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createClientMock(): Client
    {
        return $this->createMock(Client::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Repository\PullRequestRepositoryInterface
     */
    private function createPullRequestRepositoryMock(): Repository\PullRequestRepositoryInterface
    {
        return $this->createMock(Repository\PullRequestRepositoryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Util\RepositoryResolverInterface
     */
    private function createRepositoryResolverMock(): Util\RepositoryResolverInterface
    {
        return $this->createMock(Util\RepositoryResolverInterface::class);
    }

    /**
     * @param array $pullRequests
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Resource\RangeInterface
     */
    private function createRangeMock(array $pullRequests = []): Resource\RangeInterface
    {
        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects($this->any())
            ->method('pullRequests')
            ->willReturn($pullRequests);

        return $range;
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
     * @return Resource\PullRequest[]
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
