<?php

/**
 * Copyright (c) 2017-present, Emile Silas Sare
 *
 * This file is part of Kli package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Kli\Tests;

use Kli\Exceptions\KliInputException;
use Kli\Exceptions\KliRuntimeException;
use Kli\Kli;
use Kli\KliAction;
use Kli\KliOption;
use Kli\KliParser;
use PHPUnit\Framework\TestCase;

/**
 * Class KliParserTest.
 *
 * @internal
 *
 * @coversNothing
 */
final class KliParserTest extends TestCase
{
	private Kli $kli;

	private KliParser $parser;

	protected function setUp(): void
	{
		$this->kli    = Kli::new('test');
		$this->parser = new KliParser($this->kli);
	}

	public function testParseLongOptions(): void
	{
		$action = $this->makeAction();
		$args   = $this->parser->parse($action, ['--name=Alice', '--count=5']);

		self::assertSame('Alice', $args->get('name'));
		self::assertSame(5, $args->get('count'));
	}

	public function testParseShortFlag(): void
	{
		$action = $this->makeAction();
		$args   = $this->parser->parse($action, ['-n=Bob', '-c=3']);

		self::assertSame('Bob', $args->get('name'));
		self::assertSame(3, $args->get('count'));
	}

	public function testParseCombinedFlags(): void
	{
		$action = new KliAction('run');
		$action->option('verbose', 'v')->bool();
		$action->option('debug', 'd')->bool();

		$args = $this->parser->parse($action, ['-vd']);

		self::assertTrue($args->get('verbose'));
		self::assertTrue($args->get('debug'));
	}

	public function testParseUsesDefaults(): void
	{
		$action = $this->makeAction();
		$args   = $this->parser->parse($action, []);

		self::assertSame('World', $args->get('name'));
		self::assertSame(1, $args->get('count'));
	}

	public function testParseAnonymousArgs(): void
	{
		$action = $this->makeAction();
		$args   = $this->parser->parse($action, ['extra1', 'extra2']);

		self::assertSame('extra1', $args->getAnonymousAt(0));
		self::assertSame('extra2', $args->getAnonymousAt(1));
	}

	public function testParseOffsets(): void
	{
		$action = new KliAction('run');
		$optA   = new KliOption('first');
		$optA->string()->def('');
		$optA->offsets(0);
		$action->addOption($optA);

		$optB = new KliOption('second');
		$optB->string()->def('');
		$optB->offsets(1);
		$action->addOption($optB);

		$args = $this->parser->parse($action, ['hello', 'world']);

		self::assertSame('hello', $args->get('first'));
		self::assertSame('world', $args->get('second'));
	}

	public function testParseStopParsing(): void
	{
		$action = $this->makeAction();
		$args   = $this->parser->parse($action, ['--name=Alice', '--', '--count=99']);

		self::assertSame('Alice', $args->get('name'));
		// --count=99 after -- goes to anonymous
		self::assertSame('--count=99', $args->getAnonymousAt(0));
	}

	public function testParseUnknownOptionThrows(): void
	{
		// unknown options are rejected at resolution time with KliRuntimeException
		$action = $this->makeAction();

		$this->expectException(KliRuntimeException::class);
		$this->parser->parse($action, ['--unknown=value']);
	}

	public function testParseInvalidLongFlagSyntaxThrows(): void
	{
		// --a=value is invalid because the name is too short (must be > 3 chars including --)
		$action = $this->makeAction();

		$this->expectException(KliInputException::class);
		$this->parser->parse($action, ['--a=value']);
	}

	public function testParseInvalidCombinedFlagWithEqualsThrows(): void
	{
		// -ab=value is invalid; only single char flags may use =
		$action = $this->makeAction();

		$this->expectException(KliInputException::class);
		$this->parser->parse($action, ['-nc=hello']);
	}

	public function testParseRequiredOptionMissingThrows(): void
	{
		$action = new KliAction('run');
		$reqOpt = new KliOption('email');
		$reqOpt->required();
		$reqOpt->string();
		$action->addOption($reqOpt);

		$this->expectException(KliInputException::class);
		$this->parser->parse($action, []);
	}

	public function testParseEmptySingleDashThrows(): void
	{
		$action = $this->makeAction();

		$this->expectException(KliInputException::class);
		$this->parser->parse($action, ['-']);
	}

	public function testGetNamedArgs(): void
	{
		$action = $this->makeAction();
		$args   = $this->parser->parse($action, ['--name=Charlie']);

		$named = $args->getNamedArgs();

		self::assertArrayHasKey('name', $named);
		self::assertSame('Charlie', $named['name']);
	}

	public function testGetAnonymousArgs(): void
	{
		$action = $this->makeAction();
		$args   = $this->parser->parse($action, ['extra']);

		self::assertSame(['extra'], $args->getAnonymousArgs());
	}

	private function makeAction(string $name = 'run'): KliAction
	{
		$action = new KliAction($name);
		$action->option('name', 'n')->string()->def('World');
		$action->option('count', 'c')->number()->def(1);

		return $action;
	}
}
