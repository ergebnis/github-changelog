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
     * @param string $reference
     * @return self
     */
    public function start($reference)
    {
        $this->start = $reference;

        return $this;
    }

    /**
     * @param string $reference
     * @return self
     */
    public function end($reference)
    {
        $this->end = $reference;

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

        return [];
    }
}
