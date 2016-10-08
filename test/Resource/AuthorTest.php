<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Refinery29\Test\Util\TestHelper;

class AuthorTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $reflectionClass = new \ReflectionClass(Resource\Author::class);

        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testImplementsAuthorInterface()
    {
        $reflectionClass = new \ReflectionClass(Resource\Author::class);

        $this->assertTrue($reflectionClass->implementsInterface(Resource\AuthorInterface::class));
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
