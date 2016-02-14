<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Resource;

interface RangeInterface
{
    /**
     * @return CommitInterface[]
     */
    public function commits();

    /**
     * @return PullRequestInterface[]
     */
    public function pullRequests();
}
