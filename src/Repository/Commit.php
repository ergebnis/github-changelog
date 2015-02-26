<?php

namespace Localheinz\ChangeLog\Repository;

class Commit
{
    /**
     * @param string $user
     * @param string $repository
     * @param string $end
     */
    public function commit($user, $repository, $end)
    {
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
