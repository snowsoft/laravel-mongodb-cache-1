<?php

namespace Alfa6661\Mongodb\Cache;

use Closure;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\TaggedCache;
use Illuminate\Cache\TagSet;

class MongoStore extends DatabaseStore
{
    /**
     * Begin executing a new tags operation.
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\TaggedCache
     */
    public function tags($names)
    {
        return new TaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
    }

    /**
     * Increment or decrement an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \Closure  $callback
     * @return int|bool
     */
    protected function incrementOrDecrement($key, $value, Closure $callback)
    {
        $prefixed = $this->prefix.$key;
        $cache = $this->table()->where('key', $prefixed)->lockForUpdate()->first();

        if (is_null($cache)) {
            return false;
        }

        if (is_array($cache)) {
            $cache = (object) $cache;
        }

        $current = unserialize($cache->value);
        $new = $callback((int) $current, $value);

        if (! is_numeric($current)) {
            return false;
        }

        $this->table()->where('key', $prefixed)->update([
            'value' => serialize($new)
        ]);

        return $new;
    }

}
