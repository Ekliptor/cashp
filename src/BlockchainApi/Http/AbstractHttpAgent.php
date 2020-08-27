<?php
namespace Ekliptor\CashP\BlockchainApi\Http;


/**
 * This class has to be implemented for HTTP requests.
 * You should implement it with the default (best) available HTTP method in your framework, 
 * such as GuzzleHttp, cURL, wp_remote_get(),...
 *
 */
abstract class AbstractHttpAgent {
	/** @var callable */
	protected static $loggerFn = null;
	protected $timeoutSec = 10;
	protected $maxRedirects = 5;
	protected $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0";
	
	/**
	 * Create the HTTP agent.
	 * @param callable $loggerFn
	 * @param array $options Set defaults for additional options (depending on the specific HTTP implementation) valid: timeout|userAgent|maxRedirects
	 */
	public function __construct(callable $loggerFn = null, array $options = array()) {
		static::$loggerFn = $loggerFn;
		if (isset($options['timeout']))
			$this->timeoutSec = $options['timeout'];
		if (isset($options['maxRedirects']))
			$this->maxRedirects = $options['maxRedirects'];
		if (isset($options['userAgent']))
			$this->userAgent = $options['userAgent'];
	}
	
	/**
	 * Perform a HTTP GET request.
	 * @param string $url The full URL string (including possible query params).
	 * @param array $options additional options (depending on the specific HTTP implementation) valid: timeout|userAgent|maxRedirects
	 * @return string|bool The response body or false on failure.
	 */
	public abstract function get(string $url, array $options = array());
	
	/**
	 * Perform a HTTP POST request with Content-Type "application/json".
	 * @param string $url The full URL string (including possible query params).
	 * @param array $data The post data as string key-value pairs (not encoded).
	 * @param array $options additional options (depending on the specific HTTP implementation) valid: timeout|userAgent|maxRedirects
	 * @return string|bool The response body or false on failure.
	 */
	public abstract function post(string $url, array $data = array(), array $options = array());
	
	protected function logError(string $subject, $error, $data = null): void {
		if (static::$loggerFn !== null)
			call_user_func(static::$loggerFn, $subject, $error, $data);
		else {
			$output = "$subject<br>\n<pre>" . print_r($error, true) . "</pre><br>\n";
			if ($data !== null)
				$output .= "<pre>" . print_r($data, true) . "</pre><br>\n";
			echo $output;
		}
	}
}
?>