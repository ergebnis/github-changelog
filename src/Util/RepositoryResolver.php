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

namespace Ergebnis\GitHub\Changelog\Util;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Resource;

final class RepositoryResolver implements RepositoryResolverInterface
{
    private GitInterface $git;

    public function __construct(GitInterface $git)
    {
        $this->git = $git;
    }

    public function resolve(string ...$fromRemoteNames): Resource\RepositoryInterface
    {
        try {
            $remoteUrls = $this->git->remoteUrls();
        } catch (Exception\RuntimeException $exception) {
            throw new Exception\RuntimeException('Unable to resolve repository using git meta data.');
        }

        if (0 === \count($remoteUrls)) {
            throw new Exception\RuntimeException('Could not find any remote URLs.');
        }

        if (0 < \count($fromRemoteNames)) {
            /** @var string[] $remoteUrls */
            $remoteUrls = \array_replace(
                \array_flip($fromRemoteNames),
                \array_intersect_key(
                    $remoteUrls,
                    \array_flip($fromRemoteNames)
                )
            );
        }

        $repositories = \array_filter(\array_map(static function (string $remoteUrl): ?Resource\Repository {
            try {
                $repository = Resource\Repository::fromRemoteUrl($remoteUrl);
            } catch (Exception\InvalidArgumentException $exception) {
                return null;
            }

            return $repository;
        }, $remoteUrls));

        if (0 === \count($repositories)) {
            if (0 < \count($fromRemoteNames)) {
                throw new Exception\RuntimeException(\sprintf(
                    'Could not find a valid remote URL for remotes "%s".',
                    \implode('", "', $fromRemoteNames)
                ));
            }

            throw new Exception\RuntimeException('Could not find a valid remote URL.');
        }

        return \array_shift($repositories);
    }
}
