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
     * @param string $userName
     * @param string $repository
     * @param string $startSha
     * @param string $endSha
     * @return array
     */
    public function pullRequests($userName, $repository, $startSha, $endSha)
    {
        $this->commitRepository->commit(
            $userName,
            $repository,
            $startSha
        );

        $this->commitRepository->commit(
            $userName,
            $repository,
            $endSha
        );

        return [];
    }
}
