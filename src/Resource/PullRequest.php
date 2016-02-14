<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Resource;

use Assert\Assertion;

final class PullRequest implements PullRequestInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @param string $id
     * @param string $title
     */
    public function __construct($id, $title)
    {
        Assertion::integerish($id);
        Assertion::greaterThan($id, 0);
        Assertion::string($title);

        $this->id = $id;
        $this->title = $title;
    }

    public function id()
    {
        return $this->id;
    }

    public function title()
    {
        return $this->title;
    }
}
