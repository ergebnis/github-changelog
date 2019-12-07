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
 * @covers \Localheinz\GitHub\ChangeLog\Repository\PullRequestRepository
 */
final class PullRequestRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPullRequestRepositoryInterface(): void
    {
        self::assertClassImplementsInterface(Repository\PullRequestRepositoryInterface::class, Repository\PullRequestRepository::class);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\PullRequest
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     * @uses \Localheinz\GitHub\ChangeLog\Resource\User
     */
    public function testShowReturnsPullRequestEntityWithNumberTitleAndAuthorOnSuccess(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $api = $this->prophesize(Api\PullRequest::class);

        $expectedItem = $this->pullRequestItem();

        $api
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($expectedItem['number'])
            )
            ->shouldBeCalled()
            ->willReturn($expectedItem);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api->reveal(),
            $this->prophesize(Repository\CommitRepositoryInterface::class)->reveal()
        );

        $pullRequest = $pullRequestRepository->show(
            $repository,
            $expectedItem['number']
        );

        self::assertSame($expectedItem['number'], $pullRequest->number());
        self::assertSame($expectedItem['title'], $pullRequest->title());
        self::assertSame($expectedItem['user']['login'], $pullRequest->author()->login());
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Exception\PullRequestNotFound
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testShowThrowsPullRequestNotFoundOnFailure(): void
    {
        $faker = self::faker();

        $number = $faker->numberBetween(1);

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $api = $this->prophesize(Api\PullRequest::class);

        $api
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($number)
            )
            ->shouldBeCalled()
            ->willReturn('snafu');

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api->reveal(),
            $this->prophesize(Repository\CommitRepositoryInterface::class)->reveal()
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

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsDoesNotRequireAnEndReference(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $commitRepository = $this->prophesize(Repository\CommitRepositoryInterface::class);

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->commits()
            ->shouldBeCalled()
            ->willReturn([]);

        $commitRepository
            ->items(
                Argument::is($repository),
                Argument::is($startReference),
                Argument::is(null)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $pullRequestRepository = new Repository\PullRequestRepository(
            $this->prophesize(Api\PullRequest::class)->reveal(),
            $commitRepository->reveal()
        );

        $pullRequestRepository->items(
            $repository,
            $startReference
        );
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsDoesNotTouchRangeIfNoCommitsWereFound(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->commits()
            ->shouldBeCalled()
            ->willReturn([]);

        $range
            ->withPullRequest()
            ->shouldNotBeCalled();

        $commitRepository = $this->prophesize(Repository\CommitRepositoryInterface::class);

        $commitRepository
            ->items(
                Argument::is($repository),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $pullRequestRepository = new Repository\PullRequestRepository(
            $this->prophesize(Api\PullRequest::class)->reveal(),
            $commitRepository->reveal()
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsDoesNotTouchRangeIfNoMergeCommitsWereFound(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->prophesize(Repository\CommitRepositoryInterface::class);

        $commit = new Resource\Commit(
            $faker->sha1,
            'I am not a merge commit'
        );

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->commits()
            ->shouldBeCalled()
            ->willReturn([
                $commit,
            ]);

        $range
            ->withPullRequest()
            ->shouldNotBeCalled();

        $commitRepository
            ->items(
                Argument::is($repository),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $pullRequestRepository = new Repository\PullRequestRepository(
            $this->prophesize(Api\PullRequest::class)->reveal(),
            $commitRepository->reveal()
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\PullRequest
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     * @uses \Localheinz\GitHub\ChangeLog\Resource\User
     */
    public function testItemsFetchesPullRequestIfMergeCommitWasFound(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->prophesize(Repository\CommitRepositoryInterface::class);

        $expectedItem = $this->pullRequestItem();

        $mergeCommit = new Resource\Commit(
            $faker->unique()->sha1,
            \sprintf(
                'Merge pull request #%d from localheinz/fix/directory',
                $expectedItem['number']
            )
        );

        $mutatedRange = $this->prophesize(Resource\RangeInterface::class);

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->commits()
            ->shouldBeCalled()
            ->willReturn([
                $mergeCommit,
            ]);

        $range
            ->withPullRequest(Argument::type(Resource\PullRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($mutatedRange->reveal());

        $commitRepository
            ->items(
                Argument::is($repository),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $api = $this->prophesize(Api\PullRequest::class);

        $api
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($expectedItem['number'])
            )
            ->shouldBeCalled()
            ->willReturn($expectedItem);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api->reveal(),
            $commitRepository->reveal()
        );

        $actualRange = $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        self::assertSame($mutatedRange->reveal(), $actualRange);
    }

    /**
     * @uses \Localheinz\GitHub\ChangeLog\Exception\PullRequestNotFound
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Commit
     * @uses \Localheinz\GitHub\ChangeLog\Resource\PullRequest
     * @uses \Localheinz\GitHub\ChangeLog\Resource\Repository
     */
    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->prophesize(Repository\CommitRepositoryInterface::class);

        $number = 9000;

        $mergeCommit = new Resource\Commit(
            $faker->sha1,
            \sprintf(
                'Merge pull request #%d from localheinz/fix/directory',
                $number
            )
        );

        $range = $this->prophesize(Resource\RangeInterface::class);

        $range
            ->commits()
            ->shouldBeCalled()
            ->willReturn([
                $mergeCommit,
            ]);

        $range
            ->withPullRequest()
            ->shouldNotBeCalled();

        $commitRepository
            ->items(
                Argument::is($repository),
                Argument::is($startReference),
                Argument::is($endReference)
            )
            ->shouldBeCalled()
            ->willReturn($range->reveal());

        $pullRequestApi = $this->prophesize(Api\PullRequest::class);

        $pullRequestApi
            ->show(
                Argument::is($repository->owner()),
                Argument::is($repository->name()),
                Argument::is($number)
            )
            ->shouldBeCalled()
            ->willReturn(null);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $pullRequestApi->reveal(),
            $commitRepository->reveal()
        );

        $pullRequestRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    private function pullRequestItem(): array
    {
        $faker = self::faker();

        return [
            'number' => $faker->unique()->numberBetween(1),
            'title' => $faker->unique()->sentence(),
            'user' => [
                'login' => $faker->slug(),
            ],
        ];
    }
}
