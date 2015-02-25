<?php

namespace Localheinz\ChangeLog\Test\GitHub;

use Localheinz\ChangeLog;
use PHPUnit_Framework_TestCase;

class BuilderTest extends PHPUnit_Framework_TestCase
{
    public function testFluentInterface()
    {
        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $builder = new ChangeLog\Builder($commitRepository);

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
        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $builder = new ChangeLog\Builder($commitRepository);

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Repository needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfRepositoryHasNotBeenSet()
    {
        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $builder = new ChangeLog\Builder($commitRepository);

        $builder->user('foo');

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Start reference needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfStartReferenceHasNotBeenSet()
    {
        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $builder = new ChangeLog\Builder($commitRepository);

        $builder
            ->user('foo')
            ->repository('bar')
        ;

        $builder->pullRequests();
    }

    public function testPullRequestsDoesNotThrowBadMethodCallExceptionIfEndReferenceHasNotBeenSet()
    {
        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $builder = new ChangeLog\Builder($commitRepository);

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
        ;

        $builder->pullRequests();
    }

    public function testPullRequestsReturnsEmptyArrayIfNoCommitsHaveBeenFoundBetweenStartAndEnd()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';

        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $commitRepository
            ->expects($this->once())
            ->method('commits')
            ->with(
                $this->equalTo($user),
                $this->equalTo($repository),
                $this->equalTo($start),
                $this->equalTo(null)
            )
            ->willReturn([])
        ;

        $builder = new ChangeLog\Builder($commitRepository);

        $builder
            ->user($user)
            ->repository($repository)
            ->start($start)
        ;

        $this->assertSame([], $builder->pullRequests());
    }

    public function testPullRequestsReturnsEmptyArrayIfNoCommitsHaveBeenFoundBetweenStartAndHead()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';
        $end = '7fc1c4f';

        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $commitRepository
            ->expects($this->once())
            ->method('commits')
            ->with(
                $this->equalTo($user),
                $this->equalTo($repository),
                $this->equalTo($start),
                $this->equalTo($end)
            )
            ->willReturn([])
        ;

        $builder = new ChangeLog\Builder($commitRepository);

        $builder
            ->user($user)
            ->repository($repository)
            ->start($start)
            ->end($end)
        ;

        $this->assertSame([], $builder->pullRequests());
    }
}
