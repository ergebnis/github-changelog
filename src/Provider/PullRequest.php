<?php

namespace Localheinz\ChangeLog\Provider;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;

class PullRequest implements ItemProvider
{
    /**
     * @var Commit
     */
    private $commitRepository;

    /**
     * @var Repository\PullRequest
     */
    private $pullRequestRepository;

    /**
     * @param Repository\Commit $commitRepository
     * @param Repository\PullRequest $pullRequestRepository
     */
    public function __construct(Repository\Commit $commitRepository, Repository\PullRequest $pullRequestRepository)
    {
        $this->commitRepository = $commitRepository;
        $this->pullRequestRepository = $pullRequestRepository;
    }

    /**
     * @param string $vendor
     * @param string $package
     * @param string $startReference
     * @param string $endReference
     * @return Entity\PullRequest[] array
     */
    public function items($vendor, $package, $startReference, $endReference)
    {
        $commits = $this->commitRepository->items(
            $vendor,
            $package,
            $startReference,
            $endReference
        );

        $pullRequests = [];

        array_walk($commits, function (Entity\Commit $commit) use (&$pullRequests, $vendor, $package) {

            if (0 === preg_match('/^Merge pull request #(?P<id>\d+)/', $commit->message(), $matches)) {
                return;
            }

            $id = $matches['id'];

            $pullRequest = $this->pullRequestRepository->show(
                $vendor,
                $package,
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
