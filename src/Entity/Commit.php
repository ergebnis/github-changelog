<?php

namespace Localheinz\ChangeLog\Entity;

class Commit
{
    /**
     * @var string
     */
    private $sha;

    /**
     * @var string
     */
    private $message;

    /**
     * @param string $sha
     * @param string $message
     */
    public function __construct($sha, $message)
    {
        $this->sha = $sha;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function sha()
    {
        return $this->sha;
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
