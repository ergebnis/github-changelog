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
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class PullRequestRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPullRequestRepositoryInterface()
    {
        $this->assertClassImplementsInterface(Repository\PullRequestRepositoryInterface::class, Repository\PullRequestRepository::class);
    }

    public function testShowReturnsPullRequestEntityWithIdAndTitleOnSuccess()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();

        $api = $this->createPullRequestApiMock();

        $expectedItem = $this->pullRequestItem();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($expectedItem->number)
            )
            ->willReturn($this->response($expectedItem));

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->createCommitRepositoryMock()
        );

        $pullRequest = $pullRequestRepository->show(
            $owner,
            $name,
            $expectedItem->number
        );

        $this->assertInstanceOf(Resource\PullRequestInterface::class, $pullRequest);

        $this->assertSame($expectedItem->number, $pullRequest->number());
        $this->assertSame($expectedItem->title, $pullRequest->title());
    }

    public function testShowThrowsPullRequestNotFoundOnFailure()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $number = $faker->randomNumber();

        $api = $this->createPullRequestApiMock();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($number)
            )
            ->willReturn('snafu');

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->createCommitRepositoryMock()
        );

        $this->expectException(Exception\PullRequestNotFound::class);
        $this->expectExceptionMessage(\sprintf(
            'Could not find pull request "%d" in "%s/%s".',
            $number,
            $owner,
            $name
        ));

        $pullRequestRepository->show(
            $owner,
            $name,
            $number
        );
    }

    public function testItemsDoesNotRequireAnEndReference()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;

        $commitRepository = $this->createCommitRepositoryMock();

        $range = $this->createRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([]);

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference),
                $this->identicalTo(null)
            )
            ->willReturn($range);

        $repository = new Repository\PullRequestRepository(
            $this->createPullRequestApiMock(),
            $commitRepository
        );

        $repository->items(
            $owner,
            $name,
            $startReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoCommitsWereFound()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->createRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([]);

        $range
            ->expects($this->never())
            ->method('withPullRequest');

        $commitRepository = $this->createCommitRepositoryMock();

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference),
                $this->identicalTo($endReference)
            )
            ->willReturn($range);

        $repository = new Repository\PullRequestRepository(
            $this->createPullRequestApiMock(),
            $commitRepository
        );

        $repository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoMergeCommitsWereFound()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createCommitRepositoryMock();

        $commit = new Resource\Commit(
            $faker->sha1,
            'I am not a merge commit'
        );

        $range = $this->createRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([
                $commit,
            ]);

        $range
            ->expects($this->never())
            ->method('withPullRequest');

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference),
                $this->identicalTo($endReference)
            )
            ->willReturn($range);

        $repository = new Repository\PullRequestRepository(
            $this->createPullRequestApiMock(),
            $commitRepository
        );

        $repository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesPullRequestIfMergeCommitWasFound()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->createCommitRepositoryMock();

        $expectedItem = $this->pullRequestItem();

        $mergeCommit = new Resource\Commit(
            $faker->unique()->sha1,
            \sprintf(
                'Merge pull request #%d from localheinz/fix/directory',
                $expectedItem->number
            )
        );

        $mutatedRange = $this->createRangeMock();

        $range = $this->createRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([
                $mergeCommit,
            ]);

        $range
            ->expects($this->once())
            ->method('withPullRequest')
            ->with($this->isInstanceOf(Resource\PullRequestInterface::class))
            ->willReturn($mutatedRange);

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference),
                $this->identicalTo($endReference)
            )
            ->willReturn($range);

        $api = $this->createPullRequestApiMock();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($expectedItem->number)
            )
            ->willReturn($this->response($expectedItem));

        $repository = new Repository\PullRequestRepository(
            $api,
            $commitRepository
        );

        $actualRange = $repository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );

        $this->assertSame($mutatedRange, $actualRange);
    }

    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $name = $faker->slug();
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
            ->expects($this->any())
            ->method('commits')
            ->willReturn([
                $mergeCommit,
            ]);

        $range
            ->expects($this->never())
            ->method('withPullRequest');

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($startReference),
                $this->identicalTo($endReference)
            )
            ->willReturn($range);

        $pullRequestApi = $this->createPullRequestApiMock();

        $pullRequestApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($owner),
                $this->identicalTo($name),
                $this->identicalTo($number)
            )
            ->willReturn(null);

        $pullRequestRepository = new Repository\PullRequestRepository(
            $pullRequestApi,
            $commitRepository
        );

        $pullRequestRepository->items(
            $owner,
            $name,
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

    private function pullRequestItem(): \stdClass
    {
        $faker = $this->faker();

        $item = new \stdClass();

        $item->number = $faker->unique()->randomNumber();
        $item->title = $faker->unique()->sentence();

        return $item;
    }

    private function response(\stdClass $item): array
    {
        $template = \file_get_contents(__DIR__ . '/_response/pull-request.json');

        $body = \str_replace(
            [
                '%number%',
                '%title%',
            ],
            [
                $item->number,
                $item->title,
            ],
            $template
        );

        return \json_decode(
            $body,
            true
        );
    }
}
