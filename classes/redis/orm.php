<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Provides a simple abstraction for working with JSON-encoded objects.
 *
 * @package    KV Store Client
 * @category   Extension
 * @author     Mongoose Metrics
 * @copyright  (c) 2011-2012 Kohana Team, (c) 2012 Mongoose Metrics
 * @license    http://kohanaphp.com/license
 */
class Redis_ORM extends KV_Store_ORM {

    protected $_expiration = NULL; // never expire by default

    protected function create_db_instance()
    {
        // Create a new Redis client
        return Redis::factory();
    }

    protected function post_save()
    {
        if ($this->_expiration != null) {
            $expires = (int) $this->_expiration;

            // Determine the key name
            $key_name = $this->_get_key_name();

            $this->_db->expire($key_name, $expires);
        };
    }

}
