<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Test\Util\DataProviderTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use stdClass;

class CommitTest extends PHPUnit_Framework_TestCase
{
    use DataProviderTrait;

    public function testShowReturnsCommitEntityWithShaAndMessageOnSuccess()
    {
        $vendor = 'foo';
        $package = 'bar';
        $sha = 'ad77125';

        $api = $this->commitApi();

        $expected = $this->commitData();

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

        $api = $this->commitApi();

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

        $api = $this->commitApi();

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

        $commitRepository = new Repository\Commit($api);

        $commits = $commitRepository->all($vendor, $package, [
            'sha' => $sha,
        ]);

        $this->assertSame([], $commits);
    }

    public function testAllReturnsArrayOfCommitEntities()
    {
        $vendor = 'foo';
        $package = 'bar';
        $sha = 'ad77125';

        $api = $this->commitApi();

        $expectedCommits = [];
        for ($i = 0; $i < 15; $i++) {
            array_push($expectedCommits, $this->commitData());
        }

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

        $commitRepository = new Repository\Commit($api);

        $commits = $commitRepository->all($vendor, $package, [
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
