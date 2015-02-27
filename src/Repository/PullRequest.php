<?php

namespace Localheinz\ChangeLog\Repository;

use Guzzle\Common\Exception\BadMethodCallException;

class PullRequest
{
    /**
     * @var Commits
     */
    private $commitRepository;

    /**
     * @param Commits $commitRepository
     */
    public function __construct(Commits $commitRepository)
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
        if ($startSha === $endSha) {
            return [];
        }

        $startCommit = $this->commitRepository->show(
            $userName,
            $repository,
            $startSha
        );

        if (null === $startCommit) {
            throw new BadMethodCallException('Could not find start commit');
        }

        $endCommit = $this->commitRepository->show(
            $userName,
            $repository,
            $endSha
        );

        if (null === $endCommit) {
            throw new BadMethodCallException('Could not find end commit');
        }

        $this->commitRepository->range(
            $userName,
            $repository,
            $startCommit->sha(),
            $endCommit->sha()
        );

        return [];
    }
}
