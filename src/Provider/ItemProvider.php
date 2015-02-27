<?php

namespace Localheinz\ChangeLog\Provider;

interface ItemProvider
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
