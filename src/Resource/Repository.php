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

use Assert;

final class Repository implements RepositoryInterface
{
    /**
     * @var string
     */
    private $owner;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $owner
     * @param string $name
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $owner, string $name)
    {
        Assert\that($owner)->regex('/^[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*$/');
        Assert\that($name)->notBlank();

        $this->owner = $owner;
        $this->name = $name;
    }

    public function owner(): string
    {
        return $this->owner;
    }

    public function name(): string
    {
        return $this->name;
    }
}
