<?php

namespace Localheinz\ChangeLog\Test\Service;

use Localheinz\ChangeLog;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use FakerTrait;

    public function testFluentInterface()
    {
        $builder = new ChangeLog\Service\PullRequest(
            $this->commitService(),
            $this->pullRequestRepository()
        );

        $this->assertSame($builder, $builder->userName('foo'));
        $this->assertSame($builder, $builder->repository('bar'));
        $this->assertSame($builder, $builder->startSha('ad77125'));
        $this->assertSame($builder, $builder->endSha('7fc1c4f'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage User needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfUserHasNotBeenSet()
    {
        $builder = new ChangeLog\Service\PullRequest(
            $this->commitService(),
            $this->pullRequestRepository()
        );

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Repository needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfRepositoryHasNotBeenSet()
    {
        $builder = new ChangeLog\Service\PullRequest(
            $this->commitService(),
            $this->pullRequestRepository()
        );

        $builder->userName('foo');

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Start reference needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfStartReferenceHasNotBeenSet()
    {
        $builder = new ChangeLog\Service\PullRequest(
            $this->commitService(),
            $this->pullRequestRepository()
        );

        $builder
            ->userName('foo')
            ->repository('bar')
        ;

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage End reference needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfEndReferenceHasNotBeenSet()
    {
        $builder = new ChangeLog\Service\PullRequest(
            $this->commitService(),
            $this->pullRequestRepository()
        );

        $builder
            ->userName('foo')
            ->repository('bar')
            ->startSha('ad77125')
        ;

        $builder->pullRequests();
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
            ->method('range')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha),
                $this->equalTo($endSha)
            )
            ->willReturn([])
        ;

        $builder = new ChangeLog\Service\PullRequest(
            $commitService,
            $this->pullRequestRepository()
        );

        $builder
            ->userName($userName)
            ->repository($repository)
            ->startSha($startSha)
            ->endSha($endSha)
        ;

        $this->assertSame([], $builder->pullRequests());
    }

    public function testPullRequestsReturnsEmptyArrayIfNoMergeCommitsWereFound()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitService = $this->commitService();

        $commits = [];

        $this->addCommits($commits, 20);

        $commitService
            ->expects($this->once())
            ->method('range')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($startSha),
                $this->equalTo($endSha)
            )
            ->willReturn($commits)
        ;

        $builder = new ChangeLog\Service\PullRequest(
            $commitService,
            $this->pullRequestRepository()
        );

        $builder
            ->userName($userName)
            ->repository($repository)
            ->startSha($startSha)
            ->endSha($endSha)
        ;

        $this->assertSame([], $builder->pullRequests());
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
            ->method('range')
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

        $builder = new ChangeLog\Service\PullRequest(
            $commitService,
            $pullRequestRepository
        );

        $builder
            ->userName($userName)
            ->repository($repository)
            ->startSha($startSha)
            ->endSha($endSha)
        ;

        $this->assertSame([$pullRequest], $builder->pullRequests());
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
            ->method('range')
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

        $builder = new ChangeLog\Service\PullRequest(
            $commitService,
            $pullRequestRepository
        );

        $builder
            ->userName($userName)
            ->repository($repository)
            ->startSha($startSha)
            ->endSha($endSha)
        ;

        $this->assertSame([], $builder->pullRequests());
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function commitService()
    {
        return $this->getMockBuilder(ChangeLog\Service\Commit::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function pullRequestRepository()
    {
        return $this->getMockBuilder(ChangeLog\Repository\PullRequest::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @param string $sha
     * @param string $message
     * @return Entity\Commit
     */
    private function commit($sha = null, $message = null)
    {
        $sha = $sha ?: $this->faker()->unique()->sha1;
        $message = $message ?: $this->faker()->unique()->sentence();

        return new Entity\Commit(
            $sha,
            $message
        );
    }

    /**
     * @param Entity\Commit[] $commits
     * @param int $count
     */
    private function addCommits(&$commits, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            array_push($commits, $this->commit());
        }
    }
}
