<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
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
        $instance = clone $this;

        $instance->commits[] = $commit;

        return $instance;
    }

    public function withPullRequest(PullRequestInterface $pullRequest)
    {
        $instance = clone $this;

        $instance->pullRequests[] = $pullRequest;

        return $instance;
    }
}
