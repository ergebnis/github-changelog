<?php

namespace Localheinz\ChangeLog\Service;

interface ProvidesItems
{
    /**
     * @param string $userName
     * @param string $repository
     * @param string $startReference
     * @param string $endReference
     * @return array
     */
    public function items($userName, $repository, $startReference, $endReference);
}
