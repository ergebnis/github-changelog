<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use stdClass;

class PullRequestRepositoryTest extends PHPUnit_Framework_TestCase
{
    use FakerTrait;

    public function testShowReturnsPullRequestEntityWithIdAndTitleOnSuccess()
    {
        $faker = $this->faker();

        $vendor = $faker->userName;
        $package = $faker->slug();

        $api = $this->pullRequestApi();

        $expectedItem = $this->pullRequestItem();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($expectedItem->id)
            )
            ->willReturn($this->response($expectedItem))
        ;

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->commitRepository()
        );

        $pullRequest = $pullRequestRepository->show(
            $vendor,
            $package,
            $expectedItem->id
        );

        $this->assertInstanceOf(Entity\PullRequest::class, $pullRequest);

        $this->assertSame($expectedItem->id, $pullRequest->id());
        $this->assertSame($expectedItem->title, $pullRequest->title());
    }

    public function testShowReturnsNullOnFailure()
    {
        $faker = $this->faker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $id = $faker->randomNumber();

        $api = $this->pullRequestApi();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($id)
            )
            ->willReturn('snafu')
        ;

        $pullRequestRepository = new Repository\PullRequestRepository(
            $api,
            $this->commitRepository()
        );

        $pullRequest = $pullRequestRepository->show(
            $vendor,
            $package,
            $id
        );

        $this->assertNull($pullRequest);
    }

    public function testItemsDoesNotRequireAnEndReference()
    {
        $faker = $this->faker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo(null)
            )
            ->willReturn([])
        ;

        $repository = new Repository\PullRequestRepository(
            $this->pullRequestApi(),
            $commitRepository
        );

        $pullRequests = $repository->items(
            $vendor,
            $package,
            $startReference
        );

        $this->assertSame([], $pullRequests);
    }

    public function testItemsReturnsEmptyArrayIfNoCommitsWereFound()
    {
        $faker = $this->faker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->commitRepository();

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn([])
        ;

        $repository = new Repository\PullRequestRepository(
            $this->pullRequestApi(),
            $commitRepository
        );

        $pullRequests = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $pullRequests);
    }

    public function testItemsReturnsEmptyArrayIfNoMergeCommitsWereFound()
    {
        $faker = $this->faker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->commitRepository();

        $commit = new Entity\Commit(
            $faker->sha1,
            'I am not a merge commit'
        );

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn([
                $commit,
            ])
        ;

        $repository = new Repository\PullRequestRepository(
            $this->pullRequestApi(),
            $commitRepository
        );

        $pullRequests = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame([], $pullRequests);
    }

    public function testItemsFetchesPullRequestIfMergeCommitWasFound()
    {
        $faker = $this->faker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->commitRepository();

        $expectedItem = $this->pullRequestItem();

        $mergeCommit = new Entity\Commit(
            $this->faker()->unique()->sha1,
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $expectedItem->id
            )
        );

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn([
                $mergeCommit,
            ])
        ;

        $api = $this->pullRequestApi();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($expectedItem->id)
            )
            ->willReturn($this->response($expectedItem))
        ;

        $repository = new Repository\PullRequestRepository(
            $api,
            $commitRepository
        );

        $pullRequests = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertInternalType('array', $pullRequests);
        $this->assertCount(1, $pullRequests);

        $pullRequest = array_shift($pullRequests);

        $this->assertInstanceOf(Entity\PullRequest::class, $pullRequest);

        /* @var Entity\PullRequest $pullRequest */
        $this->assertSame($expectedItem->id, $pullRequest->id());
        $this->assertSame($expectedItem->title, $pullRequest->title());
    }

    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound()
    {
        $faker = $this->faker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->commitRepository();

        $id = 9000;

        $mergeCommit = new Entity\Commit(
            $faker->sha1,
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $id
            )
        );

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn([
                $mergeCommit,
            ])
        ;

        $pullRequestApi = $this->pullRequestApi();

        $pullRequestApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($id)
            )
            ->willReturn(null)
        ;

        $pullRequestRepository = new Repository\PullRequestRepository(
            $pullRequestApi,
            $commitRepository
        );

        $pullRequests = $pullRequestRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $this->assertSame([], $pullRequests);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Api\PullRequest
     */
    private function pullRequestApi()
    {
        return $this->getMockBuilder(Api\PullRequest::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Repository\CommitRepository
     */
    private function commitRepository()
    {
        return $this->getMockBuilder(Repository\CommitRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return stdClass
     */
    private function pullRequestItem()
    {
        $item = new stdClass();

        $item->id = $this->faker()->unique()->randomNumber();
        $item->title = $this->faker()->unique()->sentence();

        return $item;
    }

    /**
     * @param stdClass $item
     *
     * @return array
     */
    private function response(stdClass $item)
    {
        $template = file_get_contents(__DIR__ . '/_response/pull-request.json');

        $body = str_replace(
            [
                '%id%',
                '%title%',
            ],
            [
                $item->id,
                $item->title,
            ],
            $template
        );

        return json_decode(
            $body,
            true
        );
    }
}
