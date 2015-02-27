<?php

namespace Localheinz\ChangeLog\Provider;

interface ItemProvider
{
    /**
     * @param string $vendor
     * @param string $package
     * @param string $startReference
     * @param string $endReference
     * @return array
     */
    public function items($vendor, $package, $startReference, $endReference);
}
