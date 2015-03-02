<?php

namespace Localheinz\GitHub\ChangeLog\Test\Console;

use Github\Client;
use Github\HttpClient;
use Localheinz\GitHub\ChangeLog\Console;
use Localheinz\GitHub\ChangeLog\Repository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use ReflectionObject;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

class ChangeLogCommandTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Console\ChangeLogCommand
     */
    private $command;

    protected function setUp()
    {
        $this->command = new Console\ChangeLogCommand();

        $this->command->setClient($this->client());
        $this->command->setPullRequestRepository($this->pullRequestRepository());
    }

    protected function tearDown()
    {
        unset($this->command);
    }

    public function testName()
    {
        $this->assertSame('localheinz:changelog', $this->command->getName());
    }

    public function testDescription()
    {
        $this->assertSame('Creates a changelog based on references', $this->command->getDescription());
    }

    /**
     * @dataProvider providerArgument
     *
     * @param string $name
     * @param bool $required
     * @param string $description
     */
    public function testArgument($name, $required, $description)
    {
        $this->assertTrue($this->command->getDefinition()->hasArgument($name));

        /* @var Input\InputArgument $argument */
        $argument = $this->command->getDefinition()->getArgument($name);

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
                'vendor',
                true,
                'The name of the vendor, e.g., "localheinz"',
            ],
            [
                'package',
                true,
                'The name of the package, e.g. "github-changelog"',
            ],
            [
                'start-reference',
                true,
                'The start reference, e.g. "1.0.0"',
            ],
            [
                'end-reference',
                true,
                'The end reference, e.g. "1.1.0"',
            ],
        ];
    }

    /**
     * @dataProvider providerOption
     *
     * @param string $name
     * @param string $shortcut
     * @param bool $required
     * @param string $description
     * @param mixed $default
     */
    public function testOption($name, $shortcut, $required, $description, $default)
    {
        $this->assertTrue($this->command->getDefinition()->hasOption($name));

        /* @var Input\InputOption $option */
        $option = $this->command->getDefinition()->getOption($name);

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
                'token',
                't',
                false,
                'The GitHub token',
                null,
            ],
        ];
    }

    public function testCanSetClient()
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->command->setClient($client);

        $reflectionObject = new ReflectionObject($this->command);

        $property = $reflectionObject->getProperty('client');
        $property->setAccessible(true);

        $this->assertSame($client, $property->getValue($this->command));
    }

    public function testExecuteLazilyCreatesClientWithCachedHttpClient()
    {
        $this->command = new Console\ChangeLogCommand();

        $this->command->run(
            $this->input(),
            $this->output()
        );

        $reflectionObject = new ReflectionObject($this->command);

        $property = $reflectionObject->getProperty('client');
        $property->setAccessible(true);

        $client = $property->getValue($this->command);

        $this->assertInstanceOf(Client::class, $client);

        /* @var Client $client */
        $this->assertInstanceOf(HttpClient\CachedHttpClient::class, $client->getHttpClient());
    }

    public function testExecuteAuthenticatesIfTokenOptionIsGiven()
    {
        $token = 'foo9000';

        $client = $this->client();

        $client
            ->expects($this->once())
            ->method('authenticate')
            ->with(
                $this->equalTo($token),
                $this->equalTo(Client::AUTH_HTTP_TOKEN)
            )
        ;

        $this->command->setClient($client);

        $this->command->run(
            $this->input(
                [],
                [
                    'token' => $token,
                ]
            ),
            $this->output()
        );
    }

    public function testCanSetPullRequestRepository()
    {
        $pullRequestRepository = $this->pullRequestRepository();

        $this->command->setPullRequestRepository($pullRequestRepository);

        $reflectionObject = new ReflectionObject($this->command);

        $property = $reflectionObject->getProperty('pullRequestRepository');
        $property->setAccessible(true);

        $this->assertSame($pullRequestRepository, $property->getValue($this->command));
    }

    public function testExecuteLazilyCreatesPullRequestRepository()
    {
        $client = $this->client();

        $this->command->setClient($client);

        $this->command->run(
            $this->input(),
            $this->output()
        );

        $reflectionObject = new ReflectionObject($this->command);

        $property = $reflectionObject->getProperty('pullRequestRepository');
        $property->setAccessible(true);

        $pullRequestRepository = $property->getValue($this->command);

        $this->assertInstanceOf(Repository\PullRequest::class, $pullRequestRepository);
    }

    public function testExecuteDelegatesToPullRequestRepository()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $pullRequestRepository = $this->pullRequestRepository();

        $pullRequestRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
        ;

        $this->command->setPullRequestRepository($pullRequestRepository);

        $this->command->run(
            $this->input([
                'vendor' => $vendor,
                'package' => $package,
                'start-reference' => $startReference,
                'end-reference' => $endReference,
            ]),
            $this->output()
        );
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Client
     */
    private function client()
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Repository\PullRequest
     */
    private function pullRequestRepository()
    {
        return $this->getMockBuilder(Repository\PullRequest::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @param array $arguments
     * @param array $options
     * @return Input\InputInterface
     */
    private function input(array $arguments = [], array $options = [])
    {
        $input = $this->getMockBuilder(Input\InputInterface::class)->getMock();

        $input
            ->expects($this->any())
            ->method('getArgument')
            ->willReturnCallback(function ($name) use ($arguments) {
                if (!array_key_exists($name, $arguments)) {
                    return null;
                }

                return $arguments[$name];
            })
        ;

        $input
            ->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function ($name) use ($options) {
                if (!array_key_exists($name, $options)) {
                    return null;
                }

                return $options[$name];
            })
        ;

        return $input;
    }

    /**
     * @return Output\OutputInterface
     */
    private function output()
    {
        return $this->getMockBuilder(Output\OutputInterface::class)->getMock();
    }
}
