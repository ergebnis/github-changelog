<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use stdClass;

class CommitTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Faker\Generator
     */
    private $faker;

    protected function setUp()
    {
        $this->faker = Faker\Factory::create('en_US');
        $this->faker->seed(9000);
    }

    protected function tearDown()
    {
        unset($this->faker);
        unset($this->commitTemplate);
    }

    public function testCommitReturnsCommitEntityWithShaAndMessageOnSuccess()
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
            ->willReturn($this->responseCommit($expectedCommit))
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commit = $commitRepository->commit(
            $userName,
            $repository,
            $sha
        );

        $this->assertInstanceOf(Entity\Commit::class, $commit);

        $this->assertSame($expectedCommit->sha, $commit->sha());
        $this->assertSame($expectedCommit->message, $commit->message());
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

    public function testCommitsReturnsEmptyArrayOnFailure()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitApi = $this->commitApi();

        $response = 'failure';

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
            ->willReturn($response)
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commits = $commitRepository->commits(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertSame([], $commits);
    }

    public function testCommitsReturnsArrayOfCommitEntities()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitApi = $this->commitApi();

        $expectedCommits = [];
        for ($i = 0; $i < 15; $i++) {
            array_push($expectedCommits, $this->commitData());
        }

        $response = $this->responseCommits($expectedCommits);

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
            ->willReturn($response)
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commits = $commitRepository->commits(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $this->assertCount(count($expectedCommits), $commits);

        foreach ($commits as $commit) {
            $expectedCommit = array_shift($expectedCommits);

            $this->assertInstanceOf(Entity\Commit::class, $commit);
            $this->assertSame($expectedCommit->sha, $commit->sha());
            $this->assertSame($expectedCommit->message, $commit->message());
        }
    }

    public function testCommitsReturnsArrayOfCommitsFromStartToEndExcludingStart()
    {
        $userName = 'foo';
        $repository = 'bar';

        $commitApi = $this->commitApi();

        $expectedCommits = [];

        $startCommit = $this->commitData();
        array_push($expectedCommits, $startCommit);

        for ($i = 0; $i < 5; $i++) {
            array_push($expectedCommits, $this->commitData());
        }

        $endCommit = $this->commitData();
        array_push($expectedCommits, $endCommit);

        $response = $this->responseCommits($expectedCommits);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo([
                    'sha' => $startCommit->sha,
                ])
            )
            ->willReturn($response)
        ;

        $commitRepository = new Repository\Commit($commitApi);

        $commits = $commitRepository->commits(
            $userName,
            $repository,
            $startCommit->sha,
            $endCommit->sha
        );

        $this->assertCount(count($expectedCommits) - 1, $commits);

        array_shift($expectedCommits);

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

        $data->sha = $sha ?: $this->faker->unique()->sha1;
        $data->message = $message ?: $this->faker->unique()->sentence();

        return $data;
    }

    /**
     * @param stdClass $commit
     * @return array
     */
    private function responseCommit(stdClass $commit)
    {
        $commitTemplate = file_get_contents(__DIR__ . '/_response/commit.json');

        $body = sprintf(
            $commitTemplate,
            $commit->sha,
            $commit->message
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
    private function responseCommits(array $commits)
    {
        $response = [];

        array_walk($commits, function ($commit) use (&$response) {
            array_push($response, $this->responseCommit($commit));
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
