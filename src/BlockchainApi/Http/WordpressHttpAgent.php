<?php
namespace Ekliptor\CashP\BlockchainApi\Http;

/**
 * The HTTP agent to be used on Wordpress installations.
 *
 */
class WordpressHttpAgent extends AbstractHttpAgent {
	public function __construct(callable $loggerFn = null, array $options = array()) {
		parent::__construct($loggerFn, $options);
	}
	
	public function get(string $url, array $options = array()) {
		$response = wp_remote_get($url, $this->getHttpOptions($options));
		if ($response instanceof \WP_Error) {
			$this->logError("Error on HTTP GET $url", $response->get_error_messages());
			return false;
		}
		$responseCode = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body($response);
		if ($responseCode !== 200) {
			$this->logError("Invalid HTTP response code $responseCode on GET: $url", $body);
			return false;
		}
		return $body;
	}
	
	public function post(string $url, array $data = array(), array $options = array()) {
		$wpOptions = $this->getHttpOptions($options);
		$wpOptions['headers']['Content-Type'] = 'application/json';
		$wpOptions['body'] = json_encode($data);
		$response = wp_remote_post($url, $wpOptions);
		if ($response instanceof \WP_Error) {
			$this->logError("Error on HTTP POST $url", $response->get_error_messages());
			return false;
		}
		$responseCode = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body($response);
		if ($responseCode !== 200) {
			$this->logError("Invalid HTTP response code $responseCode on GET: $url", $body);
			return false;
		}
		return $body;
	}
	
	protected function getHttpOptions(array $options = array()) {
		return array(
				'timeout' => isset($options['timeout']) ? $options['timeout'] : $this->timeoutSec, //seconds
				'user-agent' => isset($options['userAgent']) ? $options['userAgent'] : $this->userAgent,
				'redirection' => isset($options['maxRedirects']) ? $options['maxRedirects'] : $this->maxRedirects,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept' => 'application/json',
					'Cache-Control' => 'no-cache,max-age=0',
				),
			);
	}
}
?>