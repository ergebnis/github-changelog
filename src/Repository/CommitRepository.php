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
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;

class CommitRepository
{
    /**
     * @var Api\Repository\Commits
     */
    private $api;

    public function __construct(Api\Repository\Commits $api)
    {
        $this->api = $api;
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
        if ($startReference === $endReference) {
            return new Resource\Range();
        }

        try {
            $start = $this->show(
                $owner,
                $repository,
                $startReference
            );
        } catch (Exception\ReferenceNotFound $exception) {
            return new Resource\Range();
        }

        $params = [];

        if (null !== $endReference) {
            try {
                $end = $this->show(
                    $owner,
                    $repository,
                    $endReference
                );
            } catch (Exception\ReferenceNotFound $exception) {
                return new Resource\Range();
            }

            $params = [
                'sha' => $end->sha(),
            ];
        }

        $commits = $this->all($owner, $repository, $params)->commits();

        $range = new Resource\Range();

        $tail = null;

        while (\count($commits)) {
            /* @var Resource\CommitInterface $commit */
            $commit = \array_shift($commits);

            if ($tail instanceof Resource\CommitInterface && $commit->equals($tail)) {
                continue;
            }

            if ($commit->equals($start)) {
                break;
            }

            $range = $range->withCommit($commit);

            if (!\count($commits)) {
                $tail = $commit;
                $params = [
                    'sha' => $tail->sha(),
                ];

                $commits = $this->all($owner, $repository, $params)->commits();
            }
        }

        return $range;
    }

    /**
     * @param string $owner
     * @param string $repository
     * @param string $sha
     *
     * @throws Exception\ReferenceNotFound
     *
     * @return Resource\CommitInterface
     */
    public function show($owner, $repository, $sha)
    {
        $response = $this->api->show(
            $owner,
            $repository,
            $sha
        );

        if (!\is_array($response)) {
            throw Exception\ReferenceNotFound::fromOwnerRepositoryAndReference(
                $owner,
                $repository,
                $sha
            );
        }

        return new Resource\Commit(
            $response['sha'],
            $response['commit']['message']
        );
    }

    /**
     * @param string $owner
     * @param string $repository
     * @param array  $params
     *
     * @return Resource\Range
     */
    public function all($owner, $repository, array $params = [])
    {
        $range = new Resource\Range();

        if (!\array_key_exists('per_page', $params)) {
            $params['per_page'] = 250;
        }

        $response = $this->api->all(
            $owner,
            $repository,
            $params
        );

        if (!\is_array($response)) {
            return $range;
        }

        \array_walk($response, function ($data) use (&$range) {
            $commit = new Resource\Commit(
                $data['sha'],
                $data['commit']['message']
            );

            $range = $range->withCommit($commit);
        });

        return $range;
    }
}
