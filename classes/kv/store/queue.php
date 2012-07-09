<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles the task of managing a single named FIFO queue in a key value store.
 *
 * @package    KV Store Client
 * @category   Extension
 * @author     Mongoose Metrics
 * @copyright  (c) 2011-2012 Kohana Team, (c) 2012 Mongoose Metrics
 * @license    http://kohanaphp.com/license
 */
abstract class KV_Store_Queue {

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

    // Constants for error codes
    const ERROR_EMPTY_QUEUE = 404;

    /**
     * Sets up a queue abstraction for the passed key name.
     *
     * @param  string  The name of the queue.
     *
     */
    public function __construct($name)
    {
        // Set the queue name
        $this->_name = $name;
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
    public function shift()
    {
        /* TODO: implement */
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
    public function shift_work($timeout)
    {
        /* TODO: implement */
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
        /* TODO: implement */
    }

    /**
     * Returns an object with queue status information.
     *
     * @return  object  An object with key/value pairs.
     */
    public function status()
    {
        /* TODO: implement */
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
        return static::PREFIX_QUEUE.strtoupper($this->_name);
    }

} // End Kyoto_Tycoon_Queue
