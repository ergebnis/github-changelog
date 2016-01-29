<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Util;

use Faker;

trait FakerTrait
{
    /**
     * @var Faker\Generator
     */
    private $faker;

    /**
     * @return Faker\Generator
     */
    private function faker()
    {
        if (null === $this->faker) {
            $this->faker = Faker\Factory::create('en_US');
            $this->faker->seed(9000);
        }

        return $this->faker;
    }
}
