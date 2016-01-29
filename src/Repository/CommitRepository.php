<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Entity;

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
     * @param string|null $endReference
     *
     * @return Entity\Commit[]
     */
    public function items($owner, $repository, $startReference, $endReference = null)
    {
        if ($startReference === $endReference) {
            return [];
        }

        $start = $this->show(
            $owner,
            $repository,
            $startReference
        );

        if (null === $start) {
            return [];
        }

        $params = [];

        if ($endReference !== null) {
            $end = $this->show(
                $owner,
                $repository,
                $endReference
            );

            if (null === $end) {
                return [];
            }

            $params = [
                'sha' => $end->sha(),
            ];
        }

        $commits = $this->all($owner, $repository, $params);

        $range = [];

        $tail = null;

        while (count($commits)) {
            /* @var Entity\Commit $commit */
            $commit = array_shift($commits);

            if ($tail instanceof Entity\Commit && $commit->sha() === $tail->sha()) {
                continue;
            }

            if ($commit->sha() === $start->sha()) {
                break;
            }

            // API returns items in reverse order!
            array_unshift($range, $commit);

            if (!count($commits)) {
                $tail = $commit;
                $commits = $this->all($owner, $repository, [
                    'sha' => $tail->sha(),
                ]);
            }
        }

        return $range;
    }

    /**
     * @param string $owner
     * @param string $repository
     * @param string $sha
     *
     * @return Entity\Commit|null
     */
    public function show($owner, $repository, $sha)
    {
        $response = $this->api->show(
            $owner,
            $repository,
            $sha
        );

        if (!is_array($response)) {
            return;
        }

        return new Entity\Commit(
            $response['sha'],
            $response['commit']['message']
        );
    }

    /**
     * @param string $owner
     * @param string $repository
     * @param array  $params
     *
     * @return Entity\Commit[]
     */
    public function all($owner, $repository, array $params = [])
    {
        if (!array_key_exists('per_page', $params)) {
            $params['per_page'] = 250;
        }

        $response = $this->api->all(
            $owner,
            $repository,
            $params
        );

        if (!is_array($response)) {
            return [];
        }

        $commits = [];

        array_walk($response, function ($data) use (&$commits) {
            $commit = new Entity\Commit(
                $data['sha'],
                $data['commit']['message']
            );

            array_push($commits, $commit);
        });

        return $commits;
    }
}
