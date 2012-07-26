<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Provides a simple abstraction for working with JSON-encoded objects, with the top level
 * being a Redis hash.
 *
 * @package    KV Store Client
 * @category   Extension
 * @author     Mongoose Metrics
 * @copyright  (c) 2011-2012 Kohana Team, (c) 2012 Mongoose Metrics
 * @license    http://kohanaphp.com/license
 */
class Redis_Set extends Redis_ORM {

    public function is_member($value)
    {
        return $this->_db->sismember($this->_get_key_name, $value);
    }

    public function remove($value)
    {
        return $this->_db->srem($this->_get_key_name, $value);
    }

    /* get all members of the set */
    public function members()
    {
        return $this->_db->members($this->_get_key_name);
    }

    /**
     * Returns the KV store key name using the class name and the unique
     * id assigned to this object, prefixed with H: for hash.
     *
     * @return  string  The key name
     */
    protected function _get_key_name()
    {
        return 'S:'.parent::_get_key_name();
    }

}
