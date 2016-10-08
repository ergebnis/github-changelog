<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Resource;

use Localheinz\GitHub\ChangeLog\Resource\CommitInterface;
use Localheinz\GitHub\ChangeLog\Resource\PullRequestInterface;
use Localheinz\GitHub\ChangeLog\Resource\Range;
use Localheinz\GitHub\ChangeLog\Resource\RangeInterface;
use PHPUnit_Framework_TestCase;
use Refinery29\Test\Util\TestHelper;

class RangeTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $reflectionClass = new \ReflectionClass(Range::class);

        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testImplementsRangeInterface()
    {
        $reflectionClass = new \ReflectionClass(Range::class);

        $this->assertTrue($reflectionClass->implementsInterface(RangeInterface::class));
    }

    public function testDefaults()
    {
        $range = new Range();

        $this->assertSame([], $range->commits());
        $this->assertSame([], $range->pullRequests());
    }

    public function testWithCommitClonesRangeAndAddsCommit()
    {
        $commit = $this->getCommitMock();

        $range = new Range();

        $mutated = $range->withCommit($commit);

        $this->assertInstanceOf(Range::class, $mutated);
        $this->assertNotSame($range, $mutated);
        $this->assertCount(0, $range->commits());
        $this->assertCount(1, $mutated->commits());
        $this->assertContains($commit, $mutated->commits());
    }

    public function testWithPullRequestClonesRangeAndAddsPullRequest()
    {
        $pullRequest = $this->getPullRequestMock();

        $range = new Range();

        $mutated = $range->withPullRequest($pullRequest);

        $this->assertInstanceOf(Range::class, $mutated);
        $this->assertNotSame($range, $mutated);
        $this->assertCount(0, $range->pullRequests());
        $this->assertCount(1, $mutated->pullRequests());
        $this->assertContains($pullRequest, $mutated->pullRequests());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CommitInterface
     */
    private function getCommitMock()
    {
        return $this->createMock(CommitInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PullRequestInterface
     */
    private function getPullRequestMock()
    {
        return $this->createMock(PullRequestInterface::class);
    }
}
