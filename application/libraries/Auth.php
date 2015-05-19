<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Auth Class
 *
 * Base class for simple authentication.
 *
 * @package 	CodeIgniter
 * @subpackage 	Auth
 * @category 	Library
 * @author 		Luis Felipe Pérez
 * @version 	0.2.1
 */

class Auth	{

	private $salt_length  = 10;
	private $max_attempts = 3;
	private $blocked_time = '00:15:00';
	private $ci;

	public function __construct($config = array())
	{
		$this->ci =& get_instance();
		$this->ci->load->model('auth_attempt');
		$this->initialize($config);
	}

	/**
	 * Loads configuration and initialize variables
	 * @param  array  $param optional array configutation
	 * @return void
	 */
	private function initialize($param = array())
	{
		$auth = $this->ci->load->config('auth', TRUE, TRUE);

		if (is_array($auth) && is_array($param)) {
			$param = array_merge($auth, $param);
		}

		foreach ($param as $key => $value) {
			if (isset($this->$key) && !empty($value)) {
				switch ($key) {
					case 'blocked_time':
						if (preg_match('/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/', $value)) {
							$this->$key = $value;
						}
						break;

					case 'ci':
						# nothing to do, skip variable
						break;

					default:
						$this->$key = $value;
						break;
				}
			}
		}

		$this->ci->auth_attempt->set_blocked_time($this->blocked_time);
		$this->ci->auth_attempt->set_max_attempts($this->max_attempts);
	}

	/**
	 * Generates a salt for password encryption
	 * @return string salt
	 */
	private function salt()
	{
		return substr(md5(uniqid(rand(), TRUE)), 0, $this->salt_length);
	}

	/**
	 * Hash a string based on a previous hashed one
	 * @param  string $password password to hash
	 * @param  string $stored   stored hashed password
	 * @return mixed            returns hashed string or false
	 */
	private function to_hash($password, $stored)
	{
		if(!empty($password) AND !empty($stored)){
			$salt = substr($stored, 0, $this->salt_length);
			return $salt . substr(sha1($salt . $password), 0, -$this->salt_length);
		}else{
			return FALSE;
		}
	}

	/**
	 * Hash a string to be used as password
	 * @param  string $password string to hash
	 * @return string           hashed string
	 */
	public function hash($password)
	{
		if (empty($password)){
			return '';
		}

		$salt = $this->salt();
		return $salt . substr(sha1($salt . $password), 0, -$this->salt_length);
	}

	/**
	 * Compare given password against stored hashed one
	 * @param  string $password input password
	 * @param  string $stored   stored and hashed password
	 * @return bool             returns true if strings are equal, else return false.
	 */
	public function compare($password, $stored)
	{
		$hash = $this->to_hash($password, $stored);
		$ip   = $this->ci->input->ip_address();

		if ($password == $hash) {
			$this->ci->auth_attempt->delete($ip);
			return TRUE;
		} else {
			$this->ci->auth_attempt->attempt($ip);
			return FALSE;
		}
	}

	/**
	 * validates if the user ip is already blocked or not.
	 * @return boolean blocked status
	 */
	public function is_blocked()
	{
		return !$this->ci->auth_attempt->validate();
	}

}