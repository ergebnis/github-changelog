<?php

namespace Localheinz\ChangeLog;

use BadMethodCallException;

class Builder
{
    /**
     * @var Repository\Commit
     */
    private $commitRepository;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $repository;

    /**
     * @var string
     */
    private $start;

    /**
     * @var string
     */
    private $end;

    /**
     * @param Repository\Commit $commitRepository
     */
    public function __construct(Repository\Commit $commitRepository)
    {
        $this->commitRepository = $commitRepository;
    }

    /**
     * @param string $user
     * @return self
     */
    public function user($user)
    {
        $this->user = $user;

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
     * @param string $start
     * @return self
     */
    public function start($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @param string $end
     * @return self
     */
    public function end($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return array
     */
    public function pullRequests()
    {
        if (null === $this->user) {
            throw new BadMethodCallException('User needs to be specified');
        }

        if (null === $this->repository) {
            throw new BadMethodCallException('Repository needs to be specified');
        }

        if (null === $this->start) {
            throw new BadMethodCallException('Start reference needs to be specified');
        }

        $this->commitRepository->commits(
            $this->user,
            $this->repository,
            $this->start,
            $this->end
        );

        return [];
    }
}
