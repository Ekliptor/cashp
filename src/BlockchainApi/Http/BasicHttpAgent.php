<?php
namespace Ekliptor\CashP\BlockchainApi\Http;


/**
 * The basic HTTP agent that works on any PHP installation without any extensions or framework-specific code.
 *
 */
class BasicHttpAgent extends AbstractHttpAgent {
	public function __construct(callable $loggerFn = null, array $options = array()) {
		parent::__construct($loggerFn, $options);
	}
	
	public function get(string $url, $options = array()) {
		$ctx = stream_context_create(array('http' => 
				array('timeout' => isset($options['timeout']) ? $options['timeout'] : $this->timeoutSec,
						'user_agent' => isset($options['userAgent']) ? $options['userAgent'] : $this->userAgent,
						'max_redirects' => isset($options['maxRedirects']) ? $options['maxRedirects'] : $this->maxRedirects,
						'header' => implode("\r\n", array(
								'accept: application/json',
								'Content-Type: application/json',
								'Cache-Control: no-cache,max-age=0'
						)),
				))
		);
		$contents = file_get_contents($url, 0, $ctx);
		return $contents;
	}
	
	public function post(string $url, array $data = array(), array $options = array()) {
		$ctx = stream_context_create(array('http' => 
				array(
						'method' => 'POST',
						//'header' => 'Content-Type: application/x-www-form-urlencoded', // use \r\n to separate headers
						//'content' => http_build_query($data),
						'header' => implode("\r\n", array(
								'accept: application/json',
								'Content-Type: application/json',
								'Cache-Control: no-cache,max-age=0'
						)),
						'content' => json_encode($data),
						'timeout' => isset($options['timeout']) ? $options['timeout'] : $this->timeoutSec,
						'user_agent' => isset($options['userAgent']) ? $options['userAgent'] : $this->userAgent,
						'max_redirects' => isset($options['maxRedirects']) ? $options['maxRedirects'] : $this->maxRedirects
				))
		);
		$contents = file_get_contents($url, 0, $ctx);
		return $contents;
	}
}
?>