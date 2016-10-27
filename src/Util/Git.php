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

namespace Localheinz\GitHub\ChangeLog\Util;

use Localheinz\GitHub\ChangeLog\Exception;

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

        return \array_filter(\array_combine(
            $remoteNames,
            \array_map(function (string $remoteName) {
                \exec(
                    \sprintf(
                        'git remote get-url %s',
                        $remoteName
                    ),
                    $remoteUrls,
                    $returnValue
                );

                if (0 !== $returnValue || 0 === \count($remoteUrls)) {
                    return;
                }

                return \array_shift($remoteUrls);
            }, $remoteNames)
        ));
    }
}
