<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2021 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Exception;

use Ergebnis\GitHub\Changelog\Resource;

final class PullRequestNotFound extends \RuntimeException implements ExceptionInterface
{
    public static function fromRepositoryAndNumber(Resource\RepositoryInterface $repository, int $number): self
    {
        return new self(\sprintf(
            'Could not find pull request "%d" in "%s".',
            $number,
            $repository
        ));
    }
}
