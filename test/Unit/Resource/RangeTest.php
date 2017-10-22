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

final class RangeTest extends Framework\TestCase
{
    use Helper;

    public function testIsFinal()
    {
        $this->assertClassIsFinal(Resource\Range::class);
    }

    public function testImplementsRangeInterface()
    {
        $this->assertClassImplementsInterface(Resource\RangeInterface::class, Resource\Range::class);
    }

    public function testDefaults()
    {
        $range = new Resource\Range();

        $this->assertSame([], $range->commits());
        $this->assertSame([], $range->pullRequests());
    }

    public function testWithCommitClonesRangeAndAddsCommit()
    {
        $commit = $this->createCommitMock();

        $range = new Resource\Range();

        $mutated = $range->withCommit($commit);

        $this->assertInstanceOf(Resource\Range::class, $mutated);
        $this->assertNotSame($range, $mutated);
        $this->assertCount(0, $range->commits());
        $this->assertCount(1, $mutated->commits());
        $this->assertContains($commit, $mutated->commits());
    }

    public function testWithPullRequestClonesRangeAndAddsPullRequest()
    {
        $pullRequest = $this->createPullRequestMock();

        $range = new Resource\Range();

        $mutated = $range->withPullRequest($pullRequest);

        $this->assertInstanceOf(Resource\Range::class, $mutated);
        $this->assertNotSame($range, $mutated);
        $this->assertCount(0, $range->pullRequests());
        $this->assertCount(1, $mutated->pullRequests());
        $this->assertContains($pullRequest, $mutated->pullRequests());
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
