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

namespace Localheinz\GitHub\ChangeLog\Resource;

interface CommitInterface
{
    /**
     * @return string
     */
    public function sha();

    /**
     * @return string
     */
    public function message();

    /**
     * @param CommitInterface $commit
     *
     * @return bool
     */
    public function equals(CommitInterface $commit);
}
