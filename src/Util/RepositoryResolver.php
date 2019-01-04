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

namespace Localheinz\GitHub\ChangeLog\Util;

use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;

final class RepositoryResolver implements RepositoryResolverInterface
{
    /**
     * @var GitInterface
     */
    private $git;

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

        if (!\count($remoteUrls)) {
            throw new Exception\RuntimeException('Could not find any remote URLs.');
        }

        if (\count($fromRemoteNames)) {
            $remoteUrls = \array_replace(
                \array_flip($fromRemoteNames),
                \array_intersect_key(
                    $remoteUrls,
                    \array_flip($fromRemoteNames)
                )
            );
        }

        $repositories = \array_filter(\array_map(static function (string $remoteUrl) {
            try {
                $repository = Resource\Repository::fromRemoteUrl($remoteUrl);
            } catch (Exception\InvalidArgumentException $exception) {
                return;
            }

            return $repository;
        }, $remoteUrls));

        if (!\count($repositories)) {
            if (\count($fromRemoteNames)) {
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
