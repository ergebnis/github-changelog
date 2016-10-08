<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Refinery29\Test\Util\DataProvider;
use Refinery29\Test\Util\TestHelper;

final class PullRequestTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testIsFinal()
    {
        $reflection = new \ReflectionClass(Resource\PullRequest::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testImplementsPullRequestInterface()
    {
        $reflection = new \ReflectionClass(Resource\PullRequest::class);

        $this->assertTrue($reflection->implementsInterface(Resource\PullRequestInterface::class));
    }

    /**
     * @dataProvider providerInvalidId
     *
     * @param mixed $id
     */
    public function testConstructorRejectsInvalidId($id)
    {
        $this->expectException(\InvalidArgumentException::class);

        $title = $this->getFaker()->sentence();

        new Resource\PullRequest(
            $id,
            $title
        );
    }

    /**
     * @return \Generator
     */
    public function providerInvalidId()
    {
        return $this->provideDataFrom(
            new DataProvider\InvalidIntegerish(),
            new DataProvider\Elements([
                0,
                -1 * $this->getFaker()->numberBetween(1),
            ])
        );
    }

    /**
     * @dataProvider \Refinery29\Test\Util\DataProvider\InvalidString::data()
     *
     * @param mixed $message
     */
    public function testConstructorRejectsInvalidTitle($message)
    {
        $this->expectException(\InvalidArgumentException::class);

        $sha = sha1($this->getFaker()->sentence());

        new Resource\PullRequest(
            $sha,
            $message
        );
    }

    public function testConstructorSetsIdAndTitle()
    {
        $faker = $this->getFaker();

        $id = $faker->numberBetween(1);
        $title = $faker->sentence();

        $pullRequest = new Resource\PullRequest(
            $id,
            $title
        );

        $this->assertSame($id, $pullRequest->id());
        $this->assertSame($title, $pullRequest->title());
    }
}
