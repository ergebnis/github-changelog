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
            ->willReturn($this->responseFromCommit($expectedCommit))
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

        $commit = $commitRepository->commit(
            $userName,
            $repository,
            $sha
        );

        $this->assertNull($commit);
    }

    public function testCommitsDoesNotQueryGitHubApiWhenStartAndEndReferencesAreTheSame()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';

        $endSha = $startSha;

        $commitApi = $this->commitApi();

        $commitApi
            ->expects($this->never())
            ->method($this->anything())
        ;

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
            ->willReturn('failure')
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

    public function testCommitsReturnsArrayOfCommitsFromStartToEndExcludingStart()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitApi = $this->commitApi();

        $commitsReturnedByGitHubApi = [];
        for ($i = 0; $i < 50; $i++) {
            array_push($commitsReturnedByGitHubApi, $this->commitData());
        }

        $expectedCommits = array_slice(
            $commitsReturnedByGitHubApi,
            1,
            12
        );

        $startCommit = reset($commitsReturnedByGitHubApi);
        $startCommit->sha = $startSha;

        $endCommit = end($expectedCommits);
        $endCommit->sha = $endSha;

        reset($expectedCommits);

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
            ->willReturn($this->responseFromCommits($commitsReturnedByGitHubApi))
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

    public function testCommitsRequeriesIfEndIsNotContainedInFirstBatch()
    {
        $userName = 'foo';
        $repository = 'bar';
        $startSha = 'ad77125';
        $endSha = '7fc1c4f';

        $commitApi = $this->commitApi();

        $firstBatch = [];
        for ($i = 0; $i < 50; $i++) {
            array_push($firstBatch, $this->commitData());
        }

        $lastCommitFromFirstBatch = end($firstBatch);
        reset($firstBatch);

        $secondBatch = [
            $lastCommitFromFirstBatch,
        ];

        for ($i = 0; $i < 50; $i++) {
            array_push($secondBatch, $this->commitData());
        }

        $expectedCommits = array_merge(
            array_slice(
                $firstBatch,
                1
            ),
            array_slice(
                $secondBatch,
                1,
                10
            )
        );

        $startCommit = reset($firstBatch);
        $startCommit->sha = $startSha;

        $endCommit = end($expectedCommits);
        $endCommit->sha = $endSha;

        reset($expectedCommits);

        $commitApi
            ->expects($this->at(0))
            ->method('all')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo([
                    'sha' => $startSha,
                ])
            )
            ->willReturn($this->responseFromCommits($firstBatch))
        ;

        $commitApi
            ->expects($this->at(1))
            ->method('all')
            ->with(
                $this->equalTo($userName),
                $this->equalTo($repository),
                $this->equalTo([
                    'sha' => $lastCommitFromFirstBatch->sha,
                ])
            )
            ->willReturn($this->responseFromCommits($secondBatch))
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
