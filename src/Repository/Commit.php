<?php

namespace Localheinz\ChangeLog\Repository;

use Github\Api;
use Localheinz\ChangeLog\Entity;

class Commit
{
    /**
     * @var Api\Repository\Commits
     */
    private $commitApi;

    /**
     * @param Api\Repository\Commits $commitApi
     */
    public function __construct(Api\Repository\Commits $commitApi)
    {
        $this->commitApi = $commitApi;
    }

    /**
     * @param string $vendor
     * @param string $package
     * @param string $sha
     * @return Entity\Commit|null
     */
    public function show($vendor, $package, $sha)
    {
        $response = $this->commitApi->show(
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
        $response = $this->commitApi->all(
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
