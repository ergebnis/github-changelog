<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Resource;

use Ergebnis\Test\Util\Helper;
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Localheinz\GitHub\ChangeLog\Resource\PullRequest
 */
final class PullRequestTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPullRequestInterface(): void
    {
        self::assertClassImplementsInterface(Resource\PullRequestInterface::class, Resource\PullRequest::class);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\User
     *
     * @dataProvider \Localheinz\GitHub\ChangeLog\Test\Util\DataProvider::providerInvalidPullRequestNumber
     *
     * @param int $number
     */
    public function testConstructorRejectsInvalidNumber(int $number): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Number "%d" does not appear to be a valid pull request number.',
            $number
        ));

        $faker = self::faker();

        $title = $faker->sentence();
        $author = new Resource\User($faker->slug());

        new Resource\PullRequest(
            $number,
            $title,
            $author
        );
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\User
     */
    public function testConstructorSetsValues(): void
    {
        $faker = self::faker();

        $number = $faker->numberBetween(1);
        $title = $faker->sentence();
        $author = new Resource\User($faker->slug());

        $pullRequest = new Resource\PullRequest(
            $number,
            $title,
            $author
        );

        self::assertSame($number, $pullRequest->number());
        self::assertSame($title, $pullRequest->title());
        self::assertSame($author, $pullRequest->author());
    }
}
