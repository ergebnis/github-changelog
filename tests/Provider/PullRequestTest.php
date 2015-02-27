<?php

namespace Localheinz\ChangeLog\Test\Provider;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Provider;
use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Test\Util\DataProviderTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use DataProviderTrait;

    public function testImplementsProvidesItemsInterface()
    {
        $provider = new Provider\PullRequest(
            $this->commitProvider(),
            $this->pullRequestRepository()
        );

        $this->assertInstanceOf(Provider\ItemProvider::class, $provider);
    }

    public function testPullRequestsReturnsEmptyArrayIfNoCommitsWereFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitProvider = $this->commitProvider();

        $commitProvider
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

        $provider = new Provider\PullRequest(
            $commitProvider,
            $this->pullRequestRepository()
        );

        $pullRequests = $provider->items(
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

        $commitProvider = $this->commitProvider();

        $commitProvider
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

        $provider = new Provider\PullRequest(
            $commitProvider,
            $this->pullRequestRepository()
        );

        $pullRequests = $provider->items(
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

        $commitProvider = $this->commitProvider();

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

        $commitProvider
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

        $provider = new Provider\PullRequest(
            $commitProvider,
            $pullRequestRepository
        );

        $pullRequests = $provider->items(
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

        $commitProvider = $this->commitProvider();

        $id = 9000;

        $mergeCommit = $this->commit(
            null,
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $id
            )
        );

        $commitProvider
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

        $provider = new Provider\PullRequest(
            $commitProvider,
            $pullRequestRepository
        );

        $pullRequests = $provider->items(
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
    private function commitProvider()
    {
        return $this->getMockBuilder(Provider\Commit::class)
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
