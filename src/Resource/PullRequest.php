<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2022 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Resource;

use Ergebnis\GitHub\Changelog\Exception;

final class PullRequest implements PullRequestInterface
{
    private int $number;

    private string $title;

    private UserInterface $author;

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(int $number, string $title, UserInterface $author)
    {
        if (1 > $number) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'Number "%d" does not appear to be a valid pull request number.',
                $number
            ));
        }

        $this->number = $number;
        $this->title = $title;
        $this->author = $author;
    }

    public function number(): int
    {
        return $this->number;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function author(): UserInterface
    {
        return $this->author;
    }
}
