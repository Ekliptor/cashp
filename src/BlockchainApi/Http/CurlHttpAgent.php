<?php
namespace Ekliptor\CashP\BlockchainApi\Http;


/**
 * A HTTP agent using the cURL PHP extension.
 *
 */
class CurlHttpAgent extends AbstractHttpAgent {
	public function __construct(callable $loggerFn = null, array $options = array()) {
		parent::__construct($loggerFn, $options);
	}
	
	public function get(string $url, $options = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, isset($options['maxRedirects']) ? $options['timeout'] : $this->timeoutSec);
		curl_setopt($ch, CURLOPT_USERAGENT, isset($options['userAgent']) ? $options['userAgent'] : $this->userAgent);
		$maxRedirects = isset($options['maxRedirects']) ? $options['maxRedirects'] : $this->maxRedirects;
		if ($maxRedirects > 0) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirects);
		}
		/*
		if ($skip_certificate_check) {
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		*/
		$output = curl_exec($ch);
		if (curl_errno($ch)) {
			$output = false;
			$error = "URL: $url Curl error: " . curl_error($ch);
			$this->logError("cURL error getting page", $error);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $output;
	}
}
?>