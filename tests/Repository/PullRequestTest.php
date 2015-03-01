<?php

namespace Localheinz\GitHub\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use stdClass;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use FakerTrait;

    public function testShowReturnsPullRequestEntityWithIdAndTitleOnSuccess()
    {
        $vendor = 'foo';
        $package = 'bar';

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

        $pullRequestRepository = new Repository\PullRequest(
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
        $vendor = 'foo';
        $package = 'bar';

        $id = $this->faker()->unique()->randomNumber;

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

        $pullRequestRepository = new Repository\PullRequest(
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

    public function testItemsReturnsEmptyArrayIfNoCommitsWereFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

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

        $repository = new Repository\PullRequest(
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
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $commit = new Entity\Commit(
            $this->faker()->unique()->sha1,
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

        $repository = new Repository\PullRequest(
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
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

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

        $repository = new Repository\PullRequest(
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
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $id = 9000;

        $mergeCommit = new Entity\Commit(
            'foo',
            sprintf(
                'Merge pull request #%s from localheinz/fix/directory',
                $id
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
                $this->equalTo($id)
            )
            ->willReturn(null)
        ;

        $repository = new Repository\PullRequest(
            $api,
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

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function pullRequestApi()
    {
        return $this->getMockBuilder(Api\PullRequest::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function commitRepository()
    {
        return $this->getMockBuilder(Repository\Commit::class)
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

        $item->id = $this->faker()->unique()->randomNumber;
        $item->title = $this->faker()->unique()->sentence();

        return $item;
    }

    /**
     * @param stdClass $item
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
