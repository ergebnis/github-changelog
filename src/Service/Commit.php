<?php

namespace Localheinz\ChangeLog\Service;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;

class Commit
{
    /**
     * @var Repository\Commit
     */
    private $repository;

    public function __construct(Repository\Commit $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $userName
     * @param string $repository
     * @param string $startReference
     * @param string $endReference
     * @return Entity\Commit[]
     */
    public function range($userName, $repository, $startReference, $endReference)
    {
        if ($startReference === $endReference) {
            return [];
        }

        $start = $this->repository->show(
            $userName,
            $repository,
            $startReference
        );

        if (null === $start) {
            return [];
        }

        $end = $this->repository->show(
            $userName,
            $repository,
            $endReference
        );

        if (null === $end) {
            return [];
        }

        $commits = $this->repository->all($userName, $repository, [
            'sha' => $start->sha(),
        ]);

        if (!is_array($commits)) {
            return [];
        }

        $range = [];

        $currentStart = $start;

        while (count($commits)) {
            /* @var Entity\Commit $commit */
            $commit = array_shift($commits);

            if ($commit->sha() === $currentStart->sha()) {
                continue;
            }

            array_push($range, $commit);

            if ($commit->sha() === $end->sha()) {
                break;
            }

            if (!count($commits)) {
                $currentStart = $commit;

                $commits = $this->repository->all($userName, $repository, [
                    'sha' => $currentStart->sha(),
                ]);
            }
        }

        return $range;
    }
}
