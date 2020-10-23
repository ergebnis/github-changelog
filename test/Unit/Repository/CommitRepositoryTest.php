<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2020 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Test\Unit\Repository;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Repository;
use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\Test\Util\Helper;
use Github\Api;
use PHPUnit\Framework;
use Prophecy\Argument;
use Prophecy\PhpUnit;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Repository\CommitRepository
 */
final class CommitRepositoryTest extends Framework\TestCase
{
    use Helper;
    use PhpUnit\ProphecyTrait;

    public function testImplementsCommitRepositoryInterface(): void
    {
        self::assertClassImplementsInterface(Repository\CommitRepositoryInterface::class, Repository\CommitRepository::class);
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Exception\ReferenceNotFound
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Exception\ReferenceNotFound
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Exception\ReferenceNotFound
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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
     * @uses \Ergebnis\GitHub\Changelog\Resource\Commit
     * @uses \Ergebnis\GitHub\Changelog\Resource\Range
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
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

        if (null === $sha) {
            $sha = $faker->unique()->sha1;
        }

        if (null === $message) {
            $message = $faker->unique()->sentence();
        }

        return [
            'sha' => $sha,
            'commit' => [
                'message' => $message,
            ],
        ];
    }

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
     */
    private function reverse(array $commits): array
    {
        return \array_reverse($commits);
    }

    /**
     * @param mixed $value
     */
    private function arrayHasKeyAndValue(string $key, $value): Framework\Constraint\Callback
    {
        return self::callback(static function ($array) use ($key, $value): bool {
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
        return self::callback(static function ($array) use ($key): bool {
            if (\is_array($array)
                && !\array_key_exists($key, $array)
            ) {
                return true;
            }

            return false;
        });
    }
}
