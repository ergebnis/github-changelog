<?php

namespace Localheinz\ChangeLog\Test\GitHub;

use Localheinz\ChangeLog;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class BuilderTest extends PHPUnit_Framework_TestCase
{
    public function testFluentInterface()
    {
        $builder = new ChangeLog\Builder($this->pullRequestRepository());

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
        $builder = new ChangeLog\Builder($this->pullRequestRepository());

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Repository needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfRepositoryHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder($this->pullRequestRepository());

        $builder->userName('foo');

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Start reference needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfStartReferenceHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder($this->pullRequestRepository());

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
        $builder = new ChangeLog\Builder($this->pullRequestRepository());

        $builder
            ->userName('foo')
            ->repository('bar')
            ->startSha('ad77125')
        ;

        $builder->pullRequests();
    }

    public function testPullRequestsDelegatesToPullRequestRepository()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';
        $end = '7fc1c4f';

        $pullRequestRepository = $this->pullRequestRepository();

        $pullRequests = 'baz';

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
            ->userName($user)
            ->repository($repository)
            ->startSha($start)
            ->endSha($end)
        ;

        $this->assertSame($pullRequests, $builder->pullRequests());
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
}
