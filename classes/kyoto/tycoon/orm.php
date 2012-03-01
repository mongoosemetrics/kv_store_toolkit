<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Provides a simple abstraction for working with JSON-encoded objects.
 *
 * @package    Kohana/Kyoto Tycoon Toolkit
 * @category   Extension
 * @author     Kohana Team
 * @copyright  (c) 2011-2012 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kyoto_Tycoon_ORM {

    /**
     * @var  object  Holds a reference to the Kyoto_Tycoon_Client class
     *               we use to do the actual communication with the Kyoto
     *               Tycoon server.
     */
    protected $_client = NULL;

    /**
     * @var  string  Holds the name of the primary key.
     */
    protected $_primary_key_name = 'id';

    /**
     * @var  mixed  Holds the unique primary key value for this record.
     */
    protected $_id = NULL;

    /**
     * @var  bool  If we were able to load the data, this variable will be
     *             set to TRUE.
     */
    protected $_loaded = FALSE;

    /**
     * @var  array  Holds all of the data in the record.
     */
    protected $_data = array();

    /**
     * Sets up this ORM record, including any optional Kyoto Tycoon server
     * configuration.
     *
     * @param  int     Optional. The unique primary key of the record to load.
     *                 Defaults to NULL.
     * @param  string  Optional. The name of the Kyoto Tycoon client
     *                 instance. Defaults to NULL.
     * @param  array   Optional. Any specific configuration data to pass to
     *                 new instances of the Kyoto Tycoon client class. Also
     *                 defaults to NULL.
     */
    public function __construct($id = NULL, $client_name = NULL, $config = NULL)
    {
        // Create a new Kyoto_Tycoon_Client to do the actual communication with
        // the Kyoto Tycoon server
        $this->_client = Kyoto_Tycoon_Client::instance($client_name, $config);

        // Store the passed id
        $this->_id = $id;

        // If we have an id value
        if (isset($this->_id)) {
            // Attempt to load this record
            $this->_load();
        // If this is a new record
        } else {
            // Grab the defaults and cast them to an object so we know what
            // syntax to use
            $defaults = (array) $this->_get_defaults();

            // Grab a shortcut reference to the name of the primary key
            $primary_key_name = $this->_primary_key_name;

            // If the primary key is one of the defaults
            if (isset($defaults[$primary_key_name])) {
                // Copy the primary key value
                $this->_id = $defaults[$primary_key_name];
            }

            // Set up the default data
            $this->_data = $defaults;
        }
    }

    /**
     * Reloads the data from Kyoto Tycoon.
     *
     * @return  object  The reference to this class instance so we can do
     *                  method chaining.
     */
    public function reload()
    {
        // Attempt to re-load the data from Kyoto Tycoon
        $this->_load();

        // Return the reference to this class instance
        return $this;
    }

    /**
     * Traps any calls to set undefined virtual properties on this
     * class instance.
     *
     * @param   string  The name of the key to set.
     * @param   string  The value to assign.
     * @return  void
     */
    public function __set($name, $value)
    {
        // Set the data
        $this->_data[$name] = $value;

        // If the name of the variable we are setting is the same as the
        // primary key name
        if ($name === $this->_primary_key_name) {
            // Set the primary key value
            $this->_id = $value;
        }
    }

    /**
     * Traps any calls to get undefined virtual properties on this
     * class instance.
     *
     * @param   string  The name of the key to return.
     * @return  mixed   The data in this member variable.
     */
    public function __get($name)
    {
        // Return the data, if it is available
        return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
    }

    /**
     * Traps any calls using isset on undefined virtual properties on this
     * class instance.
     *
     * @param   string  The name of the key to check using isset.
     * @return  mixed   The data in this member variable.
     */
    public function __isset($name)
    {
        // Return the result of isset on the passed key name
        return isset($this->_data[$name]);
    }

    /**
     * This function is intended to be overridden by child class definitions.
     *
     * @return  array  A collection of column-name => column-value pairs to use
     *                 as the default values for new records.
     */
    protected function _get_defaults()
    {
        // Return an empty array
        return array();
    }

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

        // Attempt to JSON-encode and save this records data
        $this->_client->set($key_name, json_encode($this->_data));

        // Return the reference to this class instance
        return $this;
    }

    /**
     * Returns boolean TRUE if the record was successfully loaded.
     *
     * @return  bool  If we loaded the record, TRUE.
     */
    public function loaded()
    {
        // Return if we were loaded or not
        return $this->_loaded;
    }

    /**
     * Returns a copy of all of the data on this record.
     *
     * @return  object  An object with all of the data on this record.
     */
    public function export()
    {
        // Return a copy of the data cast into an object
        return json_decode(json_encode($this->_data));
    }

    /**
     * Attempts to load the data from Kyoto Tycoon.
     *
     * @return  object  The reference to this class instance so we can do
     *                  method chaining.
     */
    protected function _load()
    {
        // Determine the key name
        $key_name = $this->_get_key_name();

        // Set the loaded flag to false
        $this->_loaded = FALSE;

        // Erase the data on this record
        $this->_data = array();

        // Wrap the call to get the data
        try {
            // Attempt to load this record from Kyoto Tycoon
            $encoded_data = $this->_client->get($key_name);
        // Catch any exceptions
        } catch (Kyoto_Tycoon_Exception $exception) {
            // Return a reference to this class instance
            return $this;
        }

        // Attempt to deserialize (what should be) the JSON-encoded value
        $data = json_decode($encoded_data);

        // If the attempt to decode failed
        if ($data === NULL AND $encoded_data !== 'null') {
            // Throw an exception
            throw new Kyoto_Tycoon_ORM_Exception('JSON decode failure.',
                NULL, 500);
        }

        // If we made it down here, we were able to load the record
        $this->_loaded = TRUE;

        // Store the loaded data
        $this->_data = (array) $data;

        // Return the reference to this class instance
        return $this;
    }

    /**
     * Returns the Kyoto Tycoon key name using the class name and the unique
     * id assigned to this object.
     *
     * @return  string  The Kyoto Tycoon key name.
     */
    protected function _get_key_name()
    {
        // Grab the class name
        $class_name = $this->_get_class_name();

        // Break the local class name apart on the underscores
        $class_name_parts = explode('_', $class_name);

        // Remove the first word
        array_shift($class_name_parts);

        // Re-join the remaining parts and use that as the key name prefix
        $key_name_prefix = implode('_', $class_name_parts);

        // Convert the key name prefix to all uppercase
        $key_name_prefix = strtoupper($key_name_prefix);

        // Return the Kyoto Tycoon key name
        return $key_name_prefix.'_'.((string) $this->_id);
    }

    /**
     * Returns the all-lowercase version of this class instances class name.
     *
     * @return  string  The class name for this class instance, in lowercase.
     */
    protected function _get_class_name()
    {
        // Return this class instances name
        return get_class($this);
    }

} // End Kyoto_Tycoon_ORM
