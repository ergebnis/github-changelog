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
     * @param string $userName
     * @param string $repository
     * @param string $sha
     * @return Entity\Commit|null
     */
    public function commit($userName, $repository, $sha)
    {
        $response = $this->commitApi->show(
            $userName,
            $repository,
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
     * @param string $user
     * @param string $repository
     * @param string $start
     * @param string $end
     * @return array
     */
    public function commits($user, $repository, $start, $end)
    {
        return [];
    }
}
