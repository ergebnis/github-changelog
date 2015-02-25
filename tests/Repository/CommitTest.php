<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Localheinz\ChangeLog\Repository;
use PHPUnit_Framework_TestCase;

class CommitTest extends PHPUnit_Framework_TestCase
{
    public function testCommitsReturnsEmptyArrayWhenStartAndEndAreTheSame()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';

        $end = $start;

        $commitRepository = new Repository\Commit();

        $commits = $commitRepository->commits(
            $user,
            $repository,
            $start,
            $end
        );

        $this->assertSame([], $commits);
    }
}
