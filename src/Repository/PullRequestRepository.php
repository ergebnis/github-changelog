<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Resource;

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
     *
     * @return Resource\PullRequestInterface|null
     */
    public function show($owner, $repository, $id)
    {
        $response = $this->api->show(
            $owner,
            $repository,
            $id
        );

        if (!is_array($response)) {
            return;
        }

        return new Resource\PullRequest(
            $response['number'],
            $response['title']
        );
    }

    /**
     * @param string      $owner
     * @param string      $repository
     * @param string      $startReference
     * @param string|null $endReference
     *
     * @return Resource\PullRequestInterface[] array
     */
    public function items($owner, $repository, $startReference, $endReference = null)
    {
        $commits = $this->commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $pullRequests = [];

        array_walk($commits, function (Resource\CommitInterface $commit) use (&$pullRequests, $owner, $repository) {

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
