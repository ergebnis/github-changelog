<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Github\Api;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class CommitTest extends PHPUnit_Framework_TestCase
{
    public function testCommitReturnsCommitEntityWithShaAndMessage()
    {
        $user = 'foo';
        $repository = 'bar';
        $reference = 'ad77125';

        $commitApi = $this->commitApi();

        $response = json_decode(
            $this->asset('commit.json'),
            true
        );

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($user),
                $this->equalTo($repository),
                $this->equalTo($reference)
            )
            ->willReturn($response)
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commit = $commitRepository->commit(
            $user,
            $repository,
            $reference
        );

        $this->assertInstanceOf(Entity\Commit::class, $commit);

        $this->assertSame('6dcb09b5b57875f334f61aebed695e2e4193db5e', $commit->sha());
        $this->assertSame('Fix all the bugs', $commit->message());
    }

    public function testCommitsReturnsEmptyArrayWhenStartAndEndAreTheSame()
    {
        $user = 'foo';
        $repository = 'bar';
        $start = 'ad77125';

        $end = $start;

        $commitRepository = new Repository\Commit($this->commitApi());

        $commits = $commitRepository->commits(
            $user,
            $repository,
            $start,
            $end
        );

        $this->assertSame([], $commits);
    }

    /**
     * @param string $name
     * @return string
     */
    private function asset($name)
    {
        return file_get_contents(sprintf(
            '%s/_asset/%s',
            __DIR__,
            $name
        ));
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function commitApi()
    {
        return $this->getMockBuilder(Api\Repository\Commits::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
