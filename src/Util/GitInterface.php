<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2021 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Util;

use Ergebnis\GitHub\Changelog\Exception;

interface GitInterface
{
    /**
     * @throws Exception\RuntimeException
     *
     * @return string[]
     */
    public function remoteUrls(): array;
}
