<?php
namespace Ekliptor\CashP\BlockchainApi;

use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\BlockchainApi\Structs\SlpToken;
use Ekliptor\CashP\BlockchainApi\Structs\SlpTokenAddress;
use Ekliptor\CashP\BlockchainApi\Http\AbstractHttpAgent;

class BlockchainException extends \Exception {
	public function __construct($message = null, $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}


abstract class AbstractBlockchainApi {
	/** @var AbstractBlockchainApi */
	private static $instance = null;
	/** @var callable */
	protected static $loggerFn = null;
	
	/** @var string */
	protected $blockchainApiUrl = '';
	/** @var array */
	protected $transactionCache = array(); // associative array with TXID as key
	/** @var AbstractHttpAgent */
	protected $httpAgent = null;
	
	protected function __construct(string $blockchainApiUrl = '') {
		if (!empty($blockchainApiUrl))
			$this->blockchainApiUrl = $blockchainApiUrl;
	}
	
	/**
	 * Create an API class and cache it for further usage.
	 * (You can only create 1 instance per script - even with multiple implementations.)
	 * @param string $className The BCH chain API implementation to use. Valid options: BitcoinComRestApi
	 * @param string $blockchainApiUrl The API URL to use. Defaults to rest.bitcoin.com
	 * @throws \Error
	 * @return \Ekliptor\CashP\BlockchainApi\AbstractBlockchainApi
	 */
	public static function getInstance(string $className, string $blockchainApiUrl = '') {
		if (self::$instance !== null)
			return self::$instance;
		switch ($className) {
			case 'BitcoinComRestApi':
				self::$instance = new BitcoinComRestApi($blockchainApiUrl);
				return self::$instance;
			case 'SlpDbApi':
				self::$instance = new SlpDbApi($blockchainApiUrl);
				return self::$instance;
		}
		throw new \Error("Unable to load bloackchain API class (not existing?): " . $className);
	}
	
	/**
	 * Set a logger function for errors and debug output. Use this to write to a logfile or database.
	 * If no function is provided everything will be printed using 'echo'.
	 * @param callable $loggerFn(string $subject, mixed $error, mixed $data = null)
	 */
	public static function setLogger(callable $loggerFn): void {
		static::$loggerFn = $loggerFn;
	}
	
	/**
	 * Set a a HTTP implementation for requests (cURL, Wordpress HTTP API,...)
	 * @param AbstractHttpAgent $agent
	 */
	public function setHttpAgent(AbstractHttpAgent $agent): void {
		$this->httpAgent = $agent;
	}
	
	/**
	 * Return the number of confirmation for the given blockchain transaction ID.
	 * @param string $transactionID
	 * @return int The number of confirmations or -1 if the $transactionID doesn't exist.
	 */
	public abstract function getConfirmationCount(string $transactionID): int;
	
	/**
	 * Get the blocktime of the transaction.
	 * @param string $transactionID
	 * @return int The time in seconds (UNIX timestamp) or -1 if the transaction doesn't exist.
	 */
	public abstract function getBlocktime(string $transactionID): int;
	
	/**
	 * Creates a new address from the xPub.
	 * @param string $xPub The extended public key. Called 'Master Public Key' in Electron Cash.
	 * @param int $addressCount The number of the next address to generate a unique address. Usually this should be an incrementing integer.
	 * @param string $hdPathFormat (optional) The HD path to be used for creating address children.
	 * @return BchAddress the address or null on failure
	 */
	public abstract function createNewAddress(string $xPub, int $addressCount, string $hdPathFormat = '0/%d'): ?BchAddress;
	
	/**
	 * Get general (network-wide) info about a SLP token.
	 * @param string $tokenID
	 * @return SlpToken or null on failure
	 */
	public abstract function getTokenInfo(string $tokenID): ?SlpToken;
	
	/**
	 * Return the BCH balance of the given address (including unconfirmed transactions).
	 * @param string $address The BCH address in CashAddress format.
	 * @return float The balance or -1 if the address doesn't exist.
	 */
	public abstract function getAddressBalance(string $address): float;
	
	/**
	 * Return the token balance of the given SLP address (including unconfirmed transactions).
	 * @param string $address
	 * @param string $tokenID
	 * @return float The balance or -1 if the address doesn't exist.
	 */
	public abstract function getAddressTokenBalance(string $address, string $tokenID): float;
	
	/**
	 * Return the BCH Address with all its properties such as balance, TXIDs,...
	 * @param string $address The BCH address in CashAddress format.
	 * @return BchAddress|NULL
	 */
	public abstract function getAddressDetails(string $address): ?BchAddress;
	
	/**
	 * Return the SLP token details of a given address include balance, TXIDs,...
	 * @param string $address The SLP address
	 * @param string $tokenID
	 * @return SlpTokenAddress|NULL
	 */
	public abstract function getSlpAddressDetails(string $address, string $tokenID): ?SlpTokenAddress;
	
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