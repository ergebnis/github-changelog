<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
 * @covers \Ergebnis\GitHub\Changelog\Resource\PullRequest
 */
final class PullRequestTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPullRequestInterface(): void
    {
        self::assertClassImplementsInterface(Resource\PullRequestInterface::class, Resource\PullRequest::class);
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\User
     *
     * @dataProvider \Ergebnis\GitHub\Changelog\Test\Util\DataProvider::providerInvalidPullRequestNumber
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\User
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
