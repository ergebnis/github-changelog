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
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
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
            $this->createPullRequestRepositoryMock()
        );

        $this->assertSame('generate', $command->getName());
    }

    public function testHasDescription()
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock()
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
            $this->createPullRequestRepositoryMock()
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
            'repository' => [
                true,
                'The repository, e.g. "localheinz/github-changelog"',
            ],
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
            $this->createPullRequestRepositoryMock()
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
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'repository' => 'localheinz/github-changelog',
            'start-reference' => '0.1.0',
            '--auth-token' => $authToken,
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
            $this->createPullRequestRepositoryMock()
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'repository' => $repository,
            'start-reference' => '0.1.0',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteDelegatesToPullRequestRepository()
    {
        $faker = $this->faker();

        $owner = $faker->slug();
        $name = $faker->slug();
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

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'repository' => $this->repositoryFrom(
                $owner,
                $name
            ),
            'start-reference' => $startReference,
            'end-reference' => $endReference,
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
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
            'start-reference' => $faker->sha1,
            'end-reference' => $faker->sha1,
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
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
            'start-reference' => $faker->sha1,
            'end-reference' => null,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteRendersPullRequestsWithTemplate()
    {
        $faker = $this->faker();

        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $template = '- %title% (#%number%)';

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
                    '%title%',
                    '%number%',
                ],
                [
                    $pullRequest->title(),
                    $pullRequest->number(),
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
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
            'start-reference' => $faker->sha1,
            'end-reference' => $faker->sha1,
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
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
            'start-reference' => $faker->sha1,
            'end-reference' => null,
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
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'repository' => $this->repositoryFrom(
                $faker->slug(),
                $faker->slug()
            ),
            'start-reference' => $faker->sha1,
            'end-reference' => $faker->sha1,
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

        return new Resource\PullRequest(
            $number,
            $title
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
