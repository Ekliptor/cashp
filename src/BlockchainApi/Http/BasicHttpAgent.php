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
						'max_redirects' => isset($options['maxRedirects']) ? $options['maxRedirects'] : $this->maxRedirects
				))
		);
		$contents = file_get_contents($url, 0, $ctx);
		return $contents;
	}
}
?>