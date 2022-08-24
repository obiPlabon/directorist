<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;

return static function (RectorConfig $rectorConfig): void {
    // $rectorConfig->paths([
    //     __DIR__ . '/src'
    // ]);

    // register a single rule
    // $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    //    $rectorConfig->sets([
    //        LevelSetList::UP_TO_PHP_74
    //    ]);

	$rectorConfig->paths([__DIR__ . '/includes/gutenberg']);

	$rectorConfig->phpVersion(PhpVersion::PHP_70);

	$rectorConfig->sets([
        SetList::CODE_QUALITY
    ]);

    // register single rule
    // $rectorConfig->rule(TypedPropertyRector::class);

	$rectorConfig->skip([
		CallableThisArrayToAnonymousFunctionRector::class
	]);
};
