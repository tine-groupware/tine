<?php
/**
 * run rector:
 *
 * $ vendor/bin/rector process . --dry-run
 */

declare(strict_types=1);

// just run a single rector rule...
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
return Rector\Config\RectorConfig::configure()
    ->withPaths([__DIR__])
    ->withPhpVersion(Rector\ValueObject\PhpVersion::PHP_84)
    ->withSkipPath(
        __DIR__ . '/vendor'
    )
    // "Class "SimpleSAML\Module" not found".
    ->withSkipPath(
        __DIR__ . '/SSO/Controller.php'
    )
    ->withSkipPath(
        __DIR__ . '/SSO/Facade/SAML/Session.php'
    )
    ->withRules([
        Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class,
    ])
//    ->withSkip([
//        LongArrayToShortArrayRector::class
//    ])
    ;


// a different way to run rector...

//use Rector\Config\RectorConfig;
//use Rector\Set\ValueObject\LevelSetList;
//return static function (RectorConfig $rectorConfig): void {
//    $rectorConfig->sets([
//        LevelSetList::UP_TO_PHP_84,
//    ]);
//    $rectorConfig->skip([
//        __DIR__ . '/vendor',
//        LongArrayToShortArrayRector::class
//    ]);
//};
