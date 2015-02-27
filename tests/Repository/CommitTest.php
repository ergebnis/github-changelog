<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use stdClass;

class CommitTest extends PHPUnit_Framework_TestCase
{
    use FakerTrait;

    public function testShowReturnsCommitEntityWithShaAndMessageOnSuccess()
    {
        $userName = 'foo';
        $repository = 'bar';
        $sha = 'ad77125';

        $commitApi = $this->commitApi();

        $expectedCommit = $this->commitData();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($sha)
            )
            ->willReturn($this->responseFromCommit($expectedCommit))
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commit = $commitRepository->show(
            $userName,
            $repository,
            $sha
        );

        $this->assertInstanceOf(Entity\Commit::class, $commit);

        $this->assertSame($expectedCommit->sha, $commit->sha());
        $this->assertSame($expectedCommit->message, $commit->message());
    }

    public function testShowReturnsNullOnFailure()
    {
        $userName = 'foo';
        $repository = 'bar';
        $sha = 'ad77125';

        $commitApi = $this->commitApi();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo($sha)
            )
            ->willReturn('failure')
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commit = $commitRepository->show(
            $userName,
            $repository,
            $sha
        );

        $this->assertNull($commit);
    }

    public function testAllReturnsArrayOfCommitEntities()
    {
        $userName = 'foo';
        $repository = 'bar';
        $sha = 'ad77125';

        $commitApi = $this->commitApi();

        $expectedCommits = [];
        for ($i = 0; $i < 15; $i++) {
            array_push($expectedCommits, $this->commitData());
        }

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo([
                    'sha' => $sha,
                ])
            )
            ->willReturn($this->responseFromCommits($expectedCommits))
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commits = $commitRepository->all($userName, $repository, [
            'sha' => $sha,
        ]);

        $this->assertCount(count($expectedCommits), $commits);

        foreach ($commits as $commit) {
            $expectedCommit = array_shift($expectedCommits);

            $this->assertInstanceOf(Entity\Commit::class, $commit);
            $this->assertSame($expectedCommit->sha, $commit->sha());
            $this->assertSame($expectedCommit->message, $commit->message());
        }
    }

    /**
     * @param string $sha
     * @param string $message
     * @return stdClass
     */
    private function commitData($sha = null, $message = null)
    {
        $data = new stdClass();

        $data->sha = $sha ?: $this->faker()->unique()->sha1;
        $data->message = $message ?: $this->faker()->unique()->sentence();

        return $data;
    }

    /**
     * @param stdClass $commit
     * @return array
     */
    private function responseFromCommit(stdClass $commit)
    {
        $commitTemplate = file_get_contents(__DIR__ . '/_response/commit.json');

        $body = str_replace(
            [
                '%sha%',
                '%message%',
            ],
            [
                $commit->sha,
                $commit->message,
            ],
            $commitTemplate
        );

        return json_decode(
            $body,
            true
        );
    }

    /**
     * @param array $commits
     * @return array
     */
    private function responseFromCommits(array $commits)
    {
        $response = [];

        array_walk($commits, function ($commit) use (&$response) {
            array_push($response, $this->responseFromCommit($commit));
        });

        return $response;
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
