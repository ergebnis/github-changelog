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

namespace Localheinz\GitHub\ChangeLog\Repository;

use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;

interface PullRequestRepositoryInterface
{
    /**
     * @param Resource\RepositoryInterface$repository
     * @param int $number
     *
     * @throws Exception\PullRequestNotFound
     *
     * @return Resource\PullRequestInterface
     */
    public function show(Resource\RepositoryInterface $repository, int $number): Resource\PullRequestInterface;

    /**
     * @param Resource\RepositoryInterface$repository
     * @param string      $startReference
     * @param null|string $endReference
     *
     * @return Resource\RangeInterface
     */
    public function items(Resource\RepositoryInterface $repository, string $startReference, string $endReference = null): Resource\RangeInterface;
}
