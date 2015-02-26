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
     * @param string $user
     * @param string $repository
     * @param string $end
     * @return Entity\Commit;
     */
    public function commit($user, $repository, $end)
    {
        $response = $this->commitApi->show($user, $repository, $end);

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
