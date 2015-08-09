<?php

namespace Localheinz\GitHub\ChangeLog\Test\Entity;

use Localheinz\GitHub\ChangeLog\Entity;
use Localheinz\GitHub\ChangeLog\Test\Util\FakerTrait;
use PHPUnit_Framework_TestCase;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use FakerTrait;

    public function testConstructorSetsIdAndTitle()
    {
        $faker = $this->faker();

        $id = $faker->randomNumber();
        $title = $faker->sentence();

        $entity = new Entity\PullRequest(
            $id,
            $title
        );

        $this->assertSame($id, $entity->id());
        $this->assertSame($title, $entity->title());
    }
}
