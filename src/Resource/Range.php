<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Resource;

final class Range implements RangeInterface
{
    /**
     * @var CommitInterface[]
     */
    private $commits = [];

    /**
     * @var PullRequestInterface[]
     */
    private $pullRequests = [];

    public function commits()
    {
        return $this->commits;
    }

    public function pullRequests()
    {
        return $this->pullRequests;
    }

    public function withCommit(CommitInterface $commit)
    {
        $commits = $this->commits;
        $commits[] = $commit;

        $instance = clone $this;
        $instance->commits = $commits;

        return $instance;
    }

    public function withPullRequest(PullRequestInterface $pullRequest)
    {
        $pullRequests = $this->pullRequests();
        $pullRequests[] = $pullRequest;

        $instance = clone $this;
        $instance->pullRequests = $pullRequests;

        return $instance;
    }
}
