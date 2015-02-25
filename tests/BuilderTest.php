<?php

namespace Localheinz\ChangeLog\Test\GitHub;

use Localheinz\ChangeLog;
use PHPUnit_Framework_TestCase;

class BuilderTest extends PHPUnit_Framework_TestCase
{
    public function testFluentInterface()
    {
        $builder = new ChangeLog\Builder();

        $this->assertSame($builder, $builder->user('foo'));
        $this->assertSame($builder, $builder->repository('bar'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage User needs to be specified
     */
    public function testFromPullRequestsThrowsBadMethodCallExceptionIfUserHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder->fromPullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Repository needs to be specified
     */
    public function testFromPullRequestsThrowsBadMethodCallExceptionIfRepositoryHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder->user('foo');

        $builder->fromPullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Start reference needs to be specified
     */
    public function testFromPullRequestsThrowsBadMethodCallExceptionIfStartReferenceHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
        ;

        $builder->fromPullRequests();
    }

    public function testFromPullRequestsDoesNotThrowBadMethodCallExceptionIfEndReferenceHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
        ;

        $builder->fromPullRequests();
    }

    public function testFromPullRequestsReturnsEmptyArrayIfNoCommitsHaveBeenFoundBetweenStartAndEnd()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
        ;

        $this->assertSame([], $builder->fromPullRequests());
    }

    public function testFromPullRequestsReturnsEmptyArrayIfNoCommitsHaveBeenFoundBetweenStartAndHead()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
            ->end('7fc1c4f')
        ;

        $this->assertSame([], $builder->fromPullRequests());
    }
}
