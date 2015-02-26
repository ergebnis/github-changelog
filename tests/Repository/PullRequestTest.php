<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Localheinz\ChangeLog\Repository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    public function testPullRequestsReturnsEmptyArrayWhenStartAndEndAreTheSame()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';

        $end = $start;

        $pullRequestRepository = new Repository\PullRequest($this->commitRepository());

        $pullRequests = $pullRequestRepository->pullRequests(
            $user,
            $repository,
            $start,
            $end
        );

        $this->assertSame([], $pullRequests);
    }

    public function testPullRequestsAttemptsToFindStartAndEndCommit()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';
        $end = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->at(0))
            ->method('commit')
            ->with(
                $this->equalTo($user),
                $this->equalTo($repository),
                $this->equalTo($start)
            )
        ;

        $commitRepository
            ->expects($this->at(1))
            ->method('commit')
            ->with(
                $this->equalTo($user),
                $this->equalTo($repository),
                $this->equalTo($end)
            )
        ;

        $pullRequestRepository = new Repository\PullRequest($commitRepository);

        $pullRequestRepository->pullRequests(
            $user,
            $repository,
            $start,
            $end
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
}
