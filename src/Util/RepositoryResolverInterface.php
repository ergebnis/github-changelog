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

interface RepositoryResolverInterface
{
    /**
     * @param string[] ...$fromRemoteNames
     *
     * @throws Exception\RuntimeException
     *
     * @return Resource\RepositoryInterface
     */
    public function resolve(string ...$fromRemoteNames): Resource\RepositoryInterface;
}
