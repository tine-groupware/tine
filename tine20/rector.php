<?php
/**
 * run rector:
 *
 * $ vendor/bin/rector process . --dry-run
 */

declare(strict_types=1);

use Rector\Config\RectorConfig; 
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
    ]);
    $rectorConfig->skip([
        __DIR__ . '/vendor',
        LongArrayToShortArrayRector::class
    ]);
};
