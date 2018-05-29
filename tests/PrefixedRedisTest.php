<?php declare(strict_types=1);

namespace Stefna\Redis\Tests;

use PHPUnit\Framework\TestCase;
use Stefna\Redis\PrefixedRedis;

class PrefixedRedisTest extends TestCase
{
	const PREFIX = 'test.';

	private function redis($ns = self::PREFIX, $useUnlinkAll = false)
	{
		$redis = new PrefixedRedis($ns, $useUnlinkAll);
		if (!$redis->connect('localhost')) {
			throw new \RuntimeException('Could not connect to redis on localhost');
		}
		return $redis;
	}

	public function testGetPrefix(): void
	{
		$redis = $this->redis();
		self::assertSame(self::PREFIX, $redis->getPrefix());
		self::assertSame(self::PREFIX, $redis->getOption(PrefixedRedis::OPT_PREFIX));
	}

	public function testSetWorks(): void
	{
		$redis = $this->redis();
		self::assertTrue($redis->set('a1', 'b'));
		self::assertSame('b', $redis->get('a1'));
	}

	public function testDeleteWorks(): void
	{
		$redis = $this->redis();
		$redis->set('a1', 'c');
		self::assertGreaterThan(0, $redis->delete('a1'));
		self::assertFalse($redis->get('a1'));
	}

	public function testDeleteAll(): void
	{
		$redis = $this->redis();
		$redis->set('a1', 'c');
		self::assertGreaterThan(0, $redis->deleteAll());
		self::assertFalse($redis->get('a1'));
	}

	public function testDeleteAllDoesNotTouchOtherPrefixes(): void
	{
		$other = $this->redis('other.');
		$other->set('x', 'z');
		$redis = $this->redis();
		$redis->set('a1', 'c');
		self::assertGreaterThan(0, $redis->deleteAll());
		self::assertFalse($redis->get('a1'));
		self::assertSame('z', $other->get('x'));
	}

	public function testFlushDbDoesNotTouchOtherPrefixes(): void
	{
		$other = $this->redis('other.');
		$other->set('x', 'z');
		$redis = $this->redis();
		$redis->set('a1', 'c');
		self::assertGreaterThan(0, $redis->flushDB());
		self::assertFalse($redis->get('a1'));
		self::assertSame('z', $other->get('x'));
	}

	public function testFlushAllDoesNotTouchOtherPrefixes(): void
	{
		$other = $this->redis('other.');
		$other->set('x', 'z');
		$redis = $this->redis();
		$redis->set('a1', 'c');
		self::assertGreaterThan(0, $redis->flushAll());
		self::assertFalse($redis->get('a1'));
		self::assertSame('z', $other->get('x'));
	}

	public function testUnlinkIsChecked(): void
	{
		$redis = $this->redis(self::PREFIX, true);
		// Assuming we running redis 3.x
		self::assertFalse($redis->isUseUnlinkAll());
	}
}
