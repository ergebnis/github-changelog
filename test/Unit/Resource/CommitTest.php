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
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class CommitTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsAuthorInterface()
    {
        $this->assertClassImplementsInterface(Resource\CommitInterface::class, Resource\Commit::class);
    }

    /**
     * @dataProvider providerInvalidSha
     *
     * @param mixed $sha
     */
    public function testConstructorRejectsInvalidSha($sha)
    {
        $this->expectException(\InvalidArgumentException::class);

        $message = $this->faker()->sentence();

        new Resource\Commit(
            $sha,
            $message
        );
    }

    public function providerInvalidSha(): \Generator
    {
        $faker = $this->faker();

        $values = [
            'md5' => $faker->md5,
            'sentence' => $faker->sentence(),
            'sha256' => $faker->sha256,
            'word' => $faker->word,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function testConstructorSetsShaAndMessage()
    {
        $faker = $this->faker();

        $sha = $faker->sha1;
        $message = $faker->sentence();

        $commit = new Resource\Commit(
            $sha,
            $message
        );

        $this->assertSame($sha, $commit->sha());
        $this->assertSame($message, $commit->message());
    }

    public function testEqualsReturnsFalseIfHashesAreDifferent()
    {
        $faker = $this->faker();

        $one = new Resource\Commit(
            $faker->unique()->sha1,
            $faker->unique()->sentence()
        );

        $two = new Resource\Commit(
            $faker->unique()->sha1,
            $faker->unique()->sentence()
        );

        $this->assertFalse($one->equals($two));
    }

    public function testEqualsReturnsTrueIfHashesAreTheSame()
    {
        $faker = $this->faker();

        $sha = $faker->sha1;

        $one = new Resource\Commit(
            $sha,
            $faker->unique()->sentence()
        );

        $two = new Resource\Commit(
            $sha,
            $faker->unique()->sentence()
        );

        $this->assertTrue($one->equals($two));
    }
}
