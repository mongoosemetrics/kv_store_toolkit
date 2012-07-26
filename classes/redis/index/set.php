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
class Redis_Index_Set extends Redis_ORM {

    public function __construct($orm, $foreign)
    {
        $this->_orm = $orm;
        $this->_pk = $orm->_primary_key_name;

        if (is_object($foreign)) {
            // assume foreign is an orm
            $this->_fk = $foreign->_primary_key_name;
        } else {
            $this->_fk = $foreign;
        };

    }

    protected $_orm;

    protected $_pk;
    protected $_fk;

    protected function _get_key_name()
    {
        $_object_name = $this->_orm->_object_name;
        $name = $this->_fk;
        $value = $orm->$name;

        // Construct an index referencing this object using a foreign key for another entity
        return strtoupper('S:I:'.$_object_name.':'.$name.':'.$value.'::'.$this->_orm->_primary_key_name);

    }

    /* get all indexed values */
    public function all()
    {
        return parent::members();
    }

    /* delete existing index, optionally using the old value */
    public function delete($old = NULL)
    {
    }

    public function update()
    {
        return $this;
    }

}
