<?php

namespace Localheinz\ChangeLog\Repository;

use Github\Api;
use Localheinz\ChangeLog\Entity;

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
            'sha' => $start->sha(),
        ]);

        if (!is_array($commits)) {
            return [];
        }

        $range = [];

        $currentStart = $start;

        while (count($commits)) {
            /* @var Entity\Commit $commit */
            $commit = array_shift($commits);

            if ($commit->sha() === $currentStart->sha()) {
                continue;
            }

            array_push($range, $commit);

            if ($commit->sha() === $end->sha()) {
                break;
            }

            if (!count($commits)) {
                $currentStart = $commit;

                $commits = $this->all($vendor, $package, [
                    'sha' => $currentStart->sha(),
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
