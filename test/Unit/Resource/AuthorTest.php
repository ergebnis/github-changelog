<?php

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
use Refinery29\Test\Util\TestHelper;

final class AuthorTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $this->assertFinal(Resource\Author::class);
    }

    public function testImplementsAuthorInterface()
    {
        $this->assertImplements(Resource\AuthorInterface::class, Resource\Author::class);
    }

    /**
     * @dataProvider \Refinery29\Test\Util\DataProvider\InvalidString::data()
     *
     * @param mixed $login
     */
    public function testConstructorRejectsInvalidLogin($login)
    {
        $this->expectException(\InvalidArgumentException::class);

        $htmlUrl = $this->getFaker()->url;

        new Resource\Author(
            $login,
            $htmlUrl
        );
    }

    /**
     * @dataProvider \Refinery29\Test\Util\DataProvider\InvalidUrl::data()
     *
     * @param mixed $htmlUrl
     */
    public function testConstructorRejectsInvalidHtmlUrl($htmlUrl)
    {
        $this->expectException(\InvalidArgumentException::class);

        $login = $this->getFaker()->userName;

        new Resource\Author(
            $login,
            $htmlUrl
        );
    }

    public function testConstructorSetsLoginAndHtmlUrl()
    {
        $faker = $this->getFaker();

        $login = $faker->userName;
        $htmlUrl = $faker->url;

        $author = new Resource\Author(
            $login,
            $htmlUrl
        );

        $this->assertSame($login, $author->login());
        $this->assertSame($htmlUrl, $author->htmlUrl());
    }
}
