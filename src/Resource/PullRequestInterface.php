<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
