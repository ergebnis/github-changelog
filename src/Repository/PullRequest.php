<?php

namespace Localheinz\ChangeLog\Repository;

use Github\Api;
use Localheinz\ChangeLog\Entity;

class PullRequest
{
    /**
     * @var Api\PullRequest
     */
    private $api;

    /**
     * @param Api\PullRequest $api
     */
    public function __construct(Api\PullRequest $api)
    {
        $this->api = $api;
    }

    /**
     * @param string $vendor
     * @param string $package
     * @param string $id
     * @return Entity\PullRequest|null
     */
    public function show($vendor, $package, $id)
    {
        $response = $this->api->show(
            $vendor,
            $package,
            $id
        );

        if (!is_array($response)) {
            return null;
        }

        return new Entity\PullRequest(
            $response['number'],
            $response['title']
        );
    }
}
