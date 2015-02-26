<?php

namespace Localheinz\ChangeLog\Repository;

use Guzzle\Common\Exception\BadMethodCallException;

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
        if ($startSha === $endSha) {
            return [];
        }

        $startCommit = $this->commitRepository->commit(
            $userName,
            $repository,
            $startSha
        );

        if (null === $startCommit) {
            throw new BadMethodCallException('Could not find start commit');
        }

        $endCommit = $this->commitRepository->commit(
            $userName,
            $repository,
            $endSha
        );

        if (null === $endCommit) {
            throw new BadMethodCallException('Could not find end commit');
        }

        return [];
    }
}
