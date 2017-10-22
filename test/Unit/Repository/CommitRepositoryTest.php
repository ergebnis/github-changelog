<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Exception\ReferenceNotFound;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class CommitRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testIsFinal()
    {
        $this->assertClassIsFinal(Repository\CommitRepository::class);
    }

    public function testImplementsCommitRepositoryInterface()
    {
        $this->assertClassImplementsInterface(Repository\CommitRepositoryInterface::class, Repository\CommitRepository::class);
    }

    public function testShowReturnsCommitEntityWithShaAndMessageOnSuccess()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $expectedItem = $this->commitItem();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($sha)
            )
            ->willReturn($this->response($expectedItem));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commit = $commitRepository->show(
            $owner,
            $name,
            $sha
        );

        $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

        $this->assertSame($expectedItem->sha, $commit->sha());
        $this->assertSame($expectedItem->message, $commit->message());
    }

    public function testShowThrowsCommitNotFoundOnFailure()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $sha = $faker->sha1;

        $api = $this->createCommitApiMock();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($sha)
            )
            ->willReturn('failure');

        $commitRepository = new Repository\CommitRepository($api);

        $this->expectException(ReferenceNotFound::class);

        $commitRepository->show(
            $owner,
            $name,
            $sha
        );
    }

    public function testAllReturnsEmptyArrayOnFailure()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn('snafu');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all(
            $owner,
            $name,
            [
                'sha' => $sha,
            ]
        );

        $this->assertInstanceOf(Resource\Range::class, $range);
        $this->assertCount(0, $range->commits());
    }

    public function testAllSetsParamsPerPageTo250()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('per_page', 250)
            )
            ->willReturn($this->responseFromItems($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all(
            $owner,
            $name,
            [
                'sha' => $sha,
            ]
        );
    }

    public function testAllStillAllowsSettingPerPage()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $sha = $faker->sha1;
        $perPage = $faker->randomNumber();

        $commitApi = $this->createCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('per_page', $perPage)
            )
            ->willReturn($this->responseFromItems($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all(
            $owner,
            $name,
            [
                'sha' => $sha,
                'per_page' => $perPage,
            ]
        );
    }

    public function testAllReturnsRange()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn($this->responseFromItems($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all($owner, $name, [
            'sha' => $sha,
        ]);

        $this->assertInstanceOf(Resource\Range::class, $range);

        $commits = $range->commits();

        $this->assertCount(\count($expectedItems), $commits);

        \array_walk($commits, function (Resource\CommitInterface $commit) use (&$expectedItems) {
            /*
             * API returns commits in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;

        $endReference = $startReference;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->never())
            ->method($this->anything());

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);
        $this->assertEmpty($range->commits());
        $this->assertEmpty($range->pullRequests());
    }

    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects($this->never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);
        $this->assertEmpty($range->commits());
        $this->assertEmpty($range->pullRequests());
    }

    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference)
            )
            ->willReturn($this->response($this->commitItem()));

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($endReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects($this->never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);
        $this->assertEmpty($range->commits());
        $this->assertEmpty($range->pullRequests());
    }

    public function testItemsFetchesCommitsUsingShaFromEndCommit()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $endCommit = $this->commitItem();

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($endReference)
            )
            ->willReturn($this->response($endCommit));

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesCommitsIfEndReferenceIsNotGiven()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayNotHasKey('sha')
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $owner,
            $name,
            $startReference
        );
    }

    public function testItemsReturnsRangeOfCommitsFromEndToStartExcludingStart()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem();
        $startCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $endCommit = $this->commitItem();
        $endCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($endReference)
            )
            ->willReturn($this->response($endCommit));

        $countBetween = 9;
        $countBefore = 2;

        $segment = \array_merge(
            $this->commitItems($countBefore),
            [
                $startCommit,
            ],
            $this->commitItems($countBetween),
            [
                $endCommit,
            ]
        );

        $expectedItems = \array_slice(
            $segment,
            $countBefore + 1, // We don't want the first commit
            $countBetween + 1 // We want the commits in-between and the last commit
        );

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            )
            ->willReturn($this->responseFromItems($segment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);

        $commits = $range->commits();

        $this->assertCount(\count($expectedItems), $commits);

        \array_walk($commits, function ($commit) use (&$expectedItems) {
            /*
             * API returns items in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem();
        $startCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference)
            )
            ->willReturn($this->response($startCommit));

        $endCommit = $this->commitItem();
        $endCommit->sha = $faker->sha1;

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($endReference)
            )
            ->willReturn($this->response($endCommit));

        $countBetweenFirstSegment = 4;
        $countBetweenSecondSegment = 5;

        $countBefore = 2;

        $firstSegment = \array_merge(
            $this->commitItems($countBetweenFirstSegment),
            [
                $endCommit,
            ]
        );

        $firstCommitFromFirstSegment = \reset($firstSegment);

        $secondSegment = \array_merge(
            $this->commitItems($countBefore),
            [
                $startCommit,
            ],
            $this->commitItems($countBetweenSecondSegment),
            [
                $firstCommitFromFirstSegment,
            ]
        );

        $expectedItems = \array_merge(
            \array_slice(
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
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('sha', $endCommit->sha)
            )
            ->willReturn($this->responseFromItems($firstSegment));

        $commitApi
            ->expects($this->at(3))
            ->method('all')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->arrayHasKeyAndValue('sha', $firstCommitFromFirstSegment->sha)
            )
            ->willReturn($this->responseFromItems($secondSegment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);

        $commits = $range->commits();

        $this->assertCount(\count($expectedItems), $commits);

        \array_walk($commits, function ($commit) use (&$expectedItems) {
            /*
             * API returns items in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            $this->assertSame($expectedItem->sha, $commit->sha());
            $this->assertSame($expectedItem->message, $commit->message());
        });
    }

    /**
     * @return Api\Repository\Commits|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createCommitApiMock(): Api\Repository\Commits
    {
        return $this->createMock(Api\Repository\Commits::class);
    }

    private function commitItem(string $sha = null, string $message = null): \stdClass
    {
        $faker = $this->faker();

        $data = new \stdClass();

        $data->sha = $sha ?: $faker->unique()->sha1;
        $data->message = $message ?: $faker->unique()->sentence();

        return $data;
    }

    /**
     * @param int $count
     *
     * @return \stdClass[]
     */
    private function commitItems(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; ++$i) {
            $items[] = $this->commitItem();
        }

        return $items;
    }

    /**
     * @param \stdClass $item
     *
     * @return array
     */
    private function response(\stdClass $item): array
    {
        $template = \file_get_contents(__DIR__ . '/_response/commit.json');

        $body = \str_replace(
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

        return \json_decode(
            $body,
            true
        );
    }

    /**
     * @param array $commits
     *
     * @return array
     */
    private function responseFromItems(array $commits): array
    {
        $response = [];

        \array_walk($commits, function ($commit) use (&$response) {
            // The GitHub API returns commits in reverse order!
            \array_unshift($response, $this->response($commit));
        });

        return $response;
    }

    private function arrayHasKeyAndValue(string $key, $value): Framework\Constraint\Callback
    {
        return $this->callback(function ($array) use ($key, $value) {
            if (\is_array($array)
                && \array_key_exists($key, $array)
                && $value === $array[$key]
            ) {
                return true;
            }

            return false;
        });
    }

    private function arrayNotHasKey(string $key): Framework\Constraint\Callback
    {
        return $this->callback(function ($array) use ($key) {
            if (\is_array($array)
                && !\array_key_exists($key, $array)
            ) {
                return true;
            }

            return false;
        });
    }
}
