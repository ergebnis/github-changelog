<?php

namespace Localheinz\ChangeLog\Test\GitHub;

use Localheinz\ChangeLog;
use PHPUnit_Framework_TestCase;

class BuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage User needs to be specified
     */
    public function testFromPullRequestsThrowsBadMethodCallExceptionIfUserHasNotBeenSet()
    {
        $builder = new ChangeLog\Builder();

        $builder->fromPullRequests();
    }
}
