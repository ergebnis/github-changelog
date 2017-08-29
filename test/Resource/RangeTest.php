<?php

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Refinery29\Test\Util\TestHelper;

final class RangeTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $this->assertFinal(Resource\Range::class);
    }

    public function testImplementsRangeInterface()
    {
        $this->assertImplements(Resource\RangeInterface::class, Resource\Range::class);
    }

    public function testDefaults()
    {
        $range = new Resource\Range();

        $this->assertSame([], $range->commits());
        $this->assertSame([], $range->pullRequests());
    }

    public function testWithCommitClonesRangeAndAddsCommit()
    {
        $commit = $this->getCommitMock();

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
        $pullRequest = $this->getPullRequestMock();

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
    private function getCommitMock()
    {
        return $this->createMock(Resource\CommitInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Resource\PullRequestInterface
     */
    private function getPullRequestMock()
    {
        return $this->createMock(Resource\PullRequestInterface::class);
    }
}
