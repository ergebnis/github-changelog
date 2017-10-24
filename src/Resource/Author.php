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
use Localheinz\GitHub\ChangeLog\Exception;

final class Author implements AuthorInterface
{
    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $htmlUrl;

    /**
     * @param string $login
     * @param string $htmlUrl
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(string $login, string $htmlUrl)
    {
        try {
            Assert\that($htmlUrl)->url();
        } catch (\InvalidArgumentException $exception) {
            throw new Exception\InvalidArgumentException(\sprintf(
                'URL "%s" does not appear to be a valid URL.',
                $htmlUrl
            ));
        }

        $this->login = $login;
        $this->htmlUrl = $htmlUrl;
    }

    public function login(): string
    {
        return $this->login;
    }

    public function htmlUrl(): string
    {
        return $this->htmlUrl;
    }
}
