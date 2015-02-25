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
        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $builder = new ChangeLog\Builder($commitRepository);

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
        ;

        $this->assertSame([], $builder->pullRequests());
    }

    public function testPullRequestsReturnsEmptyArrayIfNoCommitsHaveBeenFoundBetweenStartAndHead()
    {
        $commitRepository = $this->getMockBuilder(ChangeLog\Repository\Commit::class)->getMock();

        $builder = new ChangeLog\Builder($commitRepository);

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
            ->end('7fc1c4f')
        ;

        $this->assertSame([], $builder->pullRequests());
    }
}
