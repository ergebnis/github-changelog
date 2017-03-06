<?php

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Resource;

use Assert;

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
        Assert\that($id)->integerish()->greaterThan(0);
        Assert\that($title)->string();

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
