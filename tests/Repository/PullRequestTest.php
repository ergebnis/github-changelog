<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    public function testPullRequestsReturnsEmptyArrayWhenStartAndEndAreTheSame()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = $startSha;

        $pullRequestRepository = new Repository\PullRequest($this->commitRepository());

        $pullRequests = $pullRequestRepository->pullRequests(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $pullRequests);
    }

    public function testPullRequestsAttemptsToFindStartAndEndCommit()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('commit')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn($this->commit())
        ;

        $commitRepository
            ->expects($this->at(1))
            ->method('commit')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($endSha)
            )
            ->willReturn($this->commit())
        ;

        $pullRequestRepository = new Repository\PullRequest($commitRepository);

        $pullRequestRepository->pullRequests(
            $userName,
            $repository,
            $startSha,
            $endSha
        );
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Could not find start commit
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfStartCommitCouldNotBeDetermined()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('commit')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn(null)
        ;

        $pullRequestRepository = new Repository\PullRequest($commitRepository);

        $pullRequestRepository->pullRequests(
            $userName,
            $repository,
            $startSha,
            $endSha
        );
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Could not find end commit
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfEndCommitCouldNotBeDetermined()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('commit')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha)
            )
            ->willReturn($this->commit())
        ;

        $commitRepository
            ->expects($this->at(1))
            ->method('commit')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($endSha)
            )
            ->willReturn(null)
        ;

        $pullRequestRepository = new Repository\PullRequest($commitRepository);

        $pullRequestRepository->pullRequests(
            $userName,
            $repository,
            $startSha,
            $endSha
        );
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function commitRepository()
    {
        return $this->getMockBuilder(Repository\Commit::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function commit()
    {
        return $this->getMockBuilder(Entity\Commit::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
