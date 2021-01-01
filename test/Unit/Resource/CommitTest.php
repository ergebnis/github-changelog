<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2021 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Test\Unit\Resource;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Resource\Commit
 */
final class CommitTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsAuthorInterface(): void
    {
        self::assertClassImplementsInterface(Resource\CommitInterface::class, Resource\Commit::class);
    }

    /**
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerInvalidSha
     */
    public function testConstructorRejectsInvalidSha(string $sha): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Sha "%s" does not appear to be a valid sha1 hash.',
            $sha
        ));

        $message = self::faker()->sentence();

        new Resource\Commit(
            $sha,
            $message
        );
    }

    public function testConstructorSetsShaAndMessage(): void
    {
        $faker = self::faker();

        $sha = $faker->sha1;
        $message = $faker->sentence();

        $commit = new Resource\Commit(
            $sha,
            $message
        );

        self::assertSame($sha, $commit->sha());
        self::assertSame($message, $commit->message());
    }

    public function testEqualsReturnsFalseIfHashesAreDifferent(): void
    {
        $faker = self::faker();

        $one = new Resource\Commit(
            $faker->unique()->sha1,
            $faker->unique()->sentence()
        );

        $two = new Resource\Commit(
            $faker->unique()->sha1,
            $faker->unique()->sentence()
        );

        self::assertFalse($one->equals($two));
    }

    public function testEqualsReturnsTrueIfHashesAreTheSame(): void
    {
        $faker = self::faker();

        $sha = $faker->sha1;

        $one = new Resource\Commit(
            $sha,
            $faker->unique()->sentence()
        );

        $two = new Resource\Commit(
            $sha,
            $faker->unique()->sentence()
        );

        self::assertTrue($one->equals($two));
    }
}
