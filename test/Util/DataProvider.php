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

namespace Localheinz\GitHub\ChangeLog\Test\Util;

use Localheinz\Test\Util\Helper;

final class DataProvider
{
    use Helper;

    public function providerGenerateCommandArgument(): \Generator
    {
        $arguments = [
            'start-reference' => [
                true,
                'The start reference, e.g. "1.0.0"',
            ],
            'end-reference' => [
                false,
                'The end reference, e.g. "1.1.0"',
            ],
        ];

        foreach ($arguments as $name => [$isRequired, $description]) {
            yield $name => [
                $name,
                $isRequired,
                $description,
            ];
        }
    }

    public function providerGenerateCommandOption(): \Generator
    {
        $options = [
            'auth-token' => [
                'a',
                true,
                'The GitHub token',
                null,
            ],
            'repository' => [
                'r',
                true,
                'The repository, e.g. "localheinz/github-changelog"',
                null,
            ],
            'template' => [
                't',
                true,
                'The template to use for rendering a pull request',
                '- %pullrequest.title% (#%pullrequest.number%), by @%pullrequest.author.login%',
            ],
        ];

        foreach ($options as $name => [$shortcut, $isValueRequired, $description, $default]) {
            yield $name => [
                $name,
                $shortcut,
                $isValueRequired,
                $description,
                $default,
            ];
        }
    }

    public function providerInvalidRepositoryOwner(): \Generator
    {
        foreach ($this->invalidRepositoryOwners() as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function providerInvalidRepositoryName(): \Generator
    {
        foreach ($this->invalidRepositoryNames() as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function providerInvalidRepositoryString(): \Generator
    {
        foreach ($this->invalidRepositoryOwners() as $keyOne => $owner) {
            foreach ($this->invalidRepositoryNames() as $keyTwo => $name) {
                $key = \sprintf(
                    '%s/%s',
                    $keyOne,
                    $keyTwo
                );

                yield $key => [
                    \sprintf(
                        '%s/%s',
                        $owner,
                        $name
                    ),
                ];
            }
        }

        foreach ($this->validRepositoryOwners() as $key => $owner) {
            $key = \sprintf(
                'owner-%s',
                $key
            );

            yield $key => [
                $owner,
            ];
        }

        foreach ($this->validRepositoryNames() as $key => $name) {
            $key = \sprintf(
                'repository-%s',
                $key
            );

            yield $key => [
                $name,
            ];
        }
    }

    public function providerValidRepositoryOwnerAndName(): \Generator
    {
        foreach ($this->validRepositoryOwners() as $keyOne => $owner) {
            foreach ($this->validRepositoryNames() as $keyTwo => $name) {
                $key = \sprintf(
                    '%s/%s',
                    $keyOne,
                    $keyTwo
                );

                yield $key => [
                    $owner,
                    $name,
                ];
            }
        }
    }

    public function providerInvalidUrl(): \Generator
    {
        $faker = self::faker();

        /** @var string[] $words */
        $words = $faker->words;

        $values = [
            'string-path-only' => \implode(
                '/',
                $words
            ),
            'string-word-only' => $faker->word,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function providerInvalidSha(): \Generator
    {
        $faker = self::faker();

        $values = [
            'md5' => $faker->md5,
            'sentence' => $faker->sentence(),
            'sha256' => $faker->sha256,
            'word' => $faker->word,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function providerInvalidPullRequestNumber(): \Generator
    {
        $values = [
            'int-negative' => -1 * self::faker()->numberBetween(1),
            'int-zero' => 0,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function providerInvalidRemoteUrl(): \Generator
    {
        foreach ($this->remoteUrlPrefixes() as $keyOne => $remoteUrlPrefix) {
            foreach ($this->invalidStrings() as $keyTwo => $string) {
                $key = \implode('/', [
                    $keyOne,
                    $keyTwo,
                ]);

                $remoteUrl = \sprintf(
                    '%s%s.git',
                    $remoteUrlPrefix,
                    $string
                );

                yield $key => [
                    $remoteUrl,
                ];
            }
        }
    }

    public function providerValidRemoteUrlOwnerAndName(): \Generator
    {
        foreach ($this->remoteUrlPrefixes() as $keyOne => $remoteUrlPrefix) {
            foreach ($this->validRepositoryOwners() as $keyTwo => $owner) {
                foreach ($this->validRepositoryNames() as $keyThree => $name) {
                    $key = \implode('/', [
                        $keyOne,
                        $keyTwo,
                        $keyThree,
                    ]);

                    $remoteUrl = \sprintf(
                        '%s%s/%s.git',
                        $remoteUrlPrefix,
                        $owner,
                        $name
                    );

                    yield $key => [
                        $remoteUrl,
                        $owner,
                        $name,
                    ];
                }
            }
        }
    }

    /**
     * @return string[]
     */
    private function invalidRepositoryOwners(): array
    {
        $faker = self::faker();

        return [
            'blank' => '  ',
            'empty' => '',
            'starts-with-hyphen' => \sprintf(
                '-%s',
                $faker->word
            ),
            'ends-with-hyphen' => \sprintf(
                '%s-',
                $faker->word
            ),
            'has-multiple-successive-hyphens' => \sprintf(
                '%s--%s',
                $faker->word,
                $faker->word
            ),
            'has-underscores' => \sprintf(
                '%s_%s',
                $faker->word,
                $faker->word
            ),
            'has-special-characters' => \implode('', $faker->randomElements([
                '.',
                '_',
                ':',
                'ä',
                'ü',
                'ö',
                'ß',
                '🤓',
            ])),
        ];
    }

    /**
     * @return string[]
     */
    private function validRepositoryOwners(): array
    {
        $faker = self::faker();

        /** @var string[] $words */
        $words = $faker->words();

        return [
            'digit' => (string) $faker->randomDigitNotNull,
            'letter' => $faker->randomLetter,
            'word' => $faker->word,
            'word-with-numbers' => \sprintf(
                '%s%d',
                $faker->word,
                $faker->numberBetween(1)
            ),
            'words-separated-by-hyphen' => \implode(
                '-',
                $words
            ),
            'words-with-numbers-separated-by-hyphens' => \implode(
                '-',
                \array_merge($words, [
                    $faker->numberBetween(1),
                    $faker->numberBetween(1),
                ])
            ),
        ];
    }

    /**
     * @return string[]
     */
    private function invalidRepositoryNames(): array
    {
        return [
            'blank' => '  ',
            'empty' => '',
            'has-special-characters' => \implode('', self::faker()->randomElements([
                '/',
                '\\',
                ':',
                'ä',
                'ü',
                'ö',
                'ß',
                '🤓',
            ])),
        ];
    }

    /**
     * @return string[]
     */
    private function validRepositoryNames(): array
    {
        $faker = self::faker();

        /** @var string[] $words */
        $words = $faker->words;

        return [
            'digit' => (string) $faker->randomDigitNotNull,
            'letter' => $faker->randomLetter,
            'word' => $faker->word,
            'hyphen' => '-',
            'hyphens' => '---',
            'underscore' => '_',
            'underscores' => '___',
            'word-with-numbers' => \sprintf(
                '%s%d',
                $faker->word,
                $faker->numberBetween(1)
            ),
            'words-separated-by-dots' => \implode(
                '.',
                $words
            ),
            'words-separated-by-hyphen' => \implode(
                '-',
                $words
            ),
            'words-separated-by-underscore' => \implode(
                '-',
                $words
            ),
            'words-with-numbers-separated-by-dots' => \implode(
                '.',
                \array_merge($words, [
                    $faker->numberBetween(1),
                    $faker->numberBetween(1),
                ])
            ),
            'words-with-numbers-separated-by-hyphens' => \implode(
                '-',
                \array_merge($words, [
                    $faker->numberBetween(1),
                    $faker->numberBetween(1),
                ])
            ),
            'words-with-numbers-separated-by-underscores' => \implode(
                '_',
                \array_merge($words, [
                    $faker->numberBetween(1),
                    $faker->numberBetween(1),
                ])
            ),
        ];
    }

    /**
     * @return string[]
     */
    private function remoteUrlPrefixes(): array
    {
        return [
            'https' => 'https://github.com/',
            'ssh' => 'git@github.com:',
        ];
    }

    /**
     * @return string[]
     */
    private function invalidStrings(): array
    {
        $invalidStrings = [];

        foreach ($this->invalidRepositoryOwners() as $keyOne => $owner) {
            foreach ($this->invalidRepositoryNames() as $keyTwo => $name) {
                $key = \sprintf(
                    'invalid-owner-and-invalid-name-%s/%s',
                    $keyOne,
                    $keyTwo
                );

                $value = \sprintf(
                    '%s/%s',
                    $owner,
                    $name
                );

                $invalidStrings[$key] = $value;
            }
        }

        foreach ($this->validRepositoryOwners() as $key => $owner) {
            $key = \sprintf(
                'valid-owner-only-%s',
                $key
            );

            $invalidStrings[$key] = $owner;
        }

        foreach ($this->validRepositoryNames()  as $key => $name) {
            $key = \sprintf(
                'valid-name-only-%s',
                $key
            );

            $invalidStrings[$key] = $name;
        }

        return $invalidStrings;
    }
}
