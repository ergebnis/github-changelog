<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Console;

use Github\Client;
use Localheinz\GitHub\ChangeLog\Console;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Refinery29\Test\Util\TestHelper;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateCommandTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testHasName()
    {
        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $this->getPullRequestRepositoryMock()
        );

        $this->assertSame('generate', $command->getName());
    }

    public function testHasDescription()
    {
        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $this->getPullRequestRepositoryMock()
        );

        $this->assertSame('Generates a changelog from information found between commit references', $command->getDescription());
    }

    /**
     * @dataProvider providerArgument
     *
     * @param string $name
     * @param bool   $required
     * @param string $description
     */
    public function testArgument($name, $required, $description)
    {
        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $this->getPullRequestRepositoryMock()
        );

        $this->assertTrue($command->getDefinition()->hasArgument($name));

        /* @var Input\InputArgument $argument */
        $argument = $command->getDefinition()->getArgument($name);

        $this->assertSame($name, $argument->getName());
        $this->assertSame($required, $argument->isRequired());
        $this->assertSame($description, $argument->getDescription());
    }

    /**
     * @return array
     */
    public function providerArgument()
    {
        return [
            [
                'owner',
                true,
                'The owner, e.g., "localheinz"',
            ],
            [
                'repository',
                true,
                'The repository, e.g. "github-changelog"',
            ],
            [
                'start-reference',
                true,
                'The start reference, e.g. "1.0.0"',
            ],
            [
                'end-reference',
                false,
                'The end reference, e.g. "1.1.0"',
            ],
        ];
    }

    /**
     * @dataProvider providerOption
     *
     * @param string $name
     * @param string $shortcut
     * @param bool   $required
     * @param string $description
     * @param mixed  $default
     */
    public function testOption($name, $shortcut, $required, $description, $default)
    {
        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $this->getPullRequestRepositoryMock()
        );

        $this->assertTrue($command->getDefinition()->hasOption($name));

        /* @var Input\InputOption $option */
        $option = $command->getDefinition()->getOption($name);

        $this->assertSame($name, $option->getName());
        $this->assertSame($shortcut, $option->getShortcut());
        $this->assertSame($required, $option->isValueRequired());
        $this->assertSame($description, $option->getDescription());
        $this->assertSame($default, $option->getDefault());
    }

    /**
     * @return array
     */
    public function providerOption()
    {
        return [
            [
                'auth-token',
                'a',
                false,
                'The GitHub token',
                null,
            ],
            [
                'template',
                't',
                false,
                'The template to use for rendering a pull request',
                '- %title% (#%id%)',
            ],
        ];
    }

    public function testExecuteAuthenticatesIfTokenOptionIsGiven()
    {
        $authToken = $this->getFaker()->password();

        $client = $this->getClientMock();

        $client
            ->expects($this->once())
            ->method('authenticate')
            ->with(
                $this->equalTo($authToken),
                $this->equalTo(Client::AUTH_HTTP_TOKEN)
            );

        $pullRequestRepository = $this->getPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->getRangeMock());

        $command = new Console\GenerateCommand(
            $client,
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'owner' => 'localheinz',
            'repository' => 'github-changelog',
            'start-reference' => '0.1.0',
            '--auth-token' => $authToken,
        ]);
    }

    public function testExecuteDelegatesToPullRequestRepository()
    {
        $faker = $this->getFaker();

        $owner = $faker->unique()->userName;
        $repository = $faker->unique()->slug();
        $startReference = $faker->unique()->sha1;
        $endReference = $faker->unique()->sha1;

        $pullRequestRepository = $this->getPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn($this->getRangeMock([]));

        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ]);
    }

    public function testExecuteRendersMessageIfNoPullRequestsWereFound()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $expectedMessage = 'Could not find any pull requests';

        $pullRequestRepository = $this->getPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->getRangeMock());

        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertRegExp('@' . $expectedMessage . '@', $tester->getDisplay());
    }

    public function testExecuteRendersDifferentMessageIfNoPullRequestsWereFoundAndNoEndReferenceWasGiven()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;

        $expectedMessage = 'Could not find any pull requests';

        $pullRequestRepository = $this->getPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->getRangeMock());

        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => null,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteRendersPullRequestsWithTemplate()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;
        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $template = '- %title% (#%id%)';

        $expectedMessages = [
            sprintf(
                'Found %s pull requests',
                count($pullRequests)
            ),
        ];

        array_walk($pullRequests, function (Resource\PullRequestInterface $pullRequest) use (&$expectedMessages, $template) {
            $expectedMessages[] = str_replace(
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
        });

        $pullRequestRepository = $this->getPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->getRangeMock($pullRequests));

        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--template' => $template,
        ]);

        $this->assertSame(0, $exitCode);

        foreach ($expectedMessages as $expectedMessage) {
            $this->assertContains($expectedMessage, $tester->getDisplay());
        }
    }

    public function testExecuteRendersDifferentMessageWhenNoEndReferenceWasGiven()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $expectedMessage = sprintf(
            'Found %s pull requests',
            count($pullRequests)
        );

        $pullRequestRepository = $this->getPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->getRangeMock($pullRequests));

        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => null,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteHandlesExceptionsThrownWhenFetchingPullRequests()
    {
        $faker = $this->getFaker();

        $exception = new \Exception('Wait, this should not happen!');

        $pullRequestRepository = $this->getPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willThrowException($exception);

        $expectedMessage = sprintf(
            'An error occurred: %s',
            $exception->getMessage()
        );

        $command = new Console\GenerateCommand(
            $this->getClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $faker->unique()->userName,
            'repository' => $faker->unique()->slug(),
            'start-reference' => $faker->unique()->sha1,
            'end-reference' => $faker->unique()->sha1,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Client
     */
    private function getClientMock()
    {
        return $this->createMock(Client::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Repository\PullRequestRepository
     */
    private function getPullRequestRepositoryMock()
    {
        return $this->createMock(Repository\PullRequestRepository::class);
    }

    /**
     * @param array $pullRequests
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Resource\RangeInterface
     */
    private function getRangeMock(array $pullRequests = [])
    {
        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects($this->any())
            ->method('pullRequests')
            ->willReturn($pullRequests);

        return $range;
    }

    /**
     * @return Resource\PullRequest
     */
    private function pullRequest()
    {
        $faker = $this->getFaker();

        $id = $faker->unique()->numberBetween(1);
        $title = $faker->unique()->sentence();

        return new Resource\PullRequest(
            $id,
            $title
        );
    }

    /**
     * @param int $count
     *
     * @return Resource\PullRequest[]
     */
    private function pullRequests($count)
    {
        $pullRequests = [];

        for ($i = 0; $i < $count; ++$i) {
            array_push($pullRequests, $this->pullRequest());
        }

        return $pullRequests;
    }
}
