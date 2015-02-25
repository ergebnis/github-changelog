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
        $this->assertSame($builder, $builder->start('ad77125'));
        $this->assertSame($builder, $builder->end('7fc1c4f'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage User needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfUserHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Repository needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfRepositoryHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder->user('foo');

        $builder->pullRequests();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Start reference needs to be specified
     */
    public function testPullRequestsThrowsBadMethodCallExceptionIfStartReferenceHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
        ;

        $builder->pullRequests();
    }

    public function testPullRequestsDoesNotThrowBadMethodCallExceptionIfEndReferenceHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
        ;

        $builder->pullRequests();
    }

    public function testPullRequestsReturnsEmptyArrayIfNoCommitsHaveBeenFoundBetweenStartAndEnd()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
        ;

        $this->assertSame([], $builder->pullRequests());
    }

    public function testPullRequestsReturnsEmptyArrayIfNoCommitsHaveBeenFoundBetweenStartAndHead()
    {
        $builder = new ChangeLog\Builder();

        $builder
            ->user('foo')
            ->repository('bar')
            ->start('ad77125')
            ->end('7fc1c4f')
        ;

        $this->assertSame([], $builder->pullRequests());
    }
}
