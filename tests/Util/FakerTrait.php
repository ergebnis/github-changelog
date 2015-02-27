<?php

namespace Localheinz\ChangeLog\Test\Util;

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
