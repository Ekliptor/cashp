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
			$this->logError("Error on HTTP GET", $response->get_error_messages());
			return false;
		}
		$body = wp_remote_retrieve_body($response);
		return $body;
	}
	
	protected function getHttpOptions(array $options = array()) {
		return array(
				'timeout' => isset($options['timeout']) ? $options['timeout'] : $this->timeoutSec, //seconds
				'user-agent' => isset($options['userAgent']) ? $options['userAgent'] : $this->userAgent,
				'redirection' => isset($options['maxRedirects']) ? $options['maxRedirects'] : $this->maxRedirects,
				'headers' => array(
					'Accept' => 'application/json',
				),
			);
	}
}
?>