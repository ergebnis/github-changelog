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

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Refinery29\Test\Util\DataProvider;
use Refinery29\Test\Util\TestHelper;

final class CommitTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $this->assertFinal(Resource\Commit::class);
    }

    public function testImplementsAuthorInterface()
    {
        $this->assertImplements(Resource\CommitInterface::class, Resource\Commit::class);
    }

    /**
     * @dataProvider providerInvalidSha
     *
     * @param mixed $sha
     */
    public function testConstructorRejectsInvalidSha($sha)
    {
        $this->expectException(\InvalidArgumentException::class);

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

        return $this->provideDataFrom(
            new DataProvider\InvalidString(),
            new DataProvider\Elements([
                $faker->word,
                $faker->words,
                $faker->md5,
            ])
        );
    }

    /**
     * @dataProvider \Refinery29\Test\Util\DataProvider\InvalidString::data()
     *
     * @param mixed $message
     */
    public function testConstructorRejectsInvalidMessage($message)
    {
        $this->expectException(\InvalidArgumentException::class);

        $sha = $this->getFaker()->sha1;

        new Resource\Commit(
            $sha,
            $message
        );
    }

    public function testConstructorSetsShaAndMessage()
    {
        $faker = $this->getFaker();

        $sha = $faker->sha1;
        $message = $faker->sentence();

        $commit = new Resource\Commit(
            $sha,
            $message
        );

        $this->assertSame($sha, $commit->sha());
        $this->assertSame($message, $commit->message());
    }

    public function testEqualsReturnsTrueIfShasAreTheSame()
    {
        $faker = $this->getFaker();

        $sha = $faker->sha1;

        $commitOne = new Resource\Commit(
            $sha,
            $faker->unique()->sentence()
        );

        $commitTwo = new Resource\Commit(
            $sha,
            $faker->unique()->sentence()
        );

        $this->assertTrue($commitTwo->equals($commitOne));
    }
}
