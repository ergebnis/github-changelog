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
}
