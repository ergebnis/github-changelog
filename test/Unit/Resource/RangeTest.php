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

use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;
use Prophecy\PhpUnit;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Resource\Range
 */
final class RangeTest extends Framework\TestCase
{
    use Helper;
    use PhpUnit\ProphecyTrait;

    public function testImplementsRangeInterface(): void
    {
        self::assertClassImplementsInterface(Resource\RangeInterface::class, Resource\Range::class);
    }

    public function testDefaults(): void
    {
        $range = new Resource\Range();

        self::assertSame([], $range->commits());
        self::assertSame([], $range->pullRequests());
    }

    public function testWithCommitClonesRangeAndAddsCommit(): void
    {
        $commit = $this->prophesize(Resource\CommitInterface::class);

        $range = new Resource\Range();

        $mutated = $range->withCommit($commit->reveal());

        self::assertNotSame($range, $mutated);
        self::assertCount(0, $range->commits());
        self::assertCount(1, $mutated->commits());
        self::assertContains($commit->reveal(), $mutated->commits());
    }

    public function testWithPullRequestClonesRangeAndAddsPullRequest(): void
    {
        $pullRequest = $this->prophesize(Resource\PullRequestInterface::class);

        $range = new Resource\Range();

        $mutated = $range->withPullRequest($pullRequest->reveal());

        self::assertNotSame($range, $mutated);
        self::assertCount(0, $range->pullRequests());
        self::assertCount(1, $mutated->pullRequests());
        self::assertContains($pullRequest->reveal(), $mutated->pullRequests());
    }
}
