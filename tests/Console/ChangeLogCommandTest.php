<?php

namespace Localheinz\GitHub\ChangeLog\Test\Console;

use Localheinz\GitHub\ChangeLog\Console;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ChangeLogCommandTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Console\ChangeLogCommand
     */
    private $command;

    protected function setUp()
    {
        $this->command = new Console\ChangeLogCommand();
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

        /* @var InputArgument $argument */
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
                'start',
                true,
                'The start reference, e.g. "1.0.0"',
            ],
            [
                'end',
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

        /* @var InputOption $option */
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
}
