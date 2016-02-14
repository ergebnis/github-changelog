<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Resource;

use InvalidArgumentException;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\GitHub\ChangeLog\Resource\Commit;
use Localheinz\GitHub\ChangeLog\Resource\CommitInterface;
use PHPUnit_Framework_TestCase;
use Refinery29\Test\Util\Faker\GeneratorTrait;

class CommitTest extends PHPUnit_Framework_TestCase
{
    use GeneratorTrait;

    public function testIsFinal()
    {
        $reflectionClass = new \ReflectionClass(Commit::class);

        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testImplementsCommitInterface()
    {
        $reflectionClass = new \ReflectionClass(Commit::class);

        $this->assertTrue($reflectionClass->implementsInterface(CommitInterface::class));
    }

    /**
     * @dataProvider providerInvalidSha
     *
     * @param mixed $sha
     */
    public function testConstructorRejectsInvalidSha($sha)
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $message = $this->getFaker()->sentence();

        new Resource\Commit(
            $sha,
            $message
        );
    }

    /**
     * @return \Generator
     */
    public function providerInvalidSha()
    {
        $faker = $this->getFaker();

        $values = [
            new \stdClass(),
            $faker->randomNumber(),
            $faker->randomFloat(),
            $faker->word,
            $faker->words,
            $faker->md5,
        ];

        foreach ($values as $value) {
            yield [
                $value,
            ];
        }
    }

    /**
     * @dataProvider providerInvalidMessage
     *
     * @param mixed $message
     */
    public function testConstructorRejectsInvalidMessage($message)
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $sha = $this->getFaker()->sha1;

        new Resource\Commit(
            $sha,
            $message
        );
    }

    /**
     * @return \Generator
     */
    public function providerInvalidMessage()
    {
        $faker = $this->getFaker();

        $values = [
            new \stdClass(),
            $faker->words,
        ];

        foreach ($values as $value) {
            yield [
                $value,
            ];
        }
    }

    public function testConstructorSetsShaAndMessage()
    {
        $faker = $this->getFaker();

        $sha = $faker->sha1;
        $message = $faker->sentence();

        $entity = new Resource\Commit(
            $sha,
            $message
        );

        $this->assertSame($sha, $entity->sha());
        $this->assertSame($message, $entity->message());
    }
}
