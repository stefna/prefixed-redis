# Prefixed Redis client

A \Redis implementation that has better prefix support.

## Usage
```php
$redis = new \Stefna\Redis\PrefixedRedis('prefix.');
$redis->set('a', 'b'); // sets "prefix.a" to "b"
$redis->flushDb(); // removes all keys in database with keys like 'prefix.*'
// Same as:
$redis->deleteAll();
// And (for now at least)
$redis->flushAll(); // Does not run on all databases (only current) 
```
