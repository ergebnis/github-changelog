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

use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class PullRequestTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPullRequestInterface()
    {
        $this->assertClassImplementsInterface(Resource\PullRequestInterface::class, Resource\PullRequest::class);
    }

    /**
     * @dataProvider providerInvalidNumber
     *
     * @param mixed $number
     */
    public function testConstructorRejectsInvalidNumber(int $number)
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Number "%d" does not appear to be a valid pull request number.',
            $number
        ));

        $title = $this->faker()->sentence();

        new Resource\PullRequest(
            $number,
            $title
        );
    }

    public function providerInvalidNumber(): \Generator
    {
        $values = [
            'int-negative' => -1 * $this->faker()->numberBetween(1),
            'int-zero' => 0,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function testConstructorSetsIdAndTitle()
    {
        $faker = $this->faker();

        $number = $faker->numberBetween(1);
        $title = $faker->sentence();

        $pullRequest = new Resource\PullRequest(
            $number,
            $title
        );

        $this->assertSame($number, $pullRequest->number());
        $this->assertSame($title, $pullRequest->title());
    }
}
