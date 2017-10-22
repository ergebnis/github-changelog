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

interface PullRequestRepositoryInterface
{
    /**
     * @param string $owner
     * @param string $name
     * @param int    $number
     *
     * @throws Exception\PullRequestNotFound
     *
     * @return Resource\PullRequestInterface
     */
    public function show($owner, $name, $number);

    /**
     * @param string      $owner
     * @param string      $name
     * @param string      $startReference
     * @param null|string $endReference
     *
     * @return Resource\Range
     */
    public function items($owner, $name, $startReference, $endReference = null);
}
