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
     * @var Commit
     */
    private $commitRepository;

    /**
     * @param Api\PullRequest $api
     */
    public function __construct(Api\PullRequest $api, Commit $commitRepository)
    {
        $this->api = $api;
        $this->commitRepository = $commitRepository;
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

    /**
     * @param string $vendor
     * @param string $package
     * @param string $startReference
     * @param string $endReference
     * @return Entity\PullRequest[] array
     */
    public function items($vendor, $package, $startReference, $endReference)
    {
        $commits = $this->commitRepository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $pullRequests = [];

        array_walk($commits, function (Entity\Commit $commit) use (&$pullRequests, $vendor, $package) {

            if (0 === preg_match('/^Merge pull request #(?P<id>\d+)/', $commit->message(), $matches)) {
                return;
            }

            $id = $matches['id'];

            $pullRequest = $this->show(
                $vendor,
                $package,
                $id
            );

            if (null === $pullRequest) {
                return;
            }

            array_push($pullRequests, $pullRequest);
        });

        return $pullRequests;
    }
}
