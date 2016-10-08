<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Refinery29\Test\Util\TestHelper;
use stdClass;

class CommitRepositoryTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testShowReturnsCommitEntityWithShaAndMessageOnSuccess()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $expectedItem = $this->commitItem();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($sha)
            )
            ->willReturn($this->response($expectedItem));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commit = $commitRepository->show(
            $owner,
            $repository,
            $sha
        );

        $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

        $this->assertSame($expectedItem->sha, $commit->sha());
        $this->assertSame($expectedItem->message, $commit->message());
    }

    public function testShowReturnsNullOnFailure()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $sha = $faker->sha1;

        $api = $this->getCommitApiMock();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($sha)
            )
            ->willReturn('failure');

        $commitRepository = new Repository\CommitRepository($api);

        $commit = $commitRepository->show(
            $owner,
            $repository,
            $sha
        );

        $this->assertNull($commit);
    }

    public function testAllReturnsEmptyArrayOnFailure()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn('snafu');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all(
            $owner,
            $repository,
            [
                'sha' => $sha,
            ]
        );

        $this->assertInstanceOf(Resource\Range::class, $range);
        $this->assertCount(0, $range->commits());
    }

    public function testAllSetsParamsPerPageTo250()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('per_page', 250)
            )
            ->willReturn($this->responseFromItems($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all(
            $owner,
            $repository,
            [
                'sha' => $sha,
            ]
        );
    }

    public function testAllStillAllowsSettingPerPage()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $sha = $faker->sha1;
        $perPage = $faker->randomNumber();

        $commitApi = $this->getCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('per_page', $perPage)
            )
            ->willReturn($this->responseFromItems($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all(
            $owner,
            $repository,
            [
                'sha' => $sha,
                'per_page' => $perPage,
            ]
        );
    }

    public function testAllReturnsRange()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn($this->responseFromItems($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all($owner, $repository, [
            'sha' => $sha,
        ]);

        $this->assertInstanceOf(Resource\Range::class, $range);

        $commits = $range->commits();

        $this->assertCount(count($expectedItems), $commits);

        array_walk($commits, function (Resource\CommitInterface $commit) use (&$expectedItems) {
            /*
             * API returns commits in reverse order
             */
            $expectedItem = array_pop($expectedItems);

            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;

        $endReference = $startReference;

        $commitApi = $this->getCommitApiMock();

        $commitApi
            ->expects($this->never())
            ->method($this->anything());

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commits = $commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects($this->never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commits = $commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($this->commitItem()));

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($endReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects($this->never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commits = $commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $this->assertSame([], $commits);
    }

    public function testItemsFetchesCommitsUsingShaFromEndCommit()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $startCommit = $this->commitItem();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $endCommit = $this->commitItem();

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($endReference)
            )
            ->willReturn($this->response($endCommit));

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesCommitsIfEndReferenceIsNotGiven()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $startCommit = $this->commitItem();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayNotHasKey('sha')
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $owner,
            $repository,
            $startReference
        );
    }

    public function testItemsReturnsRangeOfCommitsFromEndToStartExcludingStart()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $startCommit = $this->commitItem();
        $startCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $endCommit = $this->commitItem();
        $endCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($endReference)
            )
            ->willReturn($this->response($endCommit));

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

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            )
            ->willReturn($this->responseFromItems($segment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);

        $commits = $range->commits();

        $this->assertCount(count($expectedItems), $commits);

        array_walk($commits, function ($commit) use (&$expectedItems) {
            /*
             * API returns items in reverse order
             */
            $expectedItem = array_pop($expectedItems);

            $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->getCommitApiMock();

        $startCommit = $this->commitItem();
        $startCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $endCommit = $this->commitItem();
        $endCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($endReference)
            )
            ->willReturn($this->response($endCommit));

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

        $commitApi
            ->expects($this->at(2))
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            )
            ->willReturn($this->responseFromItems($firstSegment));

        $commitApi
            ->expects($this->at(3))
            ->method('all')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->arrayHasKeyAndValue('sha', $firstCommitFromFirstSegment->sha)
            )
            ->willReturn($this->responseFromItems($secondSegment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);

        $commits = $range->commits();

        $this->assertCount(count($expectedItems), $commits);

        array_walk($commits, function ($commit) use (&$expectedItems) {
            /*
             * API returns items in reverse order
             */
            $expectedItem = array_pop($expectedItems);

            $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Api\Repository\Commits
     */
    private function getCommitApiMock()
    {
        return $this->getMockBuilder(Api\Repository\Commits::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $sha
     * @param string $message
     *
     * @return stdClass
     */
    private function commitItem($sha = null, $message = null)
    {
        $data = new stdClass();

        $data->sha = $sha ?: $this->getFaker()->unique()->sha1;
        $data->message = $message ?: $this->getFaker()->unique()->sentence();

        return $data;
    }

    /**
     * @param int $count
     *
     * @return stdClass[]
     */
    private function commitItems($count)
    {
        $items = [];

        for ($i = 0; $i < $count; ++$i) {
            array_push($items, $this->commitItem());
        }

        return $items;
    }

    /**
     * @param stdClass $item
     *
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
     *
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
     *
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

    /**
     * @param string $key
     *
     * @return \PHPUnit_Framework_Constraint_Callback
     */
    private function arrayNotHasKey($key)
    {
        return $this->callback(function ($array) use ($key) {
            if (is_array($array)
                && !array_key_exists($key, $array)
            ) {
                return true;
            }

            return false;
        });
    }
}
