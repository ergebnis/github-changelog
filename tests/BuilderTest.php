<?php

namespace Localheinz\ChangeLog\Test\GitHub;

use Localheinz\ChangeLog;
use PHPUnit_Framework_TestCase;

class BuilderTest extends PHPUnit_Framework_TestCase
{
    public function testFluentInterface()
    {
        $pullRequestRepository = $this->getMockBuilder(ChangeLog\Repository\PullRequest::class)->getMock();

        $builder = new ChangeLog\Builder($pullRequestRepository);

        $this->assertSame($builder, $builder->user('foo'));
        $this->assertSame($builder, $builder->repository('bar'));
        $this->assertSame($builder, $builder->start('ad77125'));
        $this->assertSame($builder, $builder->end('7fc1c4f'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage User needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfUserHasNotBeenSet()
    {
        $pullRequestRepository = $this->getMockBuilder(ChangeLog\Repository\PullRequest::class)->getMock();

        $builder = new ChangeLog\Builder($pullRequestRepository);

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Repository needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfRepositoryHasNotBeenSet()
    {
        $pullRequestRepository = $this->getMockBuilder(ChangeLog\Repository\PullRequest::class)->getMock();

        $builder = new ChangeLog\Builder($pullRequestRepository);

        $builder->user('foo');

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Start reference needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfStartReferenceHasNotBeenSet()
    {
        $pullRequestRepository = $this->getMockBuilder(ChangeLog\Repository\PullRequest::class)->getMock();

        $builder = new ChangeLog\Builder($pullRequestRepository);

        $builder
            ->user('foo')
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
        $pullRequestRepository = $this->getMockBuilder(ChangeLog\Repository\PullRequest::class)->getMock();

        $builder = new ChangeLog\Builder($pullRequestRepository);

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
        ;

        $builder->pullRequests();
    }

    public function testPullRequestsDelegatesToPullRequestRepository()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';
        $end = '7fc1c4f';

        $pullRequests = 'baz';

        $pullRequestRepository = $this->getMockBuilder(ChangeLog\Repository\PullRequest::class)->getMock();

        $pullRequestRepository
            ->expects($this->once())
            ->method('pullRequests')
            ->with(
                $this->equalTo($user),
                $this->equalTo($repository),
                $this->equalTo($start),
                $this->equalTo($end)
            )
            ->willReturn($pullRequests)
        ;

        $builder = new ChangeLog\Builder($pullRequestRepository);

        $builder
            ->user($user)
            ->repository($repository)
            ->start($start)
            ->end($end)
        ;

        $this->assertSame($pullRequests, $builder->pullRequests());
    }
}
