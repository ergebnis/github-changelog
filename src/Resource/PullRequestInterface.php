<?php

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Resource;

interface PullRequestInterface
{
    /**
     * @return string
     */
    public function id();

    /**
     * @return string
     */
    public function title();
}
