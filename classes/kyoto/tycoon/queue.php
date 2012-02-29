<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles the task of managing a single named FIFO queue in Kyoto Tycoon.
 *
 * @package    Kohana/Kyoto Tycoon Toolkit
 * @category   Extension
 * @author     Kohana Team
 * @copyright  (c) 2011-2012 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kyoto_Tycoon_Queue {

    /**
     * @var  string  Holds the queue name.
     */
    protected $_name = NULL;

    /**
     * @var  object  Holds a reference to the Kyoto_Tycoon_Client class
     *               we use to do the actual communication with the Kyoto
     *               Tycoon server.
     */
    protected $_client = NULL;

    // Constants for key prefixes and suffixes
    const PREFIX_QUEUE = 'QUEUE_';
    const SUFFIX_READ = '_READ';
    const SUFFIX_WRITE = '_WRITE';
    const SUFFIX_INDEX_SEPARATOR = '_';

    // Constants for error codes
    const ERROR_EMPTY_QUEUE = 404;

    /**
     * Sets up a queue for
     *
     * @param  string  The name of the queue.
     * @param  string  Optional. The name of the Kyoto_Tycoon_Client instance
     *                 to make the actual calls. Defaults to 'default'.
     * @param  array   Optional. An array of configuration data to pass to
     *                 the Kyoto_Tycoon_Client class. Defaults to NULL.
     */
    public function __construct($name, $client_name = NULL, $config = NULL)
    {
        // Set the queue name
        $this->_name = $name;

        // Create a new Kyoto_Tycoon_Client to do the actual communication with
        // the Kyoto Tycoon server
        $this->_client = Kyoto_Tycoon_Client::instance($client_name, $config);
    }

    /**
     * Attempts to shift the next item from the queue.
     *
     * @param   int     Optional. The number of seconds we will continue to
     *                  try to get the lock. Defaults to 0 seconds.
     * @return  object  An object with a 'found' and a 'data' member. If the
     *                  'found' member is TRUE, then we have data to process
     *                  in the 'data' member.
     */
    public function shift($lock_timeout = 0)
    {
        // Attempt to get the lock
        $this->_client->_lock($this->_get_key_prefix(), $lock_timeout);

        // Grab the current read and write positions
        $read_position = $this->_get_read_position();
        $write_position = $this->_get_write_position();

        // If the current read position is the same (or greater than :O) the
        // current write position
        if ($read_position >= $write_position) {
            // Reset the read and write positions
            $this->_reset_read_and_write_positions();

            // Remove the lock
            $this->_client->_unlock($this->_get_key_prefix());

            // Throw an exception
            throw new Kyoto_Tycoon_Queue_Exception('No data in queue.', NULL,
                self::ERROR_EMPTY_QUEUE);
        }

        // Increment the read position
        $read_position = $this->_increment_read_position();

        // Remove the lock
        $this->_client->_unlock($this->_get_key_prefix());

        // Determine the name of the data key
        $key_name = $this->_get_key_prefix().
            self::SUFFIX_INDEX_SEPARATOR.((string) $read_position);

        // Sieze the data
        $data = $this->_client->seize($key_name);

        // Return the data
        return $data;
    }

    /**
     * Attempts to push a new item into the queue.
     *
     * @param   string  The data to push into the queue.
     * @param   int     Optional. The number of seconds we will continue to
     *                  try to get the lock. Defaults to 30 seconds.
     * @return  object  The instance of this class so we can do
     *                  method chaining.
     */
    public function push($data, $lock_timeout = 30)
    {
        // Attempt to get the lock
        $this->_client->_lock($this->_get_key_prefix(), $lock_timeout);

        // Increment the write position
        $write_position = $this->_increment_write_position();

        // Determine the name of the data key
        $key_name = $this->_get_key_prefix().
            self::SUFFIX_INDEX_SEPARATOR.((string) $write_position);

        // Set the data
        $this->_client->set($key_name, $data);

        // Remove the lock
        $this->_client->_unlock($this->_get_key_prefix());

        // Return the instance of this class
        return $this;
    }

    /**
     * Returns an object with queue status information.
     *
     * @return  object  An object with key/value pairs.
     */
    public function status()
    {
        // Grab the current read and write positions
        $read_position = $this->_get_read_position();
        $write_position = $this->_get_write_position();

        // Return information about the queue
        return (object) array(
            'read_position' => $read_position,
            'write_position' => $write_position,
            'queue_count' => $write_position - $read_position,
        );
    }

    /**
     * Returns the full key prefix using the instance name.
     *
     * @return  string  The prefix for all of the keys required to represent
     *                  this queue.
     */
    protected function _get_key_prefix()
    {
        // Return the prefix for all of the keys
        return self::PREFIX_QUEUE.strtoupper($this->_name);
    }

    /**
     * Returns the current read position in the queue.
     *
     * @return  int  The current read position.
     */
    protected function _get_read_position()
    {
        // Determine the name of the key
        $key_name = $this->_get_key_prefix().self::SUFFIX_READ;

        // Return the current read position as an integer. If it is not
        // already defined, it will be set to 0
        return (int) $this->_client->increment($key_name, 0, 0);
    }

    /**
     * Returns the current write position in the queue.
     *
     * @return  int  The current write position.
     */
    protected function _get_write_position()
    {
        // Determine the name of the key
        $key_name = $this->_get_key_prefix().self::SUFFIX_WRITE;

        // Return the current write position as an integer. If it is not
        // already defined, it will be set to 0
        return (int) $this->_client->increment($key_name, 0, 0);
    }

    /**
     * Increments and returns the read position in the queue.
     *
     * @return  int  The current read position.
     */
    protected function _increment_read_position()
    {
        // Determine the name of the key
        $key_name = $this->_get_key_prefix().self::SUFFIX_READ;

        // Increment the read position and return it as integer
        return (int) $this->_client->increment($key_name, 1, 0);
    }

    /**
     * Increments and returns the write position in the queue.
     *
     * @return  int  The current write position.
     */
    protected function _increment_write_position()
    {
        // Determine the name of the key
        $key_name = $this->_get_key_prefix().self::SUFFIX_WRITE;

        // Increment the write position and return it as integer
        return (int) $this->_client->increment($key_name, 1, 0);
    }

    /**
     * Resets the read and write positions.
     *
     * @return  object  The instance of this class so we can do
     *                  method chaining.
     */
    protected function _reset_read_and_write_positions()
    {
        // Grab the key prefix
        $key_prefix = $this->_get_key_prefix();

        // Reset the read and write positions
        $this->_client->remove($key_prefix.self::SUFFIX_READ);
        $this->_client->remove($key_prefix.self::SUFFIX_WRITE);

        // Return the instance of this class
        return $this;
    }

} // End Kyoto_Tycoon_Queue
