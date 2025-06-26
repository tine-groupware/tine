<?php
/**
 * run rector:
 *
 * $ vendor/bin/rector --config=.rector/rector.php process . --dry-run
 */

declare(strict_types=1);

$tineroot = dirname(__DIR__);

// just run a single rector rule...
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
return Rector\Config\RectorConfig::configure()
    ->withPaths([$tineroot])
    ->withPhpVersion(Rector\ValueObject\PhpVersion::PHP_84)
    ->withSkipPath(
        $tineroot . '/vendor'
    )
    // "Class "SimpleSAML\Module" not found".
    ->withSkipPath(
        $tineroot . '/SSO/Controller.php'
    )
    ->withSkipPath(
        $tineroot . '/SSO/Facade/SAML/Session.php'
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
//        $tineroot . '/vendor',
//        LongArrayToShortArrayRector::class
//    ]);
//};
