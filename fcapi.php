<?php defined('KO_SYSPATH') or die('No direct script access.');
/**
 * Acmecart Ecommerce.
 *
 * @package		Acmecart
 * @author		HCCDevelopment - Brian Greenacre
 * @copyright	Copyright (c) 2010, HCCDevelopment, Inc.
 * @link		http://www.hccdevelopment.com
 * @license		http://www.hccdevelopment.com/Web-Based-Software/Legal_Information/
 */

// ------------------------------------------------------------------------

/**
 * FoxyCart API class.
 *
 * This class executes FoxyCart API calls.
 *
 * @package		Foxycart
 * @category	Libraries
 * @author		Brian Greenacre
 * @link		http://wiki.foxycart.com/v/0.7.0/advanced/api
 */

class Libraries_Gateway_Foxycart_Api
{
	/**
	 * FoxyCart endpoint url.
	 *
	 * @access	public
	 * @var		string
	 */
	public $endpoint = 'https://YOURDOMAIN.foxycart.com/api';

	/**
	 * FoxyCart api token.
	 *
	 * @access	public
	 * @var		string
	 */
	public $api_token;

	/**
	 * The payload to post to API.
	 *
	 * @access	private
	 * @var		string
	 */
	private $_payload;

	/**
	 * Array of errors that may occur.
	 *
	 * @access	public
	 * @var		array
	 */
	public $errors;

	/**
	 * Status of the result of the call.
	 *
	 * @access	public
	 * @var		string
	 */
	public $status;

	/**
	 * Result of the call.
	 *
	 * @access	public
	 * @var		mixed	FALSE on error else stdClass object
	 */
	public $result;

	/**
	 * Message in the result.
	 *
	 * @access	public
	 * @var		string
	 */
	public $message;
	
	/**
	 * Array CURL options.
	 *
	 * @access	private
	 * @var		array
	 */
	private $curl_options = array(
		CURLOPT_POST				=> TRUE
		, CURLOPT_SSL_VERIFYPEER	=> FALSE
		, CURLOPT_SSL_VERIFYHOST	=> FALSE
		, CURLOPT_HEADER			=> FALSE
		, CURLOPT_TIMEOUT			=> 30
		, CURLOPT_CONNECTTIMEOUT	=> 5
		);
	
	/**
	 * Singleton object.
	 *
	 * @access	private
	 * @var		object
	 */
	private static $_instance;
	
	/**
	 * __construct - Construtor method sets up enpoint and api_token properties.
	 * Calls the $this->initialize() method.
	 *
	 * @access	public
	 * @param	string	Domain name of the foxycart site
	 * @param	string	Api key
	 * @return	void
	 */
	final public function __construct($endpoint = NULL, $api_token = NULL)
	{
		if ($endpoint === NULL OR $api_token === NULL) {
			return;
		}
		
		$this->set_endpoint($endpoint);
		$this->api_token = $api_token;
		$this->initialize();
	}
	
	/**
	 * __sleep - Magic method to tell the serialize call to only
	 * serialize certain properties of this object.
	 *
	 * @access	public
	 * @return	array	Array of property names
	 */
	public function __sleep()
	{
		return array('endpoint', 'api_token', '_payload', 'errors', 'status', 'result', 'message', 'curl_options');
	}
	
	/**
	 * instance - Instantiate a singelton object of this class.
	 *
	 * @access	public
	 * @param	string	Domain name of the foxycart site
	 * @param	string	Api key
	 * @return	object
	 */
	public static function instance($endpoint = NULL, $api_token = NULL)
	{
		if ( ! is_object(self::$_instance)) {
			self::$_instance = new self($endpoint, $api_token);
		}
		
		return self::$_instance;
	}
	
	/**
	 * initialize - Initialize the object properties for a new call to
	 * FoxyCart API.
	 *
	 * @access	public
	 * @return	void
	 */
	public function initialize()
	{
		$this->_payload = NULL;
		$this->errors = array();
		$this->status = NULL;
		$this->result = FALSE;
		$this->message = NULL;
	}
	
	/**
	 * customer_get - Get a customer from FoxyCart API.
	 *
	 * @access	public
	 * @param	int		FoxyCart customer id
	 * @param	string	FoxyCart customer email address
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function customer_get($customer_id = 0, $email = NULL)
	{
		$this->_add_to_payload('api_action', 'customer_get');
		
		if ($customer_id > 0) {
			$this->_add_to_payload('customer_id', (int) $customer_id);
		} elseif ($email !== NULL) {
			$this->_add_to_payload('customer_email', $email);
		}
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * customer_save - Save data to a FoxyCart Customer.
	 *
	 * @access	public
	 * @param	int		FoxyCart customer id
	 * @param	string	FoxyCart customer email address
	 * @param	array	Array of data to save to the customer
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function customer_save($customer_id = 0, $email = NULL, array $data = NULL)
	{
		$this->_add_to_payload('api_action', 'customer_save');
		
		if ($customer_id > 0) {
			$this->_add_to_payload('customer_id', (int) $customer_id);
		} elseif ($email !== NULL) {
			$this->_add_to_payload('customer_email', $email);
		}
		
		if(is_array($data) === TRUE AND ! empty($data)) {
			$this->_add_to_payload($data);
		}
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * customer_list - List customers in FoxyCart and optionally apply filters.
	 *
	 * @access	public
	 * @param	array	Array of filters to apply
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function customer_list(array $data = NULL)
	{
		$valid_filters = array(
			'customer_id_filter'			=> NULL
			, 'customer_email_filter'		=> NULL
			, 'customer_first_name_filter'	=> NULL
			, 'customer_last_name_filter'	=> NULL
			, 'customer_state_filter'		=> NULL
			, 'pagination_start'			=> 1
			);
		
		$data = array_intersect_key((array) $data, $valid_filters);
		$this
			->_add_to_payload('api_action', 'transaction_list')
			->_add_to_payload($data);
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * customer_address_get - Get an address of a FoxyCart customer.
	 *
	 * @access	public
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function customer_address_get($customer_id = 0, $email = NULL)
	{
		$this->_add_to_payload('api_action', 'customer_address_get');
		
		if ($customer_id > 0) {
			$this->_add_to_payload('customer_id', $customer_id);
		} elseif ($email !== NULL) {
			$this->_add_to_payload('customer_email', $email);
		}
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * customer_address_save - Save an address for a FoxyCart customer.
	 *
	 * @access	public
	 * @param	array	Array of data to save
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function customer_address_save($customer_id = 0, $email = NULL, & $data = array())
	{
		$this->_add_to_payload('api_action', 'customer_address_save');
		
		if ($customer_id > 0) {
			$this->_add_to_payload('customer_id', $customer_id);
		} elseif ($email !== NULL) {
			$this->_add_to_payload('customer_email', $email);
		}
		
		if(is_array($data) === TRUE AND ! empty($data)) {
			$this->_add_to_payload($data);
		}
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * transaction_get - Get a transaction from FoxyCart by ID.
	 *
	 * @access	public
	 * @param	int		Transaction ID
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function transaction_get($transaction_id = 0)
	{
		$this->_add_to_payload(array(
			'api_action'		=> 'transaction_get',
			'transaction_id'	=> $transaction_id
			));
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * transaction_list - List out transactions from FoxyCart.
	 *
	 * @access	public
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function transaction_list(array $filters = NULL)
	{
		$valid_filters = array(
			'transaction_date_filter_begin'	=> NULL
			, 'transaction_date_filter_end'	=> NULL
			, 'is_test_filter'				=> NULL
			, 'hide_transaction_filter'		=> NULL
			, 'data_is_fed_filter'			=> NULL
			, 'id_filter'					=> NULL
			, 'order_total_filter'			=> NULL
			, 'coupon_code_filter'			=> NULL
			, 'customer_id_filter'			=> NULL
			, 'customer_email_filter'		=> NULL
			, 'customer_first_name_filter'	=> NULL
			, 'customer_last_name_filter'	=> NULL
			, 'customer_state_filter'		=> NULL
			, 'shipping_state_filter'		=> NULL
			, 'customer_ip_filter'			=> NULL
			, 'product_code_filter'			=> NULL
			, 'product_name_filter'			=> NULL
			, 'product_option_name_filter'	=> NULL
			, 'product_option_value_filter'	=> NULL
			, 'pagination_start'			=> 1
			);
		
		$data = array_intersect_key((array) $data, $valid_filters);
		$this
			->_add_to_payload('api_action', 'transaction_list')
			->_add_to_payload($data);
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * subscription_get - Get a subscription based on a token.
	 *
	 * @access	public
	 * @param	string	Subscription token
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function subscription_get($token = NULL)
	{
		$this->_add_to_payload(array(
			'api_action'	=> 'subscription_get',
			'sub_token'		=> $token
			));
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * subscription_cancel - Cancel subscription based on a token.
	 *
	 * @access	public
	 * @param	string	Subscription token
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function subscription_cancel($token = NULL)
	{
		$this->_add_to_payload(array(
			'api_action'	=> 'subscription_cancel',
			'sub_token'		=> $token
			));
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * subscription_modify - Modify a subscription in FoxyCart.
	 *
	 * @access	public
	 * @param	string	Subscription token
	 * @param	array	Array of data to save to subscription
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function subscription_modify($token = NULL, array $data = NULL)
	{
		$valid_fields = array(
			'start_date'				=> NULL
			, 'end_date'				=> NULL
			, 'next_transaction_date'	=> NULL
			, 'frequency'				=> NULL
			, 'past_due_amount'			=> NULL
			, 'is_active'				=> NULL
			, 'transaction_template'	=> NULL
			);
		
		$data = array_intersect_key((array) $data, $valid_fields);
		$this
			->_add_to_payload('api_action', 'subscription_modify')
			->_add_to_payload('sub_token', $token)
			->_add_to_payload($data);
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * subscription_list - List subscription in FoxyCart. Optionally
	 * apply filters to the list.
	 *
	 * @access	public
	 * @param	array	Array of filters to apply to list.
	 * @return	mixed	FALSE on error, simplexml stdClass object
	 */
	public function subscription_list(array $filters = NULL)
	{
		$valid_filters = array(
			'is_active_filter'						=> NULL
			, 'frequency_filter'					=> NULL
			, 'past_due_amount_filter'				=> NULL
			, 'start_date_filter_begin'				=> NULL
			, 'start_date_filter_end'				=> NULL
			, 'next_transaction_date_filter_begin'	=> NULL
			, 'next_transaction_date_filter_end'	=> NULL
			, 'end_date_filter_begin'				=> NULL
			, 'end_date_filter_end'					=> NULL
			, 'third_party_id_filter'				=> NULL
			, 'last_transaction_id_filter'			=> NULL
			, 'customer_id_filter'					=> NULL
			, 'customer_last_name_filter'			=> NULL
			, 'product_code_filter'					=> NULL
			, 'product_name_filter'					=> NULL
			, 'product_option_name_filter'			=> NULL
			, 'product_option_value_filter'			=> NULL
			, 'pagination_start'					=> 1
			);
		
		$data = array_intersect_key((array) $data, $valid_filters);
		$this
			->_add_to_payload('api_action', 'subscription_list')
			->_add_to_payload($data);
		
		$this->_api_call();
		return $this->result;
	}
	
	/**
	 * set_curl_options - Set CURL options.
	 *
	 * @access	public
	 * @param	mixed	Array of options or Curl option constant
	 * @param	mixed	Value to set to Curl option
	 * @return	object	Chainable $this
	 */
	public function set_curl_options($option = NULL, $value = NULL)
	{
		if (is_array($option)) {
			$this->curl_options = array_merge($this->curl_options, $option);
		} elseif ($option !== NULL) {
			$this->curl_options[$option] = $value;
		}

		return $this;
	}

	public function set_endpoint($endpoint = NULL)
	{
		if (strpos($endpoint, '://') !== FALSE) {
			$this->endpoint = $endpoint;
		} else {
			$this->endpoint = str_replace('YOURDOMAIN.foxycart.com', $endpoint, $this->endpoint);
		}
	}

	/**
	 * _add_to_payload - Add data to be posted to the FoxyCart API.
	 *
	 * @access	private
	 * @param	mixed	Can be an array of fields or a field name
	 * @param	mixed	The value to set to the field name
	 * @return	object	Chainable $this
	 */
	private function _add_to_payload($field = NULL, $value = NULL)
	{
		if ( ! is_array($field) AND $field != NULL AND ! is_numeric($field) AND strpos($this->_payload, $field) === FALSE) {
			$this->_payload .= $field.'='.urlencode($value).'&';
		} elseif(is_array($field) === TRUE) {
			foreach($field as $name => $value) {
				if(is_numeric($name) === TRUE OR strpos($this->_payload, $name) !== FALSE) {
					continue;
				}
				
				$this->_payload .= $name.'='.urlencode($value).'&';
			}
		}

		return $this;
	}
	
	/**
	 * _api_call - Execute the API call to FoxyCart and process the result.
	 *
	 * @access	private
	 * @return	bool	TRUE is success and FALSE an error occured
	 */
	private function _api_call()
	{
		$this->_add_to_payload('api_token', $this->api_token);
		$this->_payload = substr($this->_payload, 0, -1);
		$this->result = FALSE;
		$options = $this->curl_options;
		$options[CURLOPT_POSTFIELDS] = $this->_payload;
		$this->_payload = '';

		if ( ! ini_get('safe_mode')) {
			$options[CURLOPT_FOLLOWLOCATION] = TRUE;
		}

		$options[CURLOPT_RETURNTRANSFER] = TRUE;
		
		$curl = curl_init($this->endpoint);
		curl_setopt_array($curl, $options);
		$response = curl_exec($curl);

		// Get the response information
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($code AND $code < 200 OR $code > 299) {
			$error = $response;
		} elseif ($response === FALSE) {
			$error = curl_error($curl);
		}

		// Close the connection
		curl_close($curl);

		if (isset($error) === TRUE) {
			$this->errors['curl_error'] = 'Error calling FoxyCart API '
				. $this->endpoint
				. ' [status: '.$code.'] '
				. $error;

			return FALSE;
		}

		$response = simplexml_load_string($response, NULL, LIBXML_NOCDATA);
		
		if ($response !== FALSE) {
			$this->status	= (string) $response->result;
			$this->message	= '';
			
			foreach ($response->messages as $message) {
				$this->message .= (string) $message->message[0].' ';
			}
			
			$this->result = $response;
			
			if(strcasecmp('ERROR', $this->status) == 0) {
				$this->errors['foxycart_api'] = $this->message;
				return FALSE;
			}
			
			$this->message = $this->status . ' ' . trim($this->message);
			return TRUE;
		}
		
		return FALSE;
	}
}
