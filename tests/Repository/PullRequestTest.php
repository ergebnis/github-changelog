<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Localheinz\ChangeLog\Repository;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    public function testPullRequestsReturnsEmptyArrayWhenStartAndEndAreTheSame()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';

        $end = $start;

        $pullRequestRepository = new Repository\PullRequest();

        $pullRequests = $pullRequestRepository->pullRequests(
            $user,
            $repository,
            $start,
            $end
        );

        $this->assertSame([], $pullRequests);
    }
}
