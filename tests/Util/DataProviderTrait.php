<?php

namespace Localheinz\ChangeLog\Test\Util;

use Faker;
use Localheinz\ChangeLog\Entity;

trait DataProviderTrait
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

    /**
     * @param int $count
     * @return Entity\Commit[] array
     */
    private function commits($count)
    {
        $commits = [];

        $this->addCommits($commits, $count);

        return $commits;
    }

    /**
     * @param Entity\Commit[] $commits
     * @param int $count
     */
    private function addCommits(&$commits, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            array_push($commits, $this->commit());
        }
    }
}
