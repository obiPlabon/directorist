<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
	->withRootFiles()
    ->withPaths([
		__DIR__ . '/includes/classes/class-add-listing.php',
		// __DIR__ . '/includes/model/Listings.php',
        // __DIR__ . '/includes/fields',
        // __DIR__ . '/views',
		// __DIR__ . '/templates',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php70: true)
	// ->withSets([
	// 	SetList::PHP_70
	// ])
	->withFileExtensions(['php'])
    ->withTypeCoverageLevel(10);
