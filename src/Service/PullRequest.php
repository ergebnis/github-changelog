<?php

namespace Localheinz\ChangeLog\Service;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;

class PullRequest
{
    /**
     * @var Commit
     */
    private $commitService;

    /**
     * @var Repository\PullRequest
     */
    private $pullRequestRepository;

    /**
     * @var string
     */
    private $userName;

    /**
     * @var string
     */
    private $repository;

    /**
     * @var string
     */
    private $startSha;

    /**
     * @var string
     */
    private $endSha;

    /**
     * @param Commit $commitService
     * @param Repository\PullRequest $pullRequestRepository
     */
    public function __construct(Commit $commitService, Repository\PullRequest $pullRequestRepository)
    {
        $this->commitService = $commitService;
        $this->pullRequestRepository = $pullRequestRepository;
    }

    /**
     * @param string $user
     * @return self
     */
    public function userName($user)
    {
        $this->userName = $user;

        return $this;
    }

    /**
     * @param string $repository
     * @return self
     */
    public function repository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @param string $startSha
     * @return self
     */
    public function startSha($startSha)
    {
        $this->startSha = $startSha;

        return $this;
    }

    /**
     * @param string $endSha
     * @return self
     */
    public function endSha($endSha)
    {
        $this->endSha = $endSha;

        return $this;
    }

    /**
     * @param string $userName
     * @param string $repository
     * @param string $startSha
     * @param string $endSha
     * @return Entity\PullRequest[] array
     */
    public function pullRequests($userName, $repository, $startSha, $endSha)
    {
        $commits = $this->commitService->range(
            $userName,
            $repository,
            $startSha,
            $endSha
        );

        $pullRequests = [];

        array_walk($commits, function (Entity\Commit $commit) use (&$pullRequests, $userName, $repository) {

            if (0 === preg_match('/^Merge pull request #(?P<id>\d+)/', $commit->message(), $matches)) {
                return;
            }

            $id = $matches['id'];

            $pullRequest = $this->pullRequestRepository->show(
                $userName,
                $repository,
                $id
            );

            if (null === $pullRequest) {
                return;
            }

            array_push($pullRequests, $pullRequest);
        });

        return $pullRequests;
    }
}
