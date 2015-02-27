<?php

namespace Localheinz\ChangeLog\Test\Service;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Service;
use Localheinz\ChangeLog\Test\Util\DataProviderTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use DataProviderTrait;

    public function testImplementsProvidesItemsInterface()
    {
        $service = new Service\PullRequest(
            $this->commitService(),
            $this->pullRequestRepository()
        );

        $this->assertInstanceOf(Service\ProvidesItems::class, $service);
    }

    public function testPullRequestsReturnsEmptyArrayIfNoCommitsWereFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitService = $this->commitService();

        $commitService
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha),
                $this->equalTo($endSha)
            )
            ->willReturn([])
        ;

        $pullRequestService = new Service\PullRequest(
            $commitService,
            $this->pullRequestRepository()
        );

        $pullRequests = $pullRequestService->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $pullRequests);
    }

    public function testPullRequestsReturnsEmptyArrayIfNoMergeCommitsWereFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitService = $this->commitService();

        $commitService
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha),
                $this->equalTo($endSha)
            )
            ->willReturn($this->commits(20))
        ;

        $pullRequestService = new Service\PullRequest(
            $commitService,
            $this->pullRequestRepository()
        );

        $pullRequests = $pullRequestService->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $pullRequests);
    }

    public function testPullRequestsFetchesPullRequestIfMergeCommitWasFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitService = $this->commitService();

        $pullRequest = new Entity\PullRequest(
            9000,
            'Fix: Directory name'
        );

        $mergeCommit = $this->commit(
            null,
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $pullRequest->id()
            )
        );

        $commitService
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha),
                $this->equalTo($endSha)
            )
            ->willReturn([
                $mergeCommit,
            ])
        ;

        $pullRequestRepository = $this->pullRequestRepository();

        $pullRequestRepository
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($pullRequest->id())
            )
            ->willReturn($pullRequest)
        ;

        $pullRequestService = new Service\PullRequest(
            $commitService,
            $pullRequestRepository
        );

        $pullRequests = $pullRequestService->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([$pullRequest], $pullRequests);
    }

    public function testPullRequestsHandlesMergeCommitWherePullRequestWasNotFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitService = $this->commitService();

        $id = 9000;

        $mergeCommit = $this->commit(
            null,
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $id
            )
        );

        $commitService
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha),
                $this->equalTo($endSha)
            )
            ->willReturn([
                $mergeCommit,
            ])
        ;

        $pullRequestRepository = $this->pullRequestRepository();

        $pullRequestRepository
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($id)
            )
            ->willReturn(null)
        ;

        $pullRequestService = new Service\PullRequest(
            $commitService,
            $pullRequestRepository
        );

        $pullRequests = $pullRequestService->items(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $pullRequests);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function commitService()
    {
        return $this->getMockBuilder(Service\Commit::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function pullRequestRepository()
    {
        return $this->getMockBuilder(Repository\PullRequest::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
