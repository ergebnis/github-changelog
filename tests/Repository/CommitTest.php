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
     * @var string
     */
    private $responseCommitTemplate;

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
        unset($this->responseCommitTemplate);
        unset($this->faker);
    }

    public function testCommitReturnsCommitEntityWithShaAndMessageOnSuccess()
    {
        $userName = 'foo';
        $repository = 'bar';
        $sha = 'ad77125';

        $commitApi = $this->commitApi();

        $expectedCommit = $this->commitData();

        $response = json_decode(
            $this->responseCommit(
                $expectedCommit->sha,
                $expectedCommit->message
            ),
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

        $body = array_reduce($expectedCommits, function ($carry, $expectedCommit) {
            if ('' !== $carry) {
                $carry .= ',';
            }

            return $carry . $this->responseCommit(
                $expectedCommit->sha,
                $expectedCommit->message
            );
        }, '');

        $body = '[' . $body . ']';

        $response = json_decode($body, true);

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

    /**
     * @return stdClass
     */
    private function commitData()
    {
        $data = new stdClass();

        $data->sha = $this->faker->unique()->sha1;
        $data->message = $this->faker->unique()->sentence();

        return $data;
    }

    /**
     * @param string $sha
     * @param string $message
     * @return string
     */
    private function responseCommit($sha, $message)
    {
        return sprintf(
            $this->responseCommitTemplate(),
            $sha,
            $message
        );
    }

    /**
     * @return string
     */
    private function responseCommitTemplate()
    {
        if (null === $this->responseCommitTemplate) {
            $this->responseCommitTemplate = file_get_contents(__DIR__ . '/_response/commit.json');
        }

        return $this->responseCommitTemplate;
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
