<?php

namespace Localheinz\ChangeLog\Service;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;

class PullRequest implements ProvidesItems
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
     * @param Commit $commitService
     * @param Repository\PullRequest $pullRequestRepository
     */
    public function __construct(Commit $commitService, Repository\PullRequest $pullRequestRepository)
    {
        $this->commitService = $commitService;
        $this->pullRequestRepository = $pullRequestRepository;
    }

    /**
     * @param string $userName
     * @param string $repository
     * @param string $startSha
     * @param string $endSha
     * @return Entity\PullRequest[] array
     */
    public function items($userName, $repository, $startSha, $endSha)
    {
        $commits = $this->commitService->items(
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
