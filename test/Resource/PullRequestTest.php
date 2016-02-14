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
use Localheinz\GitHub\ChangeLog\Resource\PullRequest;
use Localheinz\GitHub\ChangeLog\Resource\PullRequestInterface;
use PHPUnit_Framework_TestCase;
use Refinery29\Test\Util\Faker\GeneratorTrait;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use GeneratorTrait;

    public function testIsFinal()
    {
        $reflectionClass = new \ReflectionClass(PullRequest::class);

        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testImplementsPullRequestInterface()
    {
        $reflectionClass = new \ReflectionClass(PullRequest::class);

        $this->assertTrue($reflectionClass->implementsInterface(PullRequestInterface::class));
    }

    /**
     * @dataProvider providerInvalidId
     *
     * @param mixed $id
     */
    public function testConstructorRejectsInvalidId($id)
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $title = $this->getFaker()->sentence();

        new Resource\PullRequest(
            $id,
            $title
        );
    }

    /**
     * @return \Generator
     */
    public function providerInvalidId()
    {
        $faker = $this->getFaker();

        $values = [
            new \stdClass(),
            $faker->randomFloat(),
            0,
            -1 * $faker->numberBetween(1),
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
     * @dataProvider providerInvalidTitle
     *
     * @param mixed $message
     */
    public function testConstructorRejectsInvalidTitle($message)
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $sha = sha1($this->getFaker()->sentence());

        new Resource\PullRequest(
            $sha,
            $message
        );
    }

    /**
     * @return \Generator
     */
    public function providerInvalidTitle()
    {
        $faker = $this->getFaker();

        $values = [
            new \stdClass(),
            $faker->randomFloat(),
            $faker->randomNumber(),
            $faker->words,
        ];

        foreach ($values as $value) {
            yield [
                $value,
            ];
        }
    }

    public function testConstructorSetsIdAndTitle()
    {
        $faker = $this->getFaker();

        $id = $faker->numberBetween(1);
        $title = $faker->sentence();

        $pullRequest = new Resource\PullRequest(
            $id,
            $title
        );

        $this->assertSame($id, $pullRequest->id());
        $this->assertSame($title, $pullRequest->title());
    }
}
