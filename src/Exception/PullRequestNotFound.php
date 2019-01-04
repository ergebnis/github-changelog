<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Exception;

use Localheinz\GitHub\ChangeLog\Resource;

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
