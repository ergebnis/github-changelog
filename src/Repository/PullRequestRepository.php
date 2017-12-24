<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;

final class PullRequestRepository implements PullRequestRepositoryInterface
{
    /**
     * @var Api\PullRequest
     */
    private $api;

    /**
     * @var CommitRepositoryInterface
     */
    private $commitRepository;

    public function __construct(Api\PullRequest $api, CommitRepositoryInterface $commitRepository)
    {
        $this->api = $api;
        $this->commitRepository = $commitRepository;
    }

    public function show(Resource\RepositoryInterface $repository, int $number): Resource\PullRequestInterface
    {
        $response = $this->api->show(
            $repository->owner(),
            $repository->name(),
            $number
        );

        if (!\is_array($response)) {
            throw Exception\PullRequestNotFound::fromRepositoryAndNumber(
                $repository,
                $number
            );
        }

        return new Resource\PullRequest(
            $response['number'],
            $response['title'],
            new Resource\User($response['user']['login'])
        );
    }

    public function items(Resource\RepositoryInterface $repository, string $startReference, string $endReference = null): Resource\RangeInterface
    {
        $range = $this->commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $commits = $range->commits();

        \array_walk($commits, function (Resource\CommitInterface $commit) use (&$range, $repository) {
            if (0 === \preg_match('/^Merge pull request #(?P<number>\d+)/', $commit->message(), $matches)) {
                return;
            }

            $number = (int) $matches['number'];

            try {
                $pullRequest = $this->show(
                    $repository,
                    $number
                );
            } catch (Exception\PullRequestNotFound $exception) {
                return;
            }

            $range = $range->withPullRequest($pullRequest);
        });

        return $range;
    }
}
