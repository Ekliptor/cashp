<?php
namespace Ekliptor\CashP;

use Ekliptor\CashP\BlockchainApi\Http\BasicHttpAgent;
use Ekliptor\CashP\BlockchainApi\AbstractBlockchainApi;


/**
 * The main interface class to interact with this library.
 *
 */
class CashP {
	/** @var CashP */
	//private static $instance = null;
	
	/** @var CashpOptions */
	protected $options = null;
	
	/** @var int */
	//protected $addressCount = 0;
	
	/** @var ExchangeRate */
	protected $rate;
	/** @var AbstractBlockchainApi */
	protected $blockchainApi;
	
	// expose the constructor and let the user create multiple instances
	// this allows for an easier API because we can move all config into CashpOptions
	/*
	public static function getInstance(CashpOptions $options = null): CashP {
		if (static::$instcance === null)
			static::$instcance = new CashP();
		return static::$instcance;
	}
	*/
	
	/**
	 * Create the main API class.
	 * @param CashpOptions $options (optional)
	*/
	public function __construct(CashpOptions $options = null) {
		if ($options === null)
			$options = new CashpOptions();
		$this->options = $options;
		if ($this->options->httpAgent === null)
			$this->options->httpAgent = new BasicHttpAgent(null, array('timeout' => $this->options->httpTimeoutSec));
		$this->rate = new ExchangeRate($this->options->httpAgent, $options->exchangeRateExpirationMin);
		$this->blockchainApi = AbstractBlockchainApi::getInstance($options->blockchainApiImplementation);
		$this->blockchainApi->setHttpAgent($this->options->httpAgent);
	}
	
	/**
	 * Return the exchange rate API.
	 * @return ExchangeRate
	 */
	public function getRate(): ExchangeRate {
		return $this->rate;
	}
	
	/**
	 * Return the Blockchain API to generate addresses, check balances, transactions,...
	 * @return AbstractBlockchainApi
	 */
	public function getBlockchain(): AbstractBlockchainApi {
		return $this->blockchainApi;
	}
	
	/**
	 * Generate a QR code for a payment.
	 * @param string $fileLocal A path on your local filesystem to store the QR code file. This should be accessible from the web if you want
	 * 			to display the QR code to users.
	 * 			If the given file already exists it will NOT be overwritten (QR codes are meant to be generated & cached in your web temp directory).
	 * @param string $address The (1-time) BCH (or SLP) address created for this payment.
	 * @param float The amount in BCH. Can be 0 if the user pays the full amount in SLP tokens. 
	 * @param float $amountToken (optional) The amount of the optional token to be received.
	 * @param string $tokenID (optional) The hex ID of the SLP token. Required if $amountToken > 0.
	 * @return bool true on success, false otherwise
	 */
	public function generateQrCodeForAddress(string $fileLocal, string $address, float $amountBCH, float $amountToken = 0.0, string $tokenID = ""): bool {
		if (substr($fileLocal, -4) !== '.png')
			$fileLocal .= '.png';
		if (file_exists($fileLocal) === true)
			return true; // use it from cache
		$codeContents = $this->createPaymentURI($address, $amountBCH, $amountToken, $tokenID);
		\QR_Code\QR_Code::png($codeContents, $fileLocal);
		return true;
	}
	
	/**
	 * Return a payment URI (starting with "bitcoincash:" or "simpleledger:" if $amountToken > 0) for the given $address.
	 * @param string $address The receiving BCH (or SLP) address.
	 * @param float $amountBCH The amount in BCH to receive.
	 * @param float $amountToken (optional) The amount of SLP tokens to receive.
	 * @param string $tokenID (optional) The hex ID of the SLP token. Required if $amountToken > 0.
	 * @return string
	 */
	public function createPaymentURI(string $address, float $amountBCH, float $amountToken = 0.0, string $tokenID = ""): string {
		$tokenDigits = 8; // TODO add parameter for this?
		$address = preg_replace("/.+:/i", "", $address);
		// we use the bitcoincash URI if tokens are disabled because bitcoin.com and other wallets only support this as of June 2019
		//$uri = "simpleledger:$address?amount=$amountBCH";
		$uri = sprintf("bitcoincash:%s?amount=%s", $address, number_format($amountBCH, $tokenDigits));
		if ($amountToken > 0.0) {
			if (empty($tokenID))
				throw new \Error("A payment URI with SLP tokens must use the $tokenID parameter.");
			$uri = sprintf("simpleledger:%s?amount=%s", $address, number_format($amountBCH, $tokenDigits));
			$uri .= sprintf("&amount1=%s-%s", number_format($amountToken, $tokenDigits), $tokenID);
		}
		return $uri;
	}
}
?>