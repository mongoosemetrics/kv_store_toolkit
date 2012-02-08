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
     * @var  string  The default instance name.
     */
    public static $default = 'default';

    /**
     * @var  array  References to all of the client instances.
     */
    public static $instances = array();

    /**
     * Get a singleton object instance of this class. If configuration is not
     * specified, it will be loaded from the kyoto configuration file using
     * the same group as the provided name.
     *
     *     // Load the default client instance
     *     $client = Kohana_Tycoon_Queue::instance();
     *
     *     // Create a custom configured instance
     *     $client = Kohana_Tycoon_Queue::instance('custom', $config);
     *
     * @param   string   instance name
     * @param   array    configuration parameters
     * @return  Kyoto_Tycoon_Client
     */
    public static function instance($name = NULL, $config = NULL)
    {
        if ($name === NULL)
        {
            // Use the default instance name
            $name = self::$default;
        }

        if ( ! isset(self::$instances[$name]))
        {
            // Create the queue instance
            new Kyoto_Tycoon_Queue($name, $config);
        }

        return self::$instances[$name];
    }

    /**
     * @var  string  Holds the instance name.
     */
    protected $_instance = NULL;

    /**
     * @var  object  Holds a reference to the Kyoto_Tycoon_Client class
     *               we use to do the actual communication with the Kyoto
     *               Tycoon server.
     */
    protected $_client = NULL;

    /**
     * Stores the client configuration locally and names the instance.
     *
     * [!!] This method cannot be accessed directly, you must use [Kyoto_Tycoon_Client::instance].
     *
     * @return  void
     */
    protected function __construct($name, array $config)
    {
        // Set the instance name
        $this->_instance = $name;

        // Store the config locally
        $this->_config = $config;

        // Store this client instance
        self::$instances[$name] = $this;

        // Create a new Kyoto_Tycoon_Client to do the actual communication with
        // the Kyoto Tycoon server
        $this->_client = Kyoto_Tycoon_Client::instance($name, $config);
    }

    /**
     * Returns the current read position.
     *
     *
     */

} // End Kyoto_Tycoon_Queue
