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
     * @param string      $owner
     * @param string      $name
     * @param string      $startReference
     * @param null|string $endReference
     *
     * @return Resource\Range
     */
    public function items($owner, $name, $startReference, $endReference = null);

    /**
     * @param string $owner
     * @param string $name
     * @param string $sha
     *
     * @throws Exception\ReferenceNotFound
     *
     * @return Resource\CommitInterface
     */
    public function show($owner, $name, $sha);

    /**
     * @param string $owner
     * @param string $name
     * @param array  $params
     *
     * @return Resource\Range
     */
    public function all($owner, $name, array $params = []);
}
