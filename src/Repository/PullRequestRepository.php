<?php

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
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
     * @return null|Resource\PullRequestInterface
     */
    public function show($owner, $repository, $id)
    {
        $response = $this->api->show(
            $owner,
            $repository,
            $id
        );

        if (!\is_array($response)) {
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
     * @param null|string $endReference
     *
     * @return Resource\Range
     */
    public function items($owner, $repository, $startReference, $endReference = null)
    {
        $range = $this->commitRepository->items(
            $owner,
            $repository,
            $startReference,
            $endReference
        );

        $commits = $range->commits();

        \array_walk($commits, function (Resource\CommitInterface $commit) use (&$range, $owner, $repository) {
            if (0 === \preg_match('/^Merge pull request #(?P<id>\d+)/', $commit->message(), $matches)) {
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

            $range = $range->withPullRequest($pullRequest);
        });

        return $range;
    }
}
