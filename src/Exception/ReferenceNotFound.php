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

use Localheinz\GitHub\ChangeLog\Resource;

final class ReferenceNotFound extends \RuntimeException implements ExceptionInterface
{
    public static function fromRepositoryAndReference(Resource\RepositoryInterface $repository, string $reference): self
    {
        return new self(\sprintf(
            'Could not find reference "%s" in "%s".',
            $reference,
            $repository
        ));
    }
}
