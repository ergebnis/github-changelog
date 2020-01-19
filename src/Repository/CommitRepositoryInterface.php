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

namespace Ergebnis\GitHub\Changelog\Repository;

use Ergebnis\GitHub\Changelog\Exception;
use Ergebnis\GitHub\Changelog\Resource;

interface CommitRepositoryInterface
{
    /**
     * @param Resource\RepositoryInterface $repository
     * @param string                       $startReference
     * @param null|string                  $endReference
     *
     * @return Resource\RangeInterface
     */
    public function items(Resource\RepositoryInterface $repository, string $startReference, ?string $endReference = null): Resource\RangeInterface;

    /**
     * @param Resource\RepositoryInterface $repository
     * @param string                       $sha
     *
     * @throws Exception\ReferenceNotFound
     *
     * @return Resource\CommitInterface
     */
    public function show(Resource\RepositoryInterface $repository, string $sha): Resource\CommitInterface;

    /**
     * @param Resource\RepositoryInterface $repository
     * @param array                        $params
     *
     * @return Resource\RangeInterface
     */
    public function all(Resource\RepositoryInterface $repository, array $params = []): Resource\RangeInterface;
}
