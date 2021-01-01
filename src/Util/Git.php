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

namespace Ergebnis\GitHub\Changelog\Util;

use Ergebnis\GitHub\Changelog\Exception;

final class Git implements GitInterface
{
    public function remoteUrls(): array
    {
        \exec(
            'git remote',
            $remoteNames,
            $returnValue
        );

        if (0 !== $returnValue) {
            throw new Exception\RuntimeException('Unable to determine git remote names.');
        }

        /** @var string[] $remoteUrls */
        $remoteUrls = \array_combine(
            $remoteNames,
            \array_map(static function (string $remoteName): ?string {
                \exec(
                    \sprintf(
                        'git remote get-url %s',
                        $remoteName
                    ),
                    $remoteUrls,
                    $returnValue
                );

                if (0 !== $returnValue || 0 === \count($remoteUrls)) {
                    return null;
                }

                return \array_shift($remoteUrls);
            }, $remoteNames)
        );

        return \array_filter($remoteUrls);
    }
}
