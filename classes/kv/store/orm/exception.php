<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Simple extension Kohana_Exception. Allows us to use the PHP 'instanceof'
 * keyword to determine the type of exception that occurred.
 *
 * @package    KV Store Client
 * @category   Extension
 * @author     Mongoose Metrics
 * @copyright  (c) 2011-2012 Kohana Team, (c) 2012 Mongoose Metrics
 * @license    http://kohanaphp.com/license
 */
class KV_Store_ORM_Exception extends KV_Store_Exception {}
