<?php

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Resource;

interface RangeInterface
{
    /**
     * @return CommitInterface[]
     */
    public function commits();

    /**
     * @return PullRequestInterface[]
     */
    public function pullRequests();

    /**
     * @param CommitInterface $commit
     *
     * @return static
     */
    public function withCommit(CommitInterface $commit);

    /**
     * @param PullRequestInterface $pullRequest
     *
     * @return static
     */
    public function withPullRequest(PullRequestInterface $pullRequest);
}
