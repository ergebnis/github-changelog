<?php

namespace Localheinz\ChangeLog\Provider;

use Localheinz\ChangeLog\Entity;
use Localheinz\ChangeLog\Repository;

class Commit implements ItemProvider
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
     * @param string $vendor
     * @param string $package
     * @param string $startReference
     * @param string $endReference
     * @return Entity\Commit[]
     */
    public function items($vendor, $package, $startReference, $endReference)
    {
        if ($startReference === $endReference) {
            return [];
        }

        $start = $this->repository->show(
            $vendor,
            $package,
            $startReference
        );

        if (null === $start) {
            return [];
        }

        $end = $this->repository->show(
            $vendor,
            $package,
            $endReference
        );

        if (null === $end) {
            return [];
        }

        $commits = $this->repository->all($vendor, $package, [
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

                $commits = $this->repository->all($vendor, $package, [
                    'sha' => $currentStart->sha(),
                ]);
            }
        }

        return $range;
    }
}
