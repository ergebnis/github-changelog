<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

use Github\Api;
use Github\Client;
use Localheinz\GitHub\ChangeLog;
use Symfony\Component\Cache;
use Symfony\Component\Console;

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

$application->add(new ChangeLog\Console\GenerateCommand(
    $client,
    new ChangeLog\Repository\PullRequestRepository(
        new Api\PullRequest($client),
        new ChangeLog\Repository\CommitRepository(new Api\Repository\Commits($client))
    ),
    new ChangeLog\Util\RepositoryResolver(new ChangeLog\Util\Git())
));

$application->run();
