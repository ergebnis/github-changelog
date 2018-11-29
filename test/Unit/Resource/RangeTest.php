<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 */
final class RangeTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsRangeInterface()
    {
        $this->assertClassImplementsInterface(Resource\RangeInterface::class, Resource\Range::class);
    }

    public function testDefaults()
    {
        $range = new Resource\Range();

        self::assertSame([], $range->commits());
        self::assertSame([], $range->pullRequests());
    }

    public function testWithCommitClonesRangeAndAddsCommit()
    {
        $commit = $this->createCommitMock();

        $range = new Resource\Range();

        $mutated = $range->withCommit($commit);

        self::assertInstanceOf(Resource\Range::class, $mutated);
        self::assertNotSame($range, $mutated);
        self::assertCount(0, $range->commits());
        self::assertCount(1, $mutated->commits());
        self::assertContains($commit, $mutated->commits());
    }

    public function testWithPullRequestClonesRangeAndAddsPullRequest()
    {
        $pullRequest = $this->createPullRequestMock();

        $range = new Resource\Range();

        $mutated = $range->withPullRequest($pullRequest);

        self::assertInstanceOf(Resource\Range::class, $mutated);
        self::assertNotSame($range, $mutated);
        self::assertCount(0, $range->pullRequests());
        self::assertCount(1, $mutated->pullRequests());
        self::assertContains($pullRequest, $mutated->pullRequests());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Resource\CommitInterface
     */
    private function createCommitMock(): Resource\CommitInterface
    {
        return $this->createMock(Resource\CommitInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Resource\PullRequestInterface
     */
    private function createPullRequestMock(): Resource\PullRequestInterface
    {
        return $this->createMock(Resource\PullRequestInterface::class);
    }
}
