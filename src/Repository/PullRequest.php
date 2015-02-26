<?php

namespace Localheinz\ChangeLog\Repository;

class PullRequest
{
    /**
     * @var Commit
     */
    private $commitRepository;

    /**
     * @param Commit $commitRepository
     */
    public function __construct(Commit $commitRepository)
    {
        $this->commitRepository = $commitRepository;
    }

    /**
     * @param string $user
     * @param string $repository
     * @param string $start
     * @param string $end
     * @return array
     */
    public function pullRequests($user, $repository, $start, $end)
    {
        $this->commitRepository->commit(
            $user,
            $repository,
            $start
        );

        $this->commitRepository->commit(
            $user,
            $repository,
            $end
        );

        return [];
    }
}
