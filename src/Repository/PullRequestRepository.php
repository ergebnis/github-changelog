<?php

namespace Localheinz\GitHub\ChangeLog\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Entity;

class PullRequestRepository
{
    /**
     * @var Api\PullRequest
     */
    private $api;

    /**
     * @var CommitRepository
     */
    private $commitRepository;

    public function __construct(Api\PullRequest $api, CommitRepository $commitRepository)
    {
        $this->api = $api;
        $this->commitRepository = $commitRepository;
    }

    /**
     * @param string $owner
     * @param string $repository
     * @param string $id
     * @return Entity\PullRequest|null
     */
    public function show($owner, $repository, $id)
    {
        $response = $this->api->show(
            $owner,
            $repository,
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
     * @param string $owner
     * @param string $repository
     * @param string $startReference
     * @param string $endReference
     * @return Entity\PullRequest[] array
     */
    public function items($owner, $repository, $startReference, $endReference)
    {
        $commits = $this->commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $pullRequests = [];

        array_walk($commits, function (Entity\Commit $commit) use (&$pullRequests, $owner, $repository) {

            if (0 === preg_match('/^Merge pull request #(?P<id>\d+)/', $commit->message(), $matches)) {
                return;
            }

            $id = $matches['id'];

            $pullRequest = $this->show(
                $owner,
                $repository,
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
