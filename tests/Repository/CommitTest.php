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
        $vendor = 'foo';
        $package = 'bar';
        $sha = 'ad77125';

        $api = $this->api();

        $expected = $this->commit();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($sha)
            )
            ->willReturn($this->responseFromCommit($expected))
        ;

        $commitRepository = new Repository\Commit($api);

        $commit = $commitRepository->show(
            $vendor,
            $package,
            $sha
        );

        $this->assertInstanceOf(Entity\Commit::class, $commit);

        $this->assertSame($expected->sha, $commit->sha());
        $this->assertSame($expected->message, $commit->message());
    }

    public function testShowReturnsNullOnFailure()
    {
        $vendor = 'foo';
        $package = 'bar';
        $sha = 'ad77125';

        $api = $this->api();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($sha)
            )
            ->willReturn('failure')
        ;

        $commitRepository = new Repository\Commit($api);

        $commit = $commitRepository->show(
            $vendor,
            $package,
            $sha
        );

        $this->assertNull($commit);
    }

    public function testAllReturnsEmptyArrayOnFailure()
    {
        $vendor = 'foo';
        $package = 'bar';
        $sha = 'ad77125';

        $api = $this->api();

        $api
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $sha,
                ])
            )
            ->willReturn('snafu')
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->all($vendor, $package, [
            'sha' => $sha,
        ]);

        $this->assertSame([], $commits);
    }

    public function testAllReturnsArrayOfCommitEntities()
    {
        $vendor = 'foo';
        $package = 'bar';
        $sha = 'ad77125';

        $api = $this->api();

        $expectedCommits = $this->commits(15);

        $api
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $sha,
                ])
            )
            ->willReturn($this->responseFromCommits($expectedCommits))
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->all($vendor, $package, [
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

    /* HELLO */

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = $startReference;

        $api = $this->api();

        $api
            ->expects($this->never())
            ->method($this->anything())
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $api = $this->api();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn(null)
        ;

        $api
            ->expects($this->never())
            ->method('all')
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $api = $this->api();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->responseFromCommit($this->commit()))
        ;

        $api
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn(null)
        ;

        $api
            ->expects($this->never())
            ->method('all')
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsFetchesCommitsUsingShaFromStartCommit()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $api = $this->api();

        $startCommit = $this->commit();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->responseFromCommit($startCommit))
        ;

        $endCommit = $this->commit();

        $api
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($this->responseFromCommit($endCommit))
        ;

        $api
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $startCommit->sha,
                ])
            )
        ;

        $repository = new Repository\Commit($api);

        $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );
    }

    public function testItemsReturnsArrayOfCommitsFromStartToEndExcludingStart()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $api = $this->api();

        $startCommit = $this->commit();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->responseFromCommit($startCommit))
        ;

        $endCommit = $this->commit();

        $api
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($this->responseFromCommit($endCommit))
        ;

        $countBetween = 13;
        $countAfter = 17;

        $allCommits = array_merge(
            [
                $startCommit,
            ],
            $this->commits($countBetween),
            [
                $endCommit,
            ],
            $this->commits($countAfter)
        );

        $expectedCommits = array_slice(
            $allCommits,
            1,
            $countBetween + 1
        );

        $api
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $startCommit->sha,
                ])
            )
            ->willReturn($this->responseFromCommits($allCommits))
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertCount(count($expectedCommits), $commits);

        foreach ($commits as $commit) {
            $expectedCommit = array_shift($expectedCommits);

            $this->assertInstanceOf(Entity\Commit::class, $commit);
            $this->assertSame($expectedCommit->sha, $commit->sha());
            $this->assertSame($expectedCommit->message, $commit->message());
        }
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $api = $this->api();

        $startCommit = $this->commit();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->responseFromCommit($startCommit))
        ;

        $endCommit = $this->commit();

        $api
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($this->responseFromCommit($endCommit))
        ;

        $firstBatch = array_merge(
            [
                $startCommit,
            ],
            $this->commits(50)
        );

        $lastCommitFromFirstBatch = end($firstBatch);

        $secondBatch = array_merge(
            [
                $lastCommitFromFirstBatch,
            ],
            $this->commits(20)
        );

        $expectedCommits = array_merge(
            array_slice(
                $firstBatch,
                1
            ),
            array_slice(
                $secondBatch,
                1
            )
        );

        $api
            ->expects($this->at(2))
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $startCommit->sha,
                ])
            )
            ->willReturn($this->responseFromCommits($firstBatch))
        ;

        $api
            ->expects($this->at(3))
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo([
                    'sha' => $lastCommitFromFirstBatch->sha,
                ])
            )
            ->willReturn($this->responseFromCommits($secondBatch))
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
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
    private function commit($sha = null, $message = null)
    {
        $data = new stdClass();

        $data->sha = $sha ?: $this->faker()->unique()->sha1;
        $data->message = $message ?: $this->faker()->unique()->sentence();

        return $data;
    }

    /**
     * @param int $count
     * @return stdClass[]
     */
    private function commits($count)
    {
        $commits = [];

        for ($i = 0; $i < $count; $i++) {
            array_push($commits, $this->commit());
        }

        return $commits;
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
    private function api()
    {
        return $this->getMockBuilder(Api\Repository\Commits::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
