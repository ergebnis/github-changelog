<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Github\Api;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class CommitTest extends PHPUnit_Framework_TestCase
{
    public function testCommitReturnsCommitEntityWithShaAndMessageOnSuccess()
    {
        $userName = 'foo';
        $repository = 'bar';
        $sha = 'ad77125';

        $commitApi = $this->commitApi();

        $response = json_decode(
            $this->response('commit.json'),
            true
        );

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($sha)
            )
            ->willReturn($response)
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commit = $commitRepository->commit(
            $userName,
            $repository,
            $sha
        );

        $this->assertInstanceOf(Entity\Commit::class, $commit);

        $this->assertSame('6dcb09b5b57875f334f61aebed695e2e4193db5e', $commit->sha());
        $this->assertSame('Fix all the bugs', $commit->message());
    }

    public function testCommitReturnsNullOnFailure()
    {
        $userName = 'foo';
        $repository = 'bar';
        $sha = 'ad77125';

        $commitApi = $this->commitApi();

        $response = 'failure';

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($sha)
            )
            ->willReturn($response)
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commit = $commitRepository->commit(
            $userName,
            $repository,
            $sha
        );

        $this->assertNull($commit);
    }

    public function testCommitsReturnsEmptyArrayWhenStartAndEndAreTheSame()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';

        $endSha = $startSha;

        $commitRepository = new Repository\Commit($this->commitApi());

        $commits = $commitRepository->commits(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $commits);
    }

    public function testCommitsDelegatesToCommitApi()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitApi = $this->commitApi();

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo([
                    'sha' => $startSha,
                ])
            )
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commitRepository->commits(
            $userName,
            $repository,
            $startSha,
            $endSha
        );
    }

    /**
     * @param string $name
     * @return string
     */
    private function response($name)
    {
        return file_get_contents(sprintf(
            '%s/_response/%s',
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
