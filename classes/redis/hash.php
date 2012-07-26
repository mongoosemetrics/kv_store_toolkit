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
class Redis_Hash extends Redis_ORM {

    /**
     * Saves this record to Kyoto Tycoon.
     *
     * @return  object  The reference to this class instance so we can do
     *                  method chaining.
     */
    public function save()
    {
        // Determine the key name
        $key_name = $this->_get_key_name();

        $this->_update_hash($key_name, $this->_object);

        // Update the alternate primary keys
        $this->_update_alternate_primary_keys();

        // The new data has been written, so now the remote object
        // is identical to the current object
        $this->_remote_object = (array) $this->_object;

        // If there is a post_save hook, call it
        if (method_exists($this, 'post_save')) $this->post_save();

        // Return the reference to this class instance
        return $this;
    }

    protected function _update_hash($key_name, $values = array())
    {
        $values = (object) $values;
        $_hash_values = array();

        foreach($values as $field=>$value) {

            // Use JSON encoding for all children
            $_hash_values[$field] = json_encode($value);

            //$this->_db->hset($key_name, $field, json_encode($value));
        };
        $this->_db->hmset($key_name, $_hash_values);
    }

    /**
     * Attempts to load the data from Kyoto Tycoon.
     *
     * @param   mixed   The primary key, or alternate primary key value pair
     *                  to use to load this record.
     * @return  object  The reference to this class instance so we can do
     *                  method chaining.
     */
    protected function _load($id)
    {
        // Set the loaded flag to false
        $this->_loaded = FALSE;

        // Erase the data on this record
        $this->_object = array();
        $this->_remote_object = array();

        // If an array or object was passed in
        if (is_array($id) OR is_object($id)) {
            // Cast the passed key/value pair structure into an array so that
            // we know what syntax to use, and make the keys all lowercase
            $id = (array) $id;

            // Grab the keys and values
            $keys = array_keys($id);
            $values = array_values($id);

            // Only use the first key/value pair
            $key = array_shift($keys);
            $value = array_shift($values);

            // If the key is not a configured alternate primary key name
            if ( ! in_array($key, $this->_alternate_primary_keys)) {
                // Throw an exception
                throw new KV_Store_ORM_Exception('Property ":key" is '.
                    'not a valid alternate primary key.', array(
                    ':key' => $key));
            }

            // Determine what the alternate primary key name should be
            $alternate_primary_key = $this->_get_alternate_key_name(
                $key, $value);

            try {
                // Attempt to determine the actual id value using
                // the alternate primary key name
                $id = $this->_db->get($alternate_primary_key);

            } catch (KV_Store_Exception $exception) {
                // Return a reference to this class instance
                return $this;
            }
        }

        // Store the primary key id
        $this->_primary_key_value = $id;

        // Determine the key name
        $key_name = $this->_get_key_name();

        // Get all the members of a set with key_name if it exists
        $_hash_values = $this->_db->hgetall($key_name);

        if (empty($_hash_values)) {
            // We didn't find anything
            return $this;
        };

        $data = new stdClass;
        foreach($_hash_values as $field=>$value) {
            $encoded_field = trim($value);

            try {
                $field_data = json_decode($encoded_field);
            } catch (Exception $ex) {
                // Return this chained object
                return $this;
            }        

            $data->$field = $field_data;
        };

        // If we made it down here, we were able to load the record
        $this->_loaded = TRUE;

        // Store the loaded data
        $this->_object = (array) $data;
        $this->_remote_object = (array) $data;

        // Return the reference to this class instance
        return $this;
    }

    /**
     * Returns the KV store key name using the class name and the unique
     * id assigned to this object, prefixed with H: for hash.
     *
     * @return  string  The key name
     */
    protected function _get_key_name()
    {
        return 'H:'.parent::_get_key_name();
    }

}
