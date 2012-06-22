<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Provides a simple abstraction for working with JSON-encoded objects.
 *
 * @package    KV Store Client
 * @category   Extension
 * @author     Mongoose Metrics
 * @copyright  (c) 2011-2012 Kohana Team, (c) 2012 Mongoose Metrics
 * @license    http://kohanaphp.com/license
 */
class Kyoto_Tycoon_ORM extends KV_Store_ORM {

    protected static function create_db_instance($client_name = NULL, $config = NULL) {
    {
        // Create a new Kyoto_Tycoon_Client to do the actual communication
        // with the Kyoto Tycoon server
        return Kyoto_Tycoon_Client::instance($client_name, $config);
    }

}
