<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 */
final class CommitRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsCommitRepositoryInterface(): void
    {
        $this->assertClassImplementsInterface(Repository\CommitRepositoryInterface::class, Repository\CommitRepository::class);
    }

    public function testShowReturnsCommitEntityWithShaAndMessageOnSuccess(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $expectedItem = $this->commitItem();

        $commitApi
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($sha)
            )
            ->willReturn($expectedItem);

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commit = $commitRepository->show(
            $repository,
            $sha
        );

        self::assertSame($expectedItem['sha'], $commit->sha());
        self::assertSame($expectedItem['commit']['message'], $commit->message());
    }

    public function testShowThrowsCommitNotFoundOnFailure(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $api = $this->createMock(Api\Repository\Commits::class);

        $api
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($sha)
            )
            ->willReturn('failure');

        $commitRepository = new Repository\CommitRepository($api);

        $this->expectException(Exception\ReferenceNotFound::class);

        $commitRepository->show(
            $repository,
            $sha
        );
    }

    public function testAllReturnsEmptyArrayOnFailure(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $commitApi
            ->expects(self::once())
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn('snafu');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all($repository, [
            'sha' => $sha,
        ]);

        self::assertInstanceOf(Resource\Range::class, $range);
        self::assertCount(0, $range->commits());
    }

    public function testAllSetsParamsPerPageTo250(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects(self::once())
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('per_page', 250)
            )
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all($repository, [
            'sha' => $sha,
        ]);
    }

    public function testAllStillAllowsSettingPerPage(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;
        $perPage = $faker->numberBetween(1);

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects(self::once())
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('per_page', $perPage)
            )
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all($repository, [
            'sha' => $sha,
            'per_page' => $perPage,
        ]);
    }

    public function testAllReturnsRange(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects(self::once())
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all($repository, [
            'sha' => $sha,
        ]);

        self::assertInstanceOf(Resource\Range::class, $range);

        $commits = $range->commits();

        self::assertCount(\count($expectedItems), $commits);

        \array_walk($commits, static function (Resource\CommitInterface $commit) use (&$expectedItems): void {
            /*
             * API returns commits in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            self::assertSame($expectedItem['sha'], $commit->sha());
            self::assertSame($expectedItem['commit']['message'], $commit->message());
        });
    }

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $endReference = $startReference;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $commitApi
            ->expects(self::never())
            ->method(self::anything());

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertEmpty($range->commits());
        self::assertEmpty($range->pullRequests());
    }

    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $commitApi
            ->expects(self::at(0))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($startReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects(self::never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertEmpty($range->commits());
        self::assertEmpty($range->pullRequests());
    }

    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $commitApi
            ->expects(self::at(0))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($startReference)
            )
            ->willReturn($this->commitItem());

        $commitApi
            ->expects(self::at(1))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($endReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects(self::never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertEmpty($range->commits());
        self::assertEmpty($range->pullRequests());
    }

    public function testItemsFetchesCommitsUsingShaFromEndCommit(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $startCommit = $this->commitItem();

        $commitApi
            ->expects(self::at(0))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $endCommit = $this->commitItem();

        $commitApi
            ->expects(self::at(1))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($endReference)
            )
            ->willReturn($endCommit);

        $commitApi
            ->expects(self::once())
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $endCommit['sha'])
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesCommitsIfEndReferenceIsNotGiven(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $startCommit = $this->commitItem();

        $commitApi
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $commitApi
            ->expects(self::once())
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayNotHasKey('sha')
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $repository,
            $startReference
        );
    }

    public function testItemsReturnsRangeOfCommitsFromEndToStartExcludingStart(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $startCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects(self::at(0))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $endCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects(self::at(1))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($endReference)
            )
            ->willReturn($endCommit);

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
            ->expects(self::once())
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $endCommit['sha'])
            )
            ->willReturn($this->reverse($segment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $commits = $range->commits();

        self::assertCount(\count($expectedItems), $commits);

        \array_walk($commits, static function ($commit) use (&$expectedItems): void {
            /*
             * API returns items in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            self::assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            self::assertSame($expectedItem['sha'], $commit->sha());
            self::assertSame($expectedItem['commit']['message'], $commit->message());
        });
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createMock(Api\Repository\Commits::class);

        $startCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects(self::at(0))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $endCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects(self::at(1))
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($endReference)
            )
            ->willReturn($endCommit);

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
            ->expects(self::at(2))
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $endCommit['sha'])
            )
            ->willReturn($this->reverse($firstSegment));

        $commitApi
            ->expects(self::at(3))
            ->method('all')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $firstCommitFromFirstSegment['sha'])
            )
            ->willReturn($this->reverse($secondSegment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $commits = $range->commits();

        self::assertCount(\count($expectedItems), $commits);

        \array_walk($commits, static function ($commit) use (&$expectedItems): void {
            /*
             * API returns items in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            self::assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            self::assertSame($expectedItem['sha'], $commit->sha());
            self::assertSame($expectedItem['commit']['message'], $commit->message());
        });
    }

    private function commitItem(string $sha = null, string $message = null): array
    {
        $faker = $this->faker();

        return [
            'sha' => $sha ?: $faker->unique()->sha1,
            'commit' => [
                'message' => $message ?: $faker->unique()->sentence(),
            ],
        ];
    }

    /**
     * @param int $count
     *
     * @return array
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
     * The GitHub API returns commits in reverse order!
     *
     * @param array $commits
     *
     * @return array
     */
    private function reverse(array $commits): array
    {
        return \array_reverse($commits);
    }

    private function arrayHasKeyAndValue(string $key, $value): Framework\Constraint\Callback
    {
        return self::callback(static function ($array) use ($key, $value) {
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
        return self::callback(static function ($array) use ($key) {
            if (\is_array($array)
                && !\array_key_exists($key, $array)
            ) {
                return true;
            }

            return false;
        });
    }
}
