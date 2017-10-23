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

    private function __construct(string $owner, string $name)
    {
        $this->owner = $owner;
        $this->name = $name;
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s/%s',
            $this->owner,
            $this->name
        );
    }

    /**
     * @param string $owner
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public static function fromOwnerAndName(string $owner, string $name): self
    {
        Assert\that($owner)->regex(self::ownerRegEx());
        Assert\that($name)->regex(self::nameRegEx());

        return new self(
            $owner,
            $name
        );
    }

    /**
     * @param string $string
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public static function fromString(string $string): self
    {
        $regEx = self::stringRegex();

        Assert\that($string)->regex($regEx);

        \preg_match($regEx, $string, $matches);

        return new self(
            $matches['owner'],
            $matches['name']
        );
    }

    public function owner(): string
    {
        return $this->owner;
    }

    public function name(): string
    {
        return $this->name;
    }

    private static function ownerRegEx(bool $asPartial = false): string
    {
        $regEx = '(?P<owner>[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*)';

        if (true === $asPartial) {
            return $regEx;
        }

        return self::fullMatch($regEx);
    }

    private static function nameRegEx(bool $asPartial = false): string
    {
        $regEx = '(?P<name>[a-zA-Z0-9-_]+)';

        if (true === $asPartial) {
            return $regEx;
        }

        return self::fullMatch($regEx);
    }

    private static function stringRegex(): string
    {
        return self::fullMatch(\sprintf(
            '%s\/%s',
            self::ownerRegEx(true),
            self::nameRegEx(true)
        ));
    }

    private static function fullMatch(string $regEx): string
    {
        return \sprintf(
            '/^%s$/',
            $regEx
        );
    }
}
