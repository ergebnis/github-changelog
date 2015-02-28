<?php

namespace Localheinz\ChangeLog\Test\Repository;

use Faker;
use Github\Api;
use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;
use Localheinz\ChangeLog\Test\Util\FakerTrait;
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
        $id = '9000';

        $api = $this->pullRequestApi();

        $expected = $this->pullRequestData();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($package),
                $this->equalTo($id)
            )
            ->willReturn($this->responseFromPullRequest($expected))
        ;

        $pullRequestRepository = new Repository\PullRequest($api);

        $pullRequest = $pullRequestRepository->show(
            $vendor,
            $package,
            $id
        );

        $this->assertInstanceOf(Entity\PullRequest::class, $pullRequest);

        $this->assertSame($expected->id, $pullRequest->id());
        $this->assertSame($expected->title, $pullRequest->title());
    }

    public function testShowReturnsNullOnFailure()
    {
        $vendor = 'foo';
        $package = 'bar';
        $id = '9000';

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

        $pullRequestRepository = new Repository\PullRequest($api);

        $pullRequest = $pullRequestRepository->show(
            $vendor,
            $package,
            $id
        );

        $this->assertNull($pullRequest);
    }

    /**
     * @param string $id
     * @param string $title
     * @return stdClass
     */
    private function pullRequestData($id = null, $title = null)
    {
        $data = new stdClass();

        $data->id = $id ?: $this->faker()->unique()->randomNumber;
        $data->title = $title ?: $this->faker()->unique()->sentence();

        return $data;
    }

    /**
     * @param stdClass $pullRequest
     * @return array
     */
    private function responseFromPullRequest(stdClass $pullRequest)
    {
        $template = file_get_contents(__DIR__ . '/_response/pull-request.json');

        $body = str_replace(
            [
                '%id%',
                '%title%',
            ],
            [
                $pullRequest->id,
                $pullRequest->title,
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
    private function pullRequestApi()
    {
        return $this->getMockBuilder(Api\PullRequest::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
