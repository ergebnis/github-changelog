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

namespace Ergebnis\GitHub\Changelog\Resource;

interface CommitInterface
{
    public function sha(): string;

    public function message(): string;

    public function equals(self $commit): bool;
}
