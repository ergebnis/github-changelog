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

use Localheinz\GitHub\ChangeLog\Exception;

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

    /**
     * @param string $remoteName
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     *
     * @return self
     */
    public static function fromRemoteName(string $remoteName): self
    {
        if (1 !== \preg_match(self::remoteNameRegEx(), $remoteName)) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'Remote name "%s" appears to be invalid.',
                $remoteName
            ));
        }

        \exec(
            'git remote',
            $remoteNames,
            $returnValue
        );

        if (!\in_array($remoteName, $remoteNames, true)) {
            throw new Exception\RuntimeException(\sprintf(
                'Remote with name "%s" does not exist.',
                $remoteName
            ));
        }

        \exec(
            \sprintf(
                'git remote get-url %s',
                $remoteName
            ),
            $remoteUrls,
            $returnValue
        );

        if (0 !== $returnValue || 0 === \count($remoteUrls)) {
            throw new Exception\RuntimeException(\sprintf(
                'Unable to determine url for remote with name "%s"',
                $remoteName
            ));
        }

        $remoteUrl = \array_shift($remoteUrls);

        return self::fromRemoteUrl($remoteUrl);
    }

    public static function fromRemoteUrl(string $remoteUrl)
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

    private static function remoteNameRegEx()
    {
        return '/^\w+(-\w+)*$/';
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

    private static function remoteUrlRegEx(): string
    {
        return self::fullMatch(\sprintf(
            '(?P<remoteUrl>(https:\/\/github\.com\/|git@github\.com:)%s\/%s\.git)',
            self::ownerRegEx(true),
            self::nameRegEx(true)
        ));
    }

    private static function stringRegex(bool $asPartial = false): string
    {
        $regEx = \sprintf(
            '%s\/%s',
            self::ownerRegEx(true),
            self::nameRegEx(true)
        );

        if (true === $asPartial) {
            return $regEx;
        }

        return self::fullMatch($regEx);
    }

    private static function fullMatch(string $regEx): string
    {
        return \sprintf(
            '/^%s$/',
            $regEx
        );
    }
}
