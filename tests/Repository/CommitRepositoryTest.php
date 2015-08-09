<?php

namespace Localheinz\GitHub\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use stdClass;

class CommitRepositoryTest extends PHPUnit_Framework_TestCase
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

        $commitRepository = new Repository\CommitRepository($api);

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

        $commitRepository = new Repository\CommitRepository($api);

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
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn('snafu')
        ;

        $repository = new Repository\CommitRepository($api);

        $commits = $repository->all(
            $vendor,
            $package,
            [
                'sha' => $sha,
            ]
        );

        $this->assertSame([], $commits);
    }

    public function testAllSetsParamsPerPageTo250()
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
                $this->arrayHasKeyAndValue('per_page', 250)
            )
            ->willReturn($this->responseFromItems($expectedItems))
        ;

        $repository = new Repository\CommitRepository($api);

        $repository->all(
            $vendor,
            $package,
            [
                'sha' => $sha,
            ]
        );
    }

    public function testAllStillAllowsSettingPerPage()
    {
        $vendor = 'foo';
        $package = 'bar';
        $sha = 'ad77125';
        $perPage = 13;

        $api = $this->commitApi();

        $expectedItems = $this->commitItems(15);

        $api
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->arrayHasKeyAndValue('per_page', $perPage)
            )
            ->willReturn($this->responseFromItems($expectedItems))
        ;

        $repository = new Repository\CommitRepository($api);

        $repository->all(
            $vendor,
            $package,
            [
                'sha' => $sha,
                'per_page' => $perPage,
            ]
        );
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
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn($this->responseFromItems($expectedItems))
        ;

        $repository = new Repository\CommitRepository($api);

        $commits = $repository->all(
            $vendor,
            $package, [
                'sha' => $sha,
            ]
        );

        $this->assertCount(count($expectedItems), $commits);

        // The GitHub API returns commits in reverse order!
        $commits = array_reverse($commits);

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

        $repository = new Repository\CommitRepository($api);

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

        $repository = new Repository\CommitRepository($api);

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

        $repository = new Repository\CommitRepository($api);

        $commits = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsFetchesCommitsUsingShaFromEndCommit()
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
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            )
        ;

        $repository = new Repository\CommitRepository($api);

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
        $startCommit->sha = 'start';

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
        $endCommit->sha = 'end';

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

        $countBetween = 9;
        $countBefore = 2;

        $segment = array_merge(
            $this->commitItems($countBefore),
            [
                $startCommit,
            ],
            $this->commitItems($countBetween),
            [
                $endCommit,
            ]
        );

        $expectedItems = array_slice(
            $segment,
            $countBefore + 1, // We don't want the first commit
            $countBetween + 1 // We want the commits in-between and the last commit
        );

        $api
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            )
            ->willReturn($this->responseFromItems($segment))
        ;

        $repository = new Repository\CommitRepository($api);

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
        $startCommit->sha = 'start';

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
        $endCommit->sha = 'end';

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

        $countBetweenFirstSegment = 4;
        $countBetweenSecondSegment = 5;

        $countBefore = 2;

        $firstSegment = array_merge(
            $this->commitItems($countBetweenFirstSegment),
            [
                $endCommit,
            ]
        );

        $firstCommitFromFirstSegment = reset($firstSegment);

        $secondSegment = array_merge(
            $this->commitItems($countBefore),
            [
                $startCommit,
            ],
            $this->commitItems($countBetweenSecondSegment),
            [
                $firstCommitFromFirstSegment,
            ]
        );

        $expectedItems = array_merge(
            array_slice(
                $secondSegment,
                $countBefore + 1,
                $countBetweenSecondSegment
            ),
            $firstSegment
        );

        $api
            ->expects($this->at(2))
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            )
            ->willReturn($this->responseFromItems($firstSegment))
        ;

        $api
            ->expects($this->at(3))
            ->method('all')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->arrayHasKeyAndValue('sha', $firstCommitFromFirstSegment->sha)
            )
            ->willReturn($this->responseFromItems($secondSegment))
        ;

        $repository = new Repository\CommitRepository($api);

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
     * @return PHPUnit_Framework_MockObject_MockObject|Api\Repository\Commits
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
            // The GitHub API returns commits in reverse order!
            array_unshift($response, $this->response($commit));
        });

        return $response;
    }

    /**
     * @param string $key
     * @param string $value
     * @return \PHPUnit_Framework_Constraint_Callback
     */
    private function arrayHasKeyAndValue($key, $value)
    {
        return $this->callback(function ($array) use ($key, $value) {
            if (is_array($array)
                && array_key_exists($key, $array)
                && $value === $array[$key]
            ) {
                return true;
            }

            return false;
        });
    }
}
