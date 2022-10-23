<?php
declare(strict_types=1);

use OLIUP\CS\PhpCS;
use PhpCsFixer\Finder;

$finder = Finder::create();

$finder->in([
	__DIR__ . '/src',
	__DIR__ . '/tests',
])
	->name('*.php')
	->notPath('vendor')
	->notPath('assets')
	->notPath('ignore')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

$header = <<<'EOF'
Copyright (c) 2017-present, Emile Silas Sare

This file is part of Kli package.

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$rules = [
	'header_comment' => [
		'header'       => $header,
		'comment_type' => 'PHPDoc',
		'separate'     => 'both',
		'location'     => 'after_open'
	],
	'comment_to_phpdoc' => [],
];

return (new PhpCS())->mergeRules($finder, $rules)
					->setRiskyAllowed(true);
