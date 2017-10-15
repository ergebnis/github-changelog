<?php

declare(strict_types=1);

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
     * @param string $name
     * @param string $number
     *
     * @return null|Resource\PullRequestInterface
     */
    public function show($owner, $name, $number)
    {
        $response = $this->api->show(
            $owner,
            $name,
            $number
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
     * @param string      $name
     * @param string      $startReference
     * @param null|string $endReference
     *
     * @return Resource\Range
     */
    public function items($owner, $name, $startReference, $endReference = null)
    {
        $range = $this->commitRepository->items(
            $owner,
            $name,
            $startReference,
            $endReference
        );

        $commits = $range->commits();

        \array_walk($commits, function (Resource\CommitInterface $commit) use (&$range, $owner, $name) {
            if (0 === \preg_match('/^Merge pull request #(?P<number>\d+)/', $commit->message(), $matches)) {
                return;
            }

            $number = $matches['number'];

            $pullRequest = $this->show(
                $owner,
                $name,
                $number
            );

            if (null === $pullRequest) {
                return;
            }

            $range = $range->withPullRequest($pullRequest);
        });

        return $range;
    }
}
