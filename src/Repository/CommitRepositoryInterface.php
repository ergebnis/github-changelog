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

namespace Localheinz\GitHub\ChangeLog\Repository;

use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;

interface CommitRepositoryInterface
{
    /**
     * @param Resource\RepositoryInterface $repository
     * @param string                       $startReference
     * @param null|string                  $endReference
     *
     * @return Resource\RangeInterface
     */
    public function items(Resource\RepositoryInterface $repository, string $startReference, string $endReference = null): Resource\RangeInterface;

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
