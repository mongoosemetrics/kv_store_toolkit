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
    protected $_db = NULL;

    /**
     * @var  string  Holds the model name.
     */
    protected $_object_name = NULL;

    /**
     * @var  string  Holds the name of the primary key.
     */
    protected $_primary_key_name = 'id';

    /**
     * @var  string  Holds the default suffix for foreign keys.
     */
    protected $_foreign_key_suffix = '_id';

    /**
     * @var  mixed  Holds the unique primary key value for this record.
     */
    protected $_primary_key_value = NULL;

    /**
     * @var  bool  If we were able to load the data, this variable will be
     *             set to TRUE.
     */
    protected $_loaded = FALSE;

    /**
     * @var  array  Holds all of the data in the record.
     */
    protected $_object = array();

    /**
     * @var  array  Holds what we believe is the state of the object
     *              in the remote key/value store.
     */
    protected $_remote_object = array();

    /**
     * @var  array  Holds all of the belongs-to relationships.
     */
    protected $_belongs_to = array();

    /**
     * @var  array  Holds the names of top-level properties which need
     *              to be stored as separate key/value pairs in Kyoto Tycoon
     *              so that we can look up a record using a passed value.
     */
    protected $_alternate_primary_keys = array();

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
        // Initialize this model
        $this->_initialize($client_name, $config);

        // If we have an id value
        if (isset($id)) {
            // Attempt to load this record
            $this->_load($id);
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
                $this->_primary_key_value = $defaults[$primary_key_name];
            }

            // Set up the default data
            $this->_object = $defaults;
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
        $this->_object[$name] = $value;

        // If the name of the variable we are setting is the same as the
        // primary key name
        if ($name === $this->_primary_key_name) {
            // Set the primary key value
            $this->_primary_key_value = $value;
        }
    }

    /**
     * Traps any calls to get undefined virtual properties on this
     * class instance.
     *
     * @param   string  The name of the key to return.
     * @return  mixed   The data in this member variable.
     */
    public function __get($column)
    {
        // If we have an object property with the passed column
        if (array_key_exists($column, $this->_object)) {
            // Return the data from the property
            return $this->_object[$column];

        // If we have a belongs-to relationship with this column
        } elseif (isset($this->_belongs_to[$column])) {
            // Grab shortcut variables to the model name and foreign key name
            $model = $this->_belongs_to[$column]['model'];
            $foreign_key = $this->_belongs_to[$column]['foreign_key'];

            // Attempt to load and return a record using the configured model
            // name and foreign key
            return ORM::factory($model, $this->_object[$foreign_key]);
        }

        // Throw an exception
		throw new Kohana_Exception('The :property property does not exist in '.
            'the :class class.', array(':property' => $column,
            ':class' => get_class($this)));
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
        return isset($this->_object[$name]);
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
        $this->_db->set($key_name, json_encode($this->_object));

        // Update the alternate primary keys
        $this->_update_alternate_primary_keys();

        // The new data has been written, so now the remote object
        // is identical to the current object
        $this->_remote_object = (array) $this->_object;

        // Return the reference to this class instance
        return $this;
    }

    /**
     * Delete this record from Kyoto Tycoon.
     *
     * @return  object  The reference to this class instance so we can do
     *                  method chaining.
     */
    public function delete()
    {
        // Determine the key name
        $key_name = $this->_get_key_name();

        // Attempt to remove the Kyoto Tycoon record
        $this->_db->remove($key_name);

        // Remove any alternate primary keys
        $this->_remove_alternate_primary_keys();

        // Return the reference to this class instance
        return $this;
    }

    /**
     * Returns the current primary key value.
     *
     * @return  mixed  The value of the primary key.
     */
    public function pk()
    {
        // Return the value of the primary key
        return $this->_primary_key_value;
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
        return json_decode(json_encode($this->_object));
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
                throw new Kyoto_Tycoon_ORM_Exception('Property ":key" is '.
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

            } catch (Kyoto_Tycoon_Exception $exception) {
                // Return a reference to this class instance
                return $this;
            }
        }

        // Store the primary key id
        $this->_primary_key_value = $id;

        // Determine the key name
        $key_name = $this->_get_key_name();

        // Wrap the call to get the data
        try {
            // Attempt to load this record from Kyoto Tycoon
            $encoded_data = $this->_db->get($key_name);
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
        $this->_object = (array) $data;
        $this->_remote_object = (array) $data;

        // Return the reference to this class instance
        return $this;
    }

    /**
     * Prepares the model class instance.
     *
     * @param  string  Optional. The name of the Kyoto Tycoon client
     *                 instance. Defaults to NULL.
     * @param  array   Optional. Any specific configuration data to pass to
     *                 new instances of the Kyoto Tycoon client class. Also
     *                 defaults to NULL.
     * @return  null
     */
    protected function _initialize($client_name = NULL, $config = NULL)
    {
        // Determine what the object name is by removing the "Model_"
        // from the class name
        $this->_object_name = strtolower(substr(get_class($this), 6));

        // If we have no database class
        if ( ! is_object($this->_db)) {
            // Create a new Kyoto_Tycoon_Client to do the actual communication
            // with the Kyoto Tycoon server
            $this->_db = Kyoto_Tycoon_Client::instance($client_name, $config);
        }

        // Define an empty array to store the defaults in
        $defaults = array();

        // Loop over each of the belongs-to relationships
		foreach ($this->_belongs_to as $alias => $details)
		{
            // Define some defaults
			$defaults['model'] = $alias;
			$defaults['foreign_key'] = $alias.$this->_foreign_key_suffix;

            // Create the final belongs-to relationship configuration data
            // by merging the defaults with the actual data
			$this->_belongs_to[$alias] = array_merge($defaults, $details);
		}
    }

    /**
     * Updates the alternate primary keys if any are configured.
     *
     * @return  object  A reference to this class instance.
     */
    protected function _update_alternate_primary_keys()
    {
        // If there are no alternate primary keys configured
        if (empty($this->_alternate_primary_keys)) {
            // Do nothing
            return $this;
        }

        // Loop through the top-level properties in the object
        foreach ($this->_object as $name => $value) {
            // If the current property name is not an alternate primary key
            if ( ! in_array($name, $this->_alternate_primary_keys)) {
                // Move on to the next property
                continue;
            }

            // Determine the name of the alternate primary key
            $new_alternate_primary_key = $this->_get_alternate_key_name(
                $name, $value);

            // Set the new alternate primary key
            $this->_db->set($new_alternate_primary_key, (string) $this->pk());

            // Grab a shortcut reference to the remote value
            $remote_value = isset($this->_remote_object[$name]) ?
                $this->_remote_object[$name] : NULL;

            // If the value of the current alternate primary key is not
            // different then its remote value
            if ((string) $remote_value === (string) $value) {
                // Move on to the next property
                continue;
            }

            // We dont actually care if this works or not
            try {
                // Grab the previous alternate primary key name
                $old_alternate_primary_key = $this->_get_alternate_key_name(
                    $name, $remote_value);

                // Remove the old alternate key
                $this->_db->remove($old_alternate_primary_key);

            // Catch any Kyoto Tycoon exceptions
            } catch (Kyoto_Tycoon_Exception $exception) {}
        }

        // Return a reference to this class instance
        return $this;
    }

    /**
     * Removes any alternate primary keys that are configured.
     *
     * @return  object  A reference to this class instance.
     */
    protected function _remove_alternate_primary_keys()
    {
        // If there are no alternate primary keys configured
        if (empty($this->_alternate_primary_keys)) {
            // Do nothing
            return $this;
        }

        // Loop through the top-level properties in the remote object
        foreach ($this->_remote_object as $name => $value) {
            // If the current property name is not an alternate primary key
            if ( ! in_array($name, $this->_alternate_primary_keys)) {
                // Move on to the next property
                continue;
            }

            // Grab the alternate primary key name
            $alternate_primary_key = $this->_get_alternate_key_name(
                $name, $value);

            // Remove the alternate primary key
            $this->_db->remove($alternate_primary_key);
        }

        // Return a reference to this class instance
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
        // Return the Kyoto Tycoon key name
        return strtoupper($this->_object_name).'_'.((string) $this->pk());
    }

    /**
     * Returns the Kyoto Tycoon key name for the passed alternate
     * primary key property name and value.
     *
     * @param   string  The name of the alternate primary key.
     * @param   string  The value of the alternate primary key.
     * @return  string  The Kyoto Tycoon key name.
     */
    protected function _get_alternate_key_name($name, $value)
    {
        // Return the Kyoto Tycoon key name
        return strtoupper($this->_object_name.'_'.$name).'_'.sha1($value);
    }

} // End Kyoto_Tycoon_ORM
