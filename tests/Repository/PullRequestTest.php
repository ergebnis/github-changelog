<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use FakerTrait;

    public function testShowReturnsPullRequestEntityWithIdAndTitleOnSuccess()
    {
        $vendor = 'foo';
        $package = 'bar';

        $api = $this->api();

        $id = $this->faker()->unique()->randomNumber;
        $title = $this->faker()->unique()->sentence();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($id)
            )
            ->willReturn($this->response([
                'id' => $id,
                'title' => $title,
            ]))
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

        $this->assertInstanceOf(Entity\PullRequest::class, $pullRequest);

        $this->assertSame($id, $pullRequest->id());
        $this->assertSame($title, $pullRequest->title());
    }

    public function testShowReturnsNullOnFailure()
    {
        $vendor = 'foo';
        $package = 'bar';

        $id = $this->faker()->unique()->randomNumber;

        $api = $this->api();

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
            $this->api(),
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
            $this->api(),
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

        $id = 9000;
        $title = 'Fix: Directory name';

        $mergeCommit = new Entity\Commit(
            $this->faker()->unique()->sha1,
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

        $api = $this->api();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($id)
            )
            ->willReturn($this->response([
                'id' => $id,
                'title' => $title,
            ]))
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
        $this->assertSame($id, $pullRequest->id());
        $this->assertSame($title, $pullRequest->title());
    }

    public function testItemsHandlesMergeCommitWherePullRequestWasNotFound()
    {
        $vendor = 'foo';
        $package = 'bar';
        $startReference = 'ad77125';
        $endReference = '7fc1c4f';

        $commitRepository = $this->commitRepository();

        $id = 9000;

        $mergeCommit = $this->commit(
            null,
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

        $api = $this->api();

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
     * @param array $data
     * @return mixed
     */
    private function response(array $data = [])
    {
        $template = file_get_contents(__DIR__ . '/_response/pull-request.json');

        $body = str_replace(
            [
                '%id%',
                '%title%',
            ],
            [
                $data['id'],
                $data['title'],
            ],
            $template
        );

        return json_decode(
            $body,
            true
        );
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function api()
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
     * @param string $sha
     * @param string $message
     * @return Entity\Commit
     */
    private function commit($sha = null, $message = null)
    {
        $sha = $sha ?: $this->faker()->unique()->sha1;
        $message = $message ?: $this->faker()->unique()->sentence();

        return new Entity\Commit(
            $sha,
            $message
        );
    }
}
