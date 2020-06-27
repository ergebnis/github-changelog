<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2020 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Resource;

final class Range implements RangeInterface
{
    /**
     * @var CommitInterface[]
     */
    private array $commits = [];

    /**
     * @var PullRequestInterface[]
     */
    private array $pullRequests = [];

    public function commits(): array
    {
        return $this->commits;
    }

    public function pullRequests(): array
    {
        return $this->pullRequests;
    }

    public function withCommit(CommitInterface $commit): RangeInterface
    {
        $instance = clone $this;

        $instance->commits[] = $commit;

        return $instance;
    }

    public function withPullRequest(PullRequestInterface $pullRequest): RangeInterface
    {
        $instance = clone $this;

        $instance->pullRequests[] = $pullRequest;

        return $instance;
    }
}
