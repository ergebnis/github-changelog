<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Console;

use Exception;
use Github\Client;
use Github\HttpClient;
use Localheinz\GitHub\ChangeLog\Console;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Refinery29\Test\Util\Faker\GeneratorTrait;
use ReflectionObject;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

class PullRequestCommandTest extends PHPUnit_Framework_TestCase
{
    use GeneratorTrait;

    public function testHasName()
    {
        $command = new Console\PullRequestCommand();

        $command->setClient($this->clientMock());
        $command->setPullRequestRepository($this->pullRequestRepositoryMock());

        $this->assertSame('pull-request', $command->getName());
    }

    public function testHasDescription()
    {
        $command = new Console\PullRequestCommand();

        $command->setClient($this->clientMock());
        $command->setPullRequestRepository($this->pullRequestRepositoryMock());

        $this->assertSame('Creates a changelog from pull requests merged between references', $command->getDescription());
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
        $command = new Console\PullRequestCommand();

        $command->setClient($this->clientMock());
        $command->setPullRequestRepository($this->pullRequestRepositoryMock());

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
        $command = new Console\PullRequestCommand();

        $command->setClient($this->clientMock());
        $command->setPullRequestRepository($this->pullRequestRepositoryMock());

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

    public function testCanSetClient()
    {
        /* @var Client $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $command = new Console\PullRequestCommand();

        $command->setClient($client);

        $reflectionObject = new ReflectionObject($command);

        $property = $reflectionObject->getProperty('client');
        $property->setAccessible(true);

        $this->assertSame($client, $property->getValue($command));
    }

    public function testExecuteLazilyCreatesClientWithCachedHttpClient()
    {
        $command = new Console\PullRequestCommand();

        $command->run(
            $this->inputMock(),
            $this->outputMock()
        );

        $reflectionObject = new ReflectionObject($command);

        $property = $reflectionObject->getProperty('client');
        $property->setAccessible(true);

        $client = $property->getValue($command);

        $this->assertInstanceOf(Client::class, $client);

        /* @var Client $client */
        $this->assertInstanceOf(HttpClient\CachedHttpClient::class, $client->getHttpClient());
    }

    public function testExecuteAuthenticatesIfTokenOptionIsGiven()
    {
        $authToken = $this->getFaker()->password();

        $client = $this->clientMock();

        $client
            ->expects($this->once())
            ->method('authenticate')
            ->with(
                $this->equalTo($authToken),
                $this->equalTo(Client::AUTH_HTTP_TOKEN)
            )
        ;
        $command = new Console\PullRequestCommand();

        $command->setClient($client);

        $command->run(
            $this->inputMock(
                [],
                [
                    'auth-token' => $authToken,
                ]
            ),
            $this->outputMock()
        );
    }

    public function testCanSetPullRequestRepository()
    {
        $pullRequestRepository = $this->pullRequestRepositoryMock();

        $command = new Console\PullRequestCommand();

        $command->setPullRequestRepository($pullRequestRepository);

        $reflectionObject = new ReflectionObject($command);

        $property = $reflectionObject->getProperty('pullRequestRepository');
        $property->setAccessible(true);

        $this->assertSame($pullRequestRepository, $property->getValue($command));
    }

    public function testExecuteLazilyCreatesPullRequestRepository()
    {
        $command = new Console\PullRequestCommand();

        $command->setClient($this->clientMock());

        $command->run(
            $this->inputMock(),
            $this->outputMock()
        );

        $reflectionObject = new ReflectionObject($command);

        $property = $reflectionObject->getProperty('pullRequestRepository');
        $property->setAccessible(true);

        $pullRequestRepository = $property->getValue($command);

        $this->assertInstanceOf(Repository\PullRequestRepository::class, $pullRequestRepository);
    }

    public function testExecuteDelegatesToPullRequestRepository()
    {
        $faker = $this->getFaker();

        $owner = $faker->unique()->userName;
        $repository = $faker->unique()->slug();
        $startReference = $faker->unique()->sha1;
        $endReference = $faker->unique()->sha1;

        $pullRequestRepository = $this->pullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn([])
        ;

        $command = new Console\PullRequestCommand();

        $command->setClient($this->clientMock());
        $command->setPullRequestRepository($pullRequestRepository);

        $command->run(
            $this->inputMock([
                'owner' => $owner,
                'repository' => $repository,
                'start-reference' => $startReference,
                'end-reference' => $endReference,
            ]),
            $this->outputMock()
        );
    }

    public function testExecuteRendersMessageIfNoPullRequestsWereFound()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $expectedMessages = [
            sprintf(
                'Could not find any pull requests merged for <info>%s/%s</info> between <info>%s</info> and <info>%s</info>.',
                $owner,
                $repository,
                $startReference,
                $endReference
            ),
        ];

        $pullRequestRepository = $this->pullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn([])
        ;

        $command = new Console\PullRequestCommand();

        $command->setPullRequestRepository($pullRequestRepository);

        $arguments = [
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ];

        $exitCode = $command->run(
            $this->inputMock($arguments),
            $this->outputSpy($expectedMessages)
        );

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteRendersDifferentMessageIfNoPullRequestsWereFoundAndNoEndReferenceWasGiven()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;

        $expectedMessages = [
            sprintf(
                'Could not find any pull requests merged for <info>%s/%s</info> since <info>%s</info>.',
                $owner,
                $repository,
                $startReference
            ),
        ];

        $pullRequestRepository = $this->pullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn([])
        ;

        $command = new Console\PullRequestCommand();

        $command->setPullRequestRepository($pullRequestRepository);

        $arguments = [
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => null,
        ];

        $exitCode = $command->run(
            $this->inputMock($arguments),
            $this->outputSpy($expectedMessages)
        );

        $this->assertSame(0, $exitCode);
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
                'Found <info>%s</info> pull request(s) merged for <info>%s/%s</info> between <info>%s</info> and <info>%s</info>.',
                count($pullRequests),
                $owner,
                $repository,
                $startReference,
                $endReference
            ),
            '',
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

        $pullRequestRepository = $this->pullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($pullRequests)
        ;

        $command = new Console\PullRequestCommand();

        $command->setPullRequestRepository($pullRequestRepository);

        $arguments = [
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ];

        $options = [
            'template' => $template,
        ];

        $exitCode = $command->run(
            $this->inputMock(
                $arguments,
                $options
            ),
            $this->outputSpy($expectedMessages)
        );

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteRendersDifferentMessageWhenNoEndReferenceWasGiven()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $expectedMessages = [
            sprintf(
                'Found <info>%s</info> pull request(s) merged for <info>%s/%s</info> since <info>%s</info>.',
                count($pullRequests),
                $owner,
                $repository,
                $startReference
            ),
            '',
        ];

        $pullRequestRepository = $this->pullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($pullRequests)
        ;

        $command = new Console\PullRequestCommand();

        $command->setPullRequestRepository($pullRequestRepository);

        $arguments = [
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => null,
        ];

        $exitCode = $command->run(
            $this->inputMock($arguments),
            $this->outputSpy($expectedMessages)
        );

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteHandlesExceptionsThrownWhenFetchingPullRequests()
    {
        $exception = new Exception('Wait, this should not happen!');
        $pullRequestRepository = $this->pullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willThrowException($exception)
        ;

        $command = new Console\PullRequestCommand();

        $command->setPullRequestRepository($pullRequestRepository);

        $faker = $this->getFaker();

        $arguments = [
            'owner' => $faker->unique()->userName,
            'repository' => $faker->unique()->slug(),
            'start-reference' => $faker->unique()->sha1,
            'end-reference' => $faker->unique()->sha1,
        ];

        $expectedMessages = [
            sprintf(
                '<error>An error occurred: %s</error>',
                $exception->getMessage()
            ),
        ];

        $exitCode = $command->run(
            $this->inputMock(
                $arguments,
                []
            ),
            $this->outputSpy($expectedMessages)
        );

        $this->assertSame(1, $exitCode);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Client
     */
    private function clientMock()
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Repository\PullRequestRepository
     */
    private function pullRequestRepositoryMock()
    {
        return $this->getMockBuilder(Repository\PullRequestRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @param array $arguments
     * @param array $options
     *
     * @return Input\InputInterface
     */
    private function inputMock(array $arguments = [], array $options = [])
    {
        $input = $this->getMockBuilder(Input\InputInterface::class)->getMock();

        $input
            ->expects($this->any())
            ->method('getArgument')
            ->willReturnCallback(function ($name) use ($arguments) {
                if (!array_key_exists($name, $arguments)) {
                    return;
                }

                return $arguments[$name];
            })
        ;

        $input
            ->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function ($name) use ($options) {
                if (!array_key_exists($name, $options)) {
                    return;
                }

                return $options[$name];
            })
        ;

        return $input;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Output\OutputInterface
     */
    private function outputMock()
    {
        return $this->getMockBuilder(Output\OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @param array $expectedMessages
     *
     * @return PHPUnit_Framework_MockObject_MockObject|Output\OutputInterface
     */
    private function outputSpy(array $expectedMessages = [])
    {
        $output = $this->outputMock();

        foreach (array_values($expectedMessages) as $i => $expectedMessage) {
            $output
                ->expects($this->at($i))
                ->method('writeln')
                ->with($expectedMessage)
            ;
        }

        return $output;
    }

    /**
     * @return Resource\PullRequest
     */
    private function pullRequest()
    {
        $faker = $this->getFaker();

        $id = $faker->unique()->randomNumber();
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
