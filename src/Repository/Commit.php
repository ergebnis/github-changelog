<?php

namespace Localheinz\GitHub\ChangeLog\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Entity;

class Commit
{
    /**
     * @var Api\Repository\Commits
     */
    private $api;

    /**
     * @param Api\Repository\Commits $api
     */
    public function __construct(Api\Repository\Commits $api)
    {
        $this->api = $api;
    }

    /**
     * @param string $vendor
     * @param string $package
     * @param string $startReference
     * @param string $endReference
     * @return Entity\Commit[]
     */
    public function items($vendor, $package, $startReference, $endReference)
    {
        if ($startReference === $endReference) {
            return [];
        }

        $start = $this->show(
            $vendor,
            $package,
            $startReference
        );

        if (null === $start) {
            return [];
        }

        $end = $this->show(
            $vendor,
            $package,
            $endReference
        );

        if (null === $end) {
            return [];
        }

        $commits = $this->all($vendor, $package, [
            'sha' => $end->sha(),
        ]);

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
                $commits = $this->all($vendor, $package, [
                    'sha' => $tail->sha(),
                ]);
            }
        }

        return $range;
    }

    /**
     * @param string $vendor
     * @param string $package
     * @param string $sha
     * @return Entity\Commit|null
     */
    public function show($vendor, $package, $sha)
    {
        $response = $this->api->show(
            $vendor,
            $package,
            $sha
        );

        if (!is_array($response)) {
            return null;
        }

        return new Entity\Commit(
            $response['sha'],
            $response['commit']['message']
        );
    }

    /**
     * @param string $vendor
     * @param string $package
     * @param array $params
     * @return Entity\Commit[]
     */
    public function all($vendor, $package, array $params = [])
    {
        $response = $this->api->all(
            $vendor,
            $package,
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
