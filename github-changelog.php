<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2022 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

use Ergebnis\GitHub\Changelog;
use Github\Api;
use Github\Client;
use Symfony\Component\Cache;
use Symfony\Component\Console;
use Symfony\Component\Stopwatch;

$autoloaders = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (\file_exists($autoloader)) {
        require $autoloader;

        break;
    }
}

$client = new Client();

$client->addCache(new Cache\Adapter\FilesystemAdapter(
    '',
    0,
    __DIR__ . '/data/cache'
));

$application = new Console\Application('github-changelog', '0.5.2');

$application->add(new Changelog\Console\GenerateCommand(
    $client,
    new Changelog\Repository\PullRequestRepository(
        new Api\PullRequest($client),
        new Changelog\Repository\CommitRepository(new Api\Repository\Commits($client))
    ),
    new Changelog\Util\RepositoryResolver(new Changelog\Util\Git()),
    new Stopwatch\Stopwatch()
));

$application->run();
