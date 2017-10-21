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

final class PullRequest implements PullRequestInterface
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $title;

    /**
     * @param int    $number
     * @param string $title
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(int $number, string $title)
    {
        Assert\that($number)->greaterThan(0);

        $this->number = $number;
        $this->title = $title;
    }

    public function number(): int
    {
        return $this->number;
    }

    public function title(): string
    {
        return $this->title;
    }
}
