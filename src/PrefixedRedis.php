<?php declare(strict_types=1);

namespace Stefna\Redis;

class PrefixedRedis extends \Redis
{
	/** @var string */
	protected $prefix;

	/** @var bool */
	protected $useUnlinkAll;

	public function __construct(string $prefix, $preferUnlinkAll = false)
	{
		parent::__construct();
		$this->prefix = $prefix;
		$this->useUnlinkAll = $preferUnlinkAll && method_exists($this, 'unlink');
	}

	public function connect($host, $port = 6379, $timeout = 0.0, $reserved = null, $retry_interval = 0): bool
	{
		if (!$reserved) {
			$reserved = '';
		}
		$ret = parent::connect($host, $port, $timeout, $reserved, $retry_interval);
		// Must call setOption() after connect
		parent::setOption(self::OPT_PREFIX, $this->prefix);
		if ($this->useUnlinkAll) {
			$this->checkUnlink();
		}
		return $ret;
	}

	/**
	 * @inheritdoc
	 */
	public function setOption($name, $value): bool
	{
		if ($name === self::OPT_PREFIX) {
			if ($value === $this->prefix) {
				return true;
			}
			throw new \InvalidArgumentException('You can not reset the PREFIX for ' . static::class);
		}
		return parent::setOption($name, $value);
	}

	/**
	 * @inheritdoc
	 */
	public function flushDB(): bool
	{
		$this->deleteAll();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function flushAll(): bool
	{
		$this->deleteAll();
		return true;
	}

	public function deleteAll(): int
	{
		$keys = $this->getKeysWithoutPrefix();
		if ($this->useUnlinkAll) {
			/** @noinspection PhpUndefinedMethodInspection */
			return $this->unlink($keys);
		}
		return (int)$this->del($keys);
	}

	/**
	 * @return string[]
	 */
	public function getKeysWithoutPrefix(): array
	{
		$len = \strlen($this->prefix);
		return array_map(function ($value) use ($len) {
			return substr($value, $len);
		}, $this->keys('*'));
	}

	public function getPrefix(): string
	{
		return $this->prefix;
	}

	public function isUseUnlinkAll(): bool
	{
		return $this->useUnlinkAll;
	}

	protected function checkUnlink(): void
	{
		/** @var array $info */
		$info = $this->info('server');
		$version = $info['redis_version'] ?? '';
		if (!$version || version_compare($version, '4.0.0', '<')) {
			$this->useUnlinkAll = false;
		}
	}

}
