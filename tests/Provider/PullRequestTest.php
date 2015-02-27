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

    public function testItemsReturnsEmptyArrayIfNoCommitsWereFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitProvider = $this->commitProvider();

        $commitProvider
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn([])
        ;

        $provider = new Provider\PullRequest(
            $commitProvider,
            $this->pullRequestRepository()
        );

        $pullRequests = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $pullRequests);
    }

    public function testItemsReturnsEmptyArrayIfNoMergeCommitsWereFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitProvider = $this->commitProvider();

        $commitProvider
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn($this->commits(20))
        ;

        $provider = new Provider\PullRequest(
            $commitProvider,
            $this->pullRequestRepository()
        );

        $pullRequests = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $pullRequests);
    }

    public function testItemsFetchesPullRequestIfMergeCommitWasFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

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
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
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
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($pullRequest->id())
            )
            ->willReturn($pullRequest)
        ;

        $provider = new Provider\PullRequest(
            $commitProvider,
            $pullRequestRepository
        );

        $pullRequests = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([$pullRequest], $pullRequests);
    }

    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

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
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
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
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($id)
            )
            ->willReturn(null)
        ;

        $provider = new Provider\PullRequest(
            $commitProvider,
            $pullRequestRepository
        );

        $pullRequests = $provider->items(
            $vendor,
            $package,
            $startReference,
            $endReference
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
