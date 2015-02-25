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
     * @param string $user
     */
    public function user($user)
    {
        $this->user = $user;
    }

    public function fromPullRequests()
    {
        if (null === $this->user) {
            throw new BadMethodCallException('User needs to be specified');
        }

        if (null === $this->repository) {
            throw new BadMethodCallException('Repository needs to be specified');
        }
    }
}
