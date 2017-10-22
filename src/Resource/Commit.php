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

use Assert;

final class Commit implements CommitInterface
{
    /**
     * @var string
     */
    private $sha;

    /**
     * @var string
     */
    private $message;

    /**
     * @param string $sha
     * @param string $message
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $sha, string $message)
    {
        Assert\that($sha)->regex('/^[0-9a-f]{40}$/i');

        $this->sha = $sha;
        $this->message = $message;
    }

    public function sha(): string
    {
        return $this->sha;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function equals(CommitInterface $commit): bool
    {
        return $commit->sha() === $this->sha();
    }
}
