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

    public function factory($orm_name, $foreign_name, $fk = '')
    {
        return new Redis_Index($orm_name, $foreign_name, $fk);
    }

    protected function __construct($orm_name, $foreign_name, $fk)
    {
        $this->_orm_name = $orm_name;
        $this->_foreign_name = $foreign_name;
        $this->_fk = $fk;
    }

    protected $_orm_name;
    protected $_foreign_name;
    protected $_fk;

    protected function _get_key_name()
    {
        $_orm_name = $this->_orm_name;
        $_foreign_name = $this->_foreign_name;
        $_fk = $this->_fk;

        // Construct an index referencing this object using a foreign key for another entity

        // Example, index of inventory actions by campaign code
        // S:I:CAMPAIGN:<code>::INVENTORY_ACTION => set( )

        return strtoupper(
            'S:I:' .        // This is a set used as an index.
            $_foreign_name  // This is the object we are using to reach this object (index object).
            $_fk .          // This is the value of the foreign key in this object
                            // cooresponding to the index object.
            $_orm_name .    // This is the object we want to reach
        );

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
