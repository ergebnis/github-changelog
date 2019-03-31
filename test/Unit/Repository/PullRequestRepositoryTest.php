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
 *
 * @covers \Localheinz\GitHub\ChangeLog\Repository\PullRequestRepository
 */
final class PullRequestRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPullRequestRepositoryInterface(): void
    {
        $this->assertClassImplementsInterface(Repository\PullRequestRepositoryInterface::class, Repository\PullRequestRepository::class);
    }

    public function testShowReturnsPullRequestEntityWithNumberTitleAndAuthorOnSuccess(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $api = $this->createMock(Api\PullRequest::class);

        $expectedItem = $this->pullRequestItem();

        $api
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo((string) $expectedItem['number'])
            )
            ->willReturn($expectedItem);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->createMock(Repository\CommitRepositoryInterface::class)
        );

        $pullRequest = $pullRequestRepository->show(
            $repository,
            $expectedItem['number']
        );

        self::assertSame($expectedItem['number'], $pullRequest->number());
        self::assertSame($expectedItem['title'], $pullRequest->title());
        self::assertSame($expectedItem['user']['login'], $pullRequest->author()->login());
    }

    public function testShowThrowsPullRequestNotFoundOnFailure(): void
    {
        $faker = $this->faker();

        $number = $faker->numberBetween(1);

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $api = $this->createMock(Api\PullRequest::class);

        $api
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo((string) $number)
            )
            ->willReturn('snafu');

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->createMock(Repository\CommitRepositoryInterface::class)
        );

        $this->expectException(Exception\PullRequestNotFound::class);
        $this->expectExceptionMessage(\sprintf(
            'Could not find pull request "%d" in "%s".',
            $number,
            $repository
        ));

        $pullRequestRepository->show(
            $repository,
            $number
        );
    }

    public function testItemsDoesNotRequireAnEndReference(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $commitRepository = $this->createMock(Repository\CommitRepositoryInterface::class);

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('commits')
            ->willReturn([]);

        $commitRepository
            ->expects(self::once())
            ->method('items')
            ->with(
                self::identicalTo($repository),
                self::identicalTo($startReference),
                self::identicalTo(null)
            )
            ->willReturn($range);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $this->createMock(Api\PullRequest::class),
            $commitRepository
        );

        $pullRequestRepository->items(
            $repository,
            $startReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoCommitsWereFound(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('commits')
            ->willReturn([]);

        $range
            ->expects(self::never())
            ->method('withPullRequest');

        $commitRepository = $this->createMock(Repository\CommitRepositoryInterface::class);

        $commitRepository
            ->expects(self::once())
            ->method('items')
            ->with(
                self::identicalTo($repository),
                self::identicalTo($startReference),
                self::identicalTo($endReference)
            )
            ->willReturn($range);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $this->createMock(Api\PullRequest::class),
            $commitRepository
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoMergeCommitsWereFound(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createMock(Repository\CommitRepositoryInterface::class);

        $commit = new Resource\Commit(
            $faker->sha1,
            'I am not a merge commit'
        );

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('commits')
            ->willReturn([
                $commit,
            ]);

        $range
            ->expects(self::never())
            ->method('withPullRequest');

        $commitRepository
            ->expects(self::once())
            ->method('items')
            ->with(
                self::identicalTo($repository),
                self::identicalTo($startReference),
                self::identicalTo($endReference)
            )
            ->willReturn($range);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $this->createMock(Api\PullRequest::class),
            $commitRepository
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesPullRequestIfMergeCommitWasFound(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createMock(Repository\CommitRepositoryInterface::class);

        $expectedItem = $this->pullRequestItem();

        $mergeCommit = new Resource\Commit(
            $faker->unique()->sha1,
            \sprintf(
                'Merge pull request #%d from localheinz/fix/directory',
                $expectedItem['number']
            )
        );

        $mutatedRange = $this->createMock(Resource\RangeInterface::class);

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('commits')
            ->willReturn([
                $mergeCommit,
            ]);

        $range
            ->expects(self::once())
            ->method('withPullRequest')
            ->with(self::isInstanceOf(Resource\PullRequestInterface::class))
            ->willReturn($mutatedRange);

        $commitRepository
            ->expects(self::once())
            ->method('items')
            ->with(
                self::identicalTo($repository),
                self::identicalTo($startReference),
                self::identicalTo($endReference)
            )
            ->willReturn($range);

        $api = $this->createMock(Api\PullRequest::class);

        $api
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo((string) $expectedItem['number'])
            )
            ->willReturn($expectedItem);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $commitRepository
        );

        $actualRange = $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertSame($mutatedRange, $actualRange);
    }

    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound(): void
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createMock(Repository\CommitRepositoryInterface::class);

        $number = 9000;

        $mergeCommit = new Resource\Commit(
            $faker->sha1,
            \sprintf(
                'Merge pull request #%d from localheinz/fix/directory',
                $number
            )
        );

        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects(self::any())
            ->method('commits')
            ->willReturn([
                $mergeCommit,
            ]);

        $range
            ->expects(self::never())
            ->method('withPullRequest');

        $commitRepository
            ->expects(self::once())
            ->method('items')
            ->with(
                self::identicalTo($repository),
                self::identicalTo($startReference),
                self::identicalTo($endReference)
            )
            ->willReturn($range);

        $pullRequestApi = $this->createMock(Api\PullRequest::class);

        $pullRequestApi
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo((string) $number)
            )
            ->willReturn(null);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $pullRequestApi,
            $commitRepository
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    private function pullRequestItem(): array
    {
        $faker = $this->faker();

        return [
            'number' => $faker->unique()->numberBetween(1),
            'title' => $faker->unique()->sentence(),
            'user' => [
                'login' => $faker->slug(),
            ],
        ];
    }
}
