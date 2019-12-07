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

use Ergebnis\Test\Util\Helper;
use Github\Api;
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use PHPUnit\Framework;
use Prophecy\Argument;

/**
 * @internal
 *
 * @covers \Localheinz\GitHub\ChangeLog\Repository\CommitRepository
 */
final class CommitRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsCommitRepositoryInterface(): void
    {
        self::assertClassImplementsInterface(Repository\CommitRepositoryInterface::class, Repository\CommitRepository::class);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testShowReturnsCommitEntityWithShaAndMessageOnSuccess(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $expectedItem = $this->commitItem();

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($sha)
            )
            ->shouldBeCalled()
            ->willReturn($expectedItem);

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $commit = $commitRepository->show(
            $repository,
            $sha
        );

        self::assertSame($expectedItem['sha'], $commit->sha());
        self::assertSame($expectedItem['commit']['message'], $commit->message());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Exception\ReferenceNotFound
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testShowThrowsCommitNotFoundOnFailure(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $api = $this->prophesize(Api\Repository\Commits::class);

        $api
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($sha)
            )
            ->shouldBeCalled()
            ->willReturn('failure');

        $commitRepository = new Repository\CommitRepository($api->reveal());

        $this->expectException(Exception\ReferenceNotFound::class);

        $commitRepository->show(
            $repository,
            $sha
        );
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testAllReturnsEmptyArrayOnFailure(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $commitApi
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('sha', $sha)
            )
            ->shouldBeCalled()
            ->willReturn('snafu');

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $range = $commitRepository->all($repository, [
            'sha' => $sha,
        ]);

        self::assertInstanceOf(Resource\Range::class, $range);
        self::assertCount(0, $range->commits());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testAllSetsParamsPerPageTo250(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('per_page', 250)
            )
            ->shouldBeCalled()
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $commitRepository->all($repository, [
            'sha' => $sha,
        ]);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testAllStillAllowsSettingPerPage(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;
        $perPage = $faker->numberBetween(1);

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('per_page', $perPage)
            )
            ->shouldBeCalled()
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $commitRepository->all($repository, [
            'sha' => $sha,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testAllReturnsRange(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('sha', $sha)
            )
            ->shouldBeCalled()
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

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

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $endReference = $startReference;

        $commitRepository = new Repository\CommitRepository($this->prophesize(Api\Repository\Commits::class)->reveal());

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertEmpty($range->commits());
        self::assertEmpty($range->pullRequests());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Exception\ReferenceNotFound
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($startReference)
            )
            ->shouldBeCalled()
            ->willReturn(null);

        $commitApi
            ->all()
            ->shouldNotBeCalled();

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertEmpty($range->commits());
        self::assertEmpty($range->pullRequests());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Exception\ReferenceNotFound
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($startReference)
            )
            ->shouldBeCalled()
            ->willReturn($this->commitItem());

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn(null);

        $commitApi
            ->all()
            ->shouldNotBeCalled();

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertEmpty($range->commits());
        self::assertEmpty($range->pullRequests());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsFetchesCommitsUsingShaFromEndCommit(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $startCommit = $this->commitItem();

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($startReference)
            )
            ->shouldBeCalled()
            ->willReturn($startCommit);

        $endCommit = $this->commitItem();

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($endCommit);

        $commitApi
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('sha', $endCommit['sha'])
            )
            ->shouldBeCalled();

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsFetchesCommitsIfEndReferenceIsNotGiven(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $startCommit = $this->commitItem();

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($startReference)
            )
            ->shouldBeCalled()
            ->willReturn($startCommit);

        $commitApi
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::not(Argument::withKey('sha'))
            )
            ->shouldBeCalled();

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

        $commitRepository->items(
            $repository,
            $startReference
        );
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsReturnsRangeOfCommitsFromEndToStartExcludingStart(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $startCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($startReference)
            )
            ->shouldBeCalled()
            ->willReturn($startCommit);

        $endCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
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
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('sha', $endCommit['sha'])
            )
            ->shouldBeCalled()
            ->willReturn($this->reverse($segment));

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

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

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Range
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->prophesize(Api\Repository\Commits::class);

        $startCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($startReference)
            )
            ->shouldBeCalled()
            ->willReturn($startCommit);

        $endCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
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
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('sha', $endCommit['sha'])
            )
            ->shouldBeCalled()
            ->willReturn($this->reverse($firstSegment));

        $commitApi
            ->all(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::withEntry('sha', $firstCommitFromFirstSegment['sha'])
            )
            ->shouldBeCalled()
            ->willReturn($this->reverse($secondSegment));

        $commitRepository = new Repository\CommitRepository($commitApi->reveal());

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

    private function commitItem(?string $sha = null, ?string $message = null): array
    {
        $faker = self::faker();

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

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Framework\Constraint\Callback
     */
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
