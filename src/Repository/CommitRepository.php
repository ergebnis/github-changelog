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

namespace Ergebnis\GitHub\Changelog\Repository;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Resource;
use Github\Api;

final class CommitRepository implements CommitRepositoryInterface
{
    private Api\Repository\Commits $api;

    public function __construct(Api\Repository\Commits $api)
    {
        $this->api = $api;
    }

    public function items(Resource\RepositoryInterface $repository, string $startReference, ?string $endReference = null): Resource\RangeInterface
    {
        if ($startReference === $endReference) {
            return new Resource\Range();
        }

        try {
            $start = $this->show(
                $repository,
                $startReference
            );
        } catch (Exception\ReferenceNotFound $exception) {
            return new Resource\Range();
        }

        $params = [];

        if (null !== $endReference) {
            try {
                $end = $this->show(
                    $repository,
                    $endReference
                );
            } catch (Exception\ReferenceNotFound $exception) {
                return new Resource\Range();
            }

            $params = [
                'sha' => $end->sha(),
            ];
        }

        $commits = $this->all($repository, $params)->commits();

        $range = new Resource\Range();

        $tail = null;

        while (\count($commits)) {
            /** @var Resource\CommitInterface $commit */
            $commit = \array_shift($commits);

            if ($tail instanceof Resource\CommitInterface && $commit->equals($tail)) {
                continue;
            }

            if ($commit->equals($start)) {
                break;
            }

            $range = $range->withCommit($commit);

            if (0 === \count($commits)) {
                $tail = $commit;
                $params = [
                    'sha' => $tail->sha(),
                ];

                $commits = $this->all($repository, $params)->commits();
            }
        }

        return $range;
    }

    public function show(Resource\RepositoryInterface $repository, string $sha): Resource\CommitInterface
    {
        $response = $this->api->show(
            $repository->owner(),
            $repository->name(),
            $sha
        );

        if (!\is_array($response)) {
            throw Exception\ReferenceNotFound::fromRepositoryAndReference(
                $repository,
                $sha
            );
        }

        return new Resource\Commit(
            $response['sha'],
            $response['commit']['message']
        );
    }

    public function all(Resource\RepositoryInterface $repository, array $params = []): Resource\RangeInterface
    {
        $range = new Resource\Range();

        if (!\array_key_exists('per_page', $params)) {
            $params['per_page'] = 250;
        }

        $response = $this->api->all(
            $repository->owner(),
            $repository->name(),
            $params
        );

        if (!\is_array($response)) {
            return $range;
        }

        \array_walk($response, static function ($data) use (&$range): void {
            $commit = new Resource\Commit(
                $data['sha'],
                $data['commit']['message']
            );

            $range = $range->withCommit($commit);
        });

        return $range;
    }
}
