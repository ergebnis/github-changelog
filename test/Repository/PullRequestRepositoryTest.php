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
use Refinery29\Test\Util\Faker\GeneratorTrait;
use stdClass;

class PullRequestRepositoryTest extends PHPUnit_Framework_TestCase
{
    use GeneratorTrait;

    public function testShowReturnsPullRequestEntityWithIdAndTitleOnSuccess()
    {
        $faker = $this->getFaker();

        $vendor = $faker->userName;
        $package = $faker->slug();

        $api = $this->getPullRequestApiMock();

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
            $this->getCommitRepositoryMock()
        );

        $pullRequest = $pullRequestRepository->show(
            $vendor,
            $package,
            $expectedItem->id
        );

        $this->assertInstanceOf(Resource\PullRequestInterface::class, $pullRequest);

        $this->assertSame($expectedItem->id, $pullRequest->id());
        $this->assertSame($expectedItem->title, $pullRequest->title());
    }

    public function testShowReturnsNullOnFailure()
    {
        $faker = $this->getFaker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $id = $faker->randomNumber();

        $api = $this->getPullRequestApiMock();

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
            $this->getCommitRepositoryMock()
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
        $faker = $this->getFaker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;

        $commitRepository = $this->getCommitRepositoryMock();

        $range = $this->getRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([])
        ;

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo(null)
            )
            ->willReturn($range)
        ;

        $repository = new Repository\PullRequestRepository(
            $this->getPullRequestApiMock(),
            $commitRepository
        );

        $repository->items(
            $vendor,
            $package,
            $startReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoCommitsWereFound()
    {
        $faker = $this->getFaker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $range = $this->getRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([])
        ;

        $range
            ->expects($this->never())
            ->method('withPullRequest')
        ;

        $commitRepository = $this->getCommitRepositoryMock();

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn($range)
        ;

        $repository = new Repository\PullRequestRepository(
            $this->getPullRequestApiMock(),
            $commitRepository
        );

        $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );
    }

    public function testItemsDoesNotTouchRangeIfNoMergeCommitsWereFound()
    {
        $faker = $this->getFaker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->getCommitRepositoryMock();

        $commit = new Resource\Commit(
            $faker->sha1,
            'I am not a merge commit'
        );

        $range = $this->getRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([
                $commit,
            ])
        ;

        $range
            ->expects($this->never())
            ->method('withPullRequest')
        ;

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn($range)
        ;

        $repository = new Repository\PullRequestRepository(
            $this->getPullRequestApiMock(),
            $commitRepository
        );

        $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesPullRequestIfMergeCommitWasFound()
    {
        $faker = $this->getFaker();

        $vendor = $faker->userName;
        $package = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->getCommitRepositoryMock();

        $expectedItem = $this->pullRequestItem();

        $mergeCommit = new Resource\Commit(
            $this->getFaker()->unique()->sha1,
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $expectedItem->id
            )
        );

        $mutatedRange = $this->getRangeMock();

        $range = $this->getRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([
                $mergeCommit,
            ])
        ;

        $range
            ->expects($this->once())
            ->method('withPullRequest')
            ->with($this->isInstanceOf(Resource\PullRequestInterface::class))
            ->willReturn($mutatedRange)
        ;

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn($range)
        ;

        $api = $this->getPullRequestApiMock();

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

        $actualRange = $repository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $this->assertSame($mutatedRange, $actualRange);
    }

    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitRepository = $this->getCommitRepositoryMock();

        $id = 9000;

        $mergeCommit = new Resource\Commit(
            $faker->sha1,
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $id
            )
        );

        $range = $this->getRangeMock();

        $range
            ->expects($this->any())
            ->method('commits')
            ->willReturn([
                $mergeCommit,
            ])
        ;

        $range
            ->expects($this->never())
            ->method('withPullRequest')
        ;

        $commitRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn($range)
        ;

        $pullRequestApi = $this->getPullRequestApiMock();

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

        $pullRequestRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Api\PullRequest
     */
    private function getPullRequestApiMock()
    {
        return $this->getMockBuilder(Api\PullRequest::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Repository\CommitRepository
     */
    private function getCommitRepositoryMock()
    {
        return $this->getMockBuilder(Repository\CommitRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Resource\RangeInterface
     */
    private function getRangeMock()
    {
        return $this->getMockBuilder(Resource\RangeInterface::class)->getMock();
    }

    /**
     * @return stdClass
     */
    private function pullRequestItem()
    {
        $item = new stdClass();

        $item->id = $this->getFaker()->unique()->randomNumber();
        $item->title = $this->getFaker()->unique()->sentence();

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
