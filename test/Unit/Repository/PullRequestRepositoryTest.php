<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
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
final class PullRequestRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPullRequestRepositoryInterface()
    {
        $this->assertClassImplementsInterface(Repository\PullRequestRepositoryInterface::class, Repository\PullRequestRepository::class);
    }

    public function testShowReturnsPullRequestEntityWithNumberTitleAndAuthorOnSuccess()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $api = $this->createPullRequestApiMock();

        $expectedItem = $this->pullRequestItem();

        $api
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($expectedItem['number'])
            )
            ->willReturn($expectedItem);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->createCommitRepositoryMock()
        );

        $pullRequest = $pullRequestRepository->show(
            $repository,
            $expectedItem['number']
        );

        self::assertInstanceOf(Resource\PullRequestInterface::class, $pullRequest);

        self::assertSame($expectedItem['number'], $pullRequest->number());
        self::assertSame($expectedItem['title'], $pullRequest->title());
        self::assertSame($expectedItem['user']['login'], $pullRequest->author()->login());
    }

    public function testShowThrowsPullRequestNotFoundOnFailure()
    {
        $faker = $this->faker();

        $number = $faker->numberBetween(1);

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $api = $this->createPullRequestApiMock();

        $api
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($number)
            )
            ->willReturn('snafu');

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->createCommitRepositoryMock()
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

    public function testItemsDoesNotRequireAnEndReference()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $commitRepository = $this->createCommitRepositoryMock();

        $range = $this->createRangeMock();

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
            $this->createPullRequestApiMock(),
            $commitRepository
        );

        $pullRequestRepository->items(
            $repository,
            $startReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoCommitsWereFound()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->createRangeMock();

        $range
            ->expects(self::any())
            ->method('commits')
            ->willReturn([]);

        $range
            ->expects(self::never())
            ->method('withPullRequest');

        $commitRepository = $this->createCommitRepositoryMock();

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
            $this->createPullRequestApiMock(),
            $commitRepository
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoMergeCommitsWereFound()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createCommitRepositoryMock();

        $commit = new Resource\Commit(
            $faker->sha1,
            'I am not a merge commit'
        );

        $range = $this->createRangeMock();

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
            $this->createPullRequestApiMock(),
            $commitRepository
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesPullRequestIfMergeCommitWasFound()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createCommitRepositoryMock();

        $expectedItem = $this->pullRequestItem();

        $mergeCommit = new Resource\Commit(
            $faker->unique()->sha1,
            \sprintf(
                'Merge pull request #%d from localheinz/fix/directory',
                $expectedItem['number']
            )
        );

        $mutatedRange = $this->createRangeMock();

        $range = $this->createRangeMock();

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

        $api = $this->createPullRequestApiMock();

        $api
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($expectedItem['number'])
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

    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createCommitRepositoryMock();

        $number = 9000;

        $mergeCommit = new Resource\Commit(
            $faker->sha1,
            \sprintf(
                'Merge pull request #%d from localheinz/fix/directory',
                $number
            )
        );

        $range = $this->createRangeMock();

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

        $pullRequestApi = $this->createPullRequestApiMock();

        $pullRequestApi
            ->expects(self::once())
            ->method('show')
            ->with(
                self::identicalTo($repository->owner()),
                self::identicalTo($repository->name()),
                self::identicalTo($number)
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

    /**
     * @return Api\PullRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPullRequestApiMock(): Api\PullRequest
    {
        return $this->createMock(Api\PullRequest::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Repository\CommitRepositoryInterface
     */
    private function createCommitRepositoryMock(): Repository\CommitRepositoryInterface
    {
        return $this->createMock(Repository\CommitRepositoryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Resource\RangeInterface
     */
    private function createRangeMock(): Resource\RangeInterface
    {
        return $this->createMock(Resource\RangeInterface::class);
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
