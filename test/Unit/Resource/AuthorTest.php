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

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class AuthorTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsAuthorInterface()
    {
        $this->assertClassImplementsInterface(Resource\AuthorInterface::class, Resource\Author::class);
    }

    /**
     * @dataProvider providerInvalidHtmlUrl
     *
     * @param mixed $htmlUrl
     */
    public function testConstructorRejectsInvalidHtmlUrl(string $htmlUrl)
    {
        $this->expectException(\InvalidArgumentException::class);

        $login = $this->faker()->slug();

        new Resource\Author(
            $login,
            $htmlUrl
        );
    }

    public function providerInvalidHtmlUrl(): \Generator
    {
        $faker = $this->faker();

        $values = [
            'string-path-only' => \implode('/', $faker->words),
            'string-word-only' => $faker->word,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function testConstructorSetsLoginAndHtmlUrl()
    {
        $faker = $this->faker();

        $login = $faker->slug();
        $htmlUrl = $faker->url;

        $author = new Resource\Author(
            $login,
            $htmlUrl
        );

        $this->assertSame($login, $author->login());
        $this->assertSame($htmlUrl, $author->htmlUrl());
    }
}
