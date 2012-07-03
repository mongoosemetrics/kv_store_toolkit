<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles the task of managing a single named FIFO queue in Redis (using lists)
 *
 * @package    KV Store Client
 * @category   Extension
 * @author     Mongoose Metrics
 * @copyright  (c) 2011-2012 Kohana Team, (c) 2012 Mongoose Metrics
 * @license    http://kohanaphp.com/license
 */
class Redis_Queue extends KV_Store_Queue {

    const PREFIX_QUEUE_PROCESSING = 'QUEUE_PROCESSING_';

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
        $client = Redis::factory();

        try {
            $json = $client->rpoplpush($this->_get_list_name(), $this->_get_processing_list_name());
            $data = json_decode($json);
            return $data;
        } catch (Exception $ex) {
            return null;
        };
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
        $client = Redis::factory();
        $client -> lpush( $this->_get_list_name(), json_encode($data) );
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
     * Returns the full name of the list using the instance name.
     *
     * @return  string  The prefix for all of the keys required to represent
     *                  this queue.
     */
    protected function _get_list_name()
    {
        // Return the prefix for all of the keys
        return static::PREFIX_QUEUE.strtoupper($this->_name);
    }

    /**
     * Returns the full name of the processing list using the instance name.
     *
     * @return  string  The prefix for all of the keys required to represent
     *                  this queue.
     */
    protected function _get_processing_list_name()
    {
        // Return the prefix for all of the keys
        return static::PREFIX_QUEUE_PROCESSING.strtoupper($this->_name);
    }

} // End Redis_Queue
