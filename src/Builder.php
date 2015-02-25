<?php

namespace Localheinz\ChangeLog;

use BadMethodCallException;

class Builder
{
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
    private $startReference;

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

    public function fromPullRequests()
    {
        if (null === $this->user) {
            throw new BadMethodCallException('User needs to be specified');
        }

        if (null === $this->repository) {
            throw new BadMethodCallException('Repository needs to be specified');
        }

        if (null === $this->startReference) {
            throw new BadMethodCallException('Start reference needs to be specified');
        }
    }
}
