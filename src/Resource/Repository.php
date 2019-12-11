<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Resource;

use Ergebnis\GitHub\Changelog\Exception;

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
     * @throws Exception\InvalidArgumentException
     *
     * @return self
     */
    public static function fromOwnerAndName(string $owner, string $name): self
    {
        if (1 !== \preg_match(self::ownerRegEx(), $owner)) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'Owner "%s" does not appear to be a valid owner.',
                $owner
            ));
        }

        if (1 !== \preg_match(self::nameRegEx(), $name)) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'Name "%s" does not appear to be a valid name.',
                $name
            ));
        }

        return new self(
            $owner,
            $name
        );
    }

    public static function fromRemoteUrl(string $remoteUrl): self
    {
        $regEx = self::remoteUrlRegEx();

        if (1 !== \preg_match($regEx, $remoteUrl, $matches)) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'Unable to parse remote URL "%s".',
                $remoteUrl
            ));
        }

        return new self(
            $matches['owner'],
            $matches['name']
        );
    }

    /**
     * @param string $string
     *
     * @throws Exception\InvalidArgumentException
     *
     * @return self
     */
    public static function fromString(string $string): self
    {
        if (1 !== \preg_match(self::stringRegex(), $string, $matches)) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'String "%s" does not appear to be a valid string.',
                $string
            ));
        }

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
        $regEx = '(?P<name>[a-zA-Z0-9-_.]+)';

        if (true === $asPartial) {
            return $regEx;
        }

        return self::fullMatch($regEx);
    }

    private static function remoteUrlRegEx(): string
    {
        return self::fullMatch(\sprintf(
            '(?P<remoteUrl>(https:\/\/github\.com\/|git@github\.com:)%s\/%s\.git)',
            self::ownerRegEx(true),
            self::nameRegEx(true)
        ));
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
