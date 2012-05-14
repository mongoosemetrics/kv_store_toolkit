<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Wraps the low-level Client in a Cursor for multiple-key operations.
 *
 * @package   Kohana/Kyoto Tycoon Toolkit
 * @category   Extension
 * @author    Mongoose Metrics
 * @copyright  (c) 2012 Mongoose Metrics
 * @license   http://kohanaphp.com/license
 */
class Kyoto_Tycoon_Cursor {

   /**
    * @var  string  The default instance name.
    */
   public static $default = 'default';
   
   /**
    * Get a singleton object instance of this class. If configuration is not
    * specified, it will be loaded from the kyoto configuration file using
    * the same group as the provided name.
    *
    * This will also create a client instance.
    *
    *    // Load the default client instance
    *    $cursor = Kohana_Tycoon_Cursor::instance();
    *
    *    // Create a custom configured instance
    *    $cursor = Kohana_Tycoon_Client::instance('custom', $config);
    *
    * @param   string   instance name
    * @param   array   configuration parameters
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
         // Create the cursor instance
         new Kyoto_Tycoon_Cursor($name, $config);
      }

      return self::$instances[$name];

   }

   /**
    * @var  string  Holds the instance name.
    */
   protected $_instance = NULL;

   /**
    * @var  Kyoto_Tycoon_client  The client used with this cursor
    */
   private $_client = null;
   
   protected function __construct($name, array $config)
   {
      // Set the instance name
      $this->_instance = $name;

      // Store this cursor instance
      self::$instances[$name] = $this;   


      $this->_client = new Kyoto_Tycoon_Client($name, $config);
   }

   protected static function createCursor()
   {
   
      return rand() * 100000000;
   
   }

   public function listKeys($keyName = '', $maxResults = 50)
   {
   
      $continue = true;
      
      $this->_cursor = self::createCursor();
      
      // Intialize our cursor
      
      try {
         $this->_client->_rpc('cur_jump', array('CUR' => $this->_cursor));
      } catch (Kyoto_Tycoon_Exception $ex) {
         return false;
      };

      $remaining = $maxResults;
      $results = array();

      while ($continue) {
      
         $opts = array(
            'CUR' => $this->_cursor
           );

         try {
            $response = $this->_client->_rpc('cur_get', $opts);
         } catch (Kyoto_Tycoon_Exception $ex) {
            return false;
         };

         $key = array_key_exists('key', $response) ?
            $response : '';

         $value = array_key_exists('value', $response) ?
            $response : '';

         $results[] = array(
            'key' => $key,
            'value' => $value
         );
         
         $remaining--;
         if ($remaining == 0) {
            $continue = false;
         };
      
      
      };
      
      return $results;
   
   }

   public static function test_list_keys()
   {
      $cur = self::instance();
      
      $results = $cur->listKeys('', 50);
      
      print_r($results);
      
   }     
}

