<?php

namespace Localheinz\ChangeLog\Test\Util;

use Faker;
use Localheinz\ChangeLog\Entity;

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

    /**
     * @param string $sha
     * @param string $message
     * @return Entity\Commit
     */
    private function commit($sha = null, $message = null)
    {
        $sha = $sha ?: $this->faker()->unique()->sha1;
        $message = $message ?: $this->faker()->unique()->sentence();

        return new Entity\Commit(
            $sha,
            $message
        );
    }
}
