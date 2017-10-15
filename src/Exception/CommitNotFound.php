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

namespace Localheinz\GitHub\ChangeLog\Exception;

final class CommitNotFound extends \RuntimeException implements ExceptionInterface
{
    public static function fromOwnerRepositoryAndReference(string $owner, string $repository, string $sha): self
    {
        return new self(\sprintf(
            'Could not find commit "%s" in "%s/%s".',
            $sha,
            $owner,
            $repository
        ));
    }
}
