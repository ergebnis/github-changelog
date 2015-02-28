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

        $api = $this->commitApi();

        $expectedItem = $this->commitItem();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($sha)
            )
            ->willReturn($this->response($expectedItem))
        ;

        $commitRepository = new Repository\Commit($api);

        $commit = $commitRepository->show(
            $vendor,
            $package,
            $sha
        );

        $this->assertInstanceOf(Entity\Commit::class, $commit);

        $this->assertSame($expectedItem->sha, $commit->sha());
        $this->assertSame($expectedItem->message, $commit->message());
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

        $api = $this->commitApi();

        $expectedItems = $this->commitItems(15);

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
            ->willReturn($this->responseFromItems($expectedItems))
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->all($vendor, $package, [
            'sha' => $sha,
        ]);

        $this->assertCount(count($expectedItems), $commits);

        array_walk($commits, function ($commit) use (&$expectedItems) {
            $expectedItem = array_shift($expectedItems);

            $this->assertInstanceOf(Entity\Commit::class, $commit);

            /* @var Entity\Commit $commit */
            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = $startReference;

        $api = $this->commitApi();

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

        $api = $this->commitApi();

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

        $api = $this->commitApi();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($this->commitItem()))
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

        $api = $this->commitApi();

        $startCommit = $this->commitItem();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($startCommit))
        ;

        $endCommit = $this->commitItem();

        $api
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($this->response($endCommit))
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

        $api = $this->commitApi();

        $startCommit = $this->commitItem();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($startCommit))
        ;

        $endCommit = $this->commitItem();

        $api
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($this->response($endCommit))
        ;

        $countBetween = 13;
        $countAfter = 17;

        $allItems = array_merge(
            [
                $startCommit,
            ],
            $this->commitItems($countBetween),
            [
                $endCommit,
            ],
            $this->commitItems($countAfter)
        );

        $expectedItems = array_slice(
            $allItems,
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
            ->willReturn($this->responseFromItems($allItems))
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertCount(count($expectedItems), $commits);

        array_walk($commits, function ($commit) use (&$expectedItems) {
            $expectedItem = array_shift($expectedItems);

            $this->assertInstanceOf(Entity\Commit::class, $commit);

            /* @var Entity\Commit $commit */
            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $api = $this->commitApi();

        $startCommit = $this->commitItem();

        $api
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($startCommit))
        ;

        $endCommit = $this->commitItem();

        $api
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($endReference)
            )
            ->willReturn($this->response($endCommit))
        ;

        $firstBatch = array_merge(
            [
                $startCommit,
            ],
            $this->commitItems(50)
        );

        $lastCommitFromFirstBatch = end($firstBatch);

        $secondBatch = array_merge(
            [
                $lastCommitFromFirstBatch,
            ],
            $this->commitItems(20)
        );

        $expectedItems = array_merge(
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
            ->willReturn($this->responseFromItems($firstBatch))
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
            ->willReturn($this->responseFromItems($secondBatch))
        ;

        $repository = new Repository\Commit($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertCount(count($expectedItems), $commits);

        array_walk($commits, function ($commit) use (&$expectedItems) {
            $expectedItem = array_shift($expectedItems);

            $this->assertInstanceOf(Entity\Commit::class, $commit);

            /* @var Entity\Commit $commit */
            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
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

    /**
     * @param string $sha
     * @param string $message
     * @return stdClass
     */
    private function commitItem($sha = null, $message = null)
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
    private function commitItems($count)
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            array_push($items, $this->commitItem());
        }

        return $items;
    }

    /**
     * @param stdClass $item
     * @return array
     */
    private function response(stdClass $item)
    {
        $template = file_get_contents(__DIR__ . '/_response/commit.json');

        $body = str_replace(
            [
                '%sha%',
                '%message%',
            ],
            [
                $item->sha,
                $item->message,
            ],
            $template
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
    private function responseFromItems(array $commits)
    {
        $response = [];

        array_walk($commits, function ($commit) use (&$response) {
            array_push($response, $this->response($commit));
        });

        return $response;
    }
}
