<?php
namespace Ekliptor\CashP;

use Ekliptor\CashP\BlockchainApi\Http\BasicHttpAgent;
use Ekliptor\CashP\BlockchainApi\AbstractBlockchainApi;


/**
 * The main interface class to interact with this library.
 *
 */
class CashP {
	const BADGER_LIB_URL = "https://badgerwallet.cash/button.js";
	
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
	/** @var bool */
	protected $includedButtonCode = false;
	
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
		$this->blockchainApi = AbstractBlockchainApi::getInstance($options->blockchainApiImplementation, $options->blockchainApiUrl);
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
	
	public static function toSatoshis(float $bch): float {
		return floor($bch * 100000000);
	}
	
	public static function fromSatoshis(float $sats): float {
		return $sats / 100000000.0;
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
	 * @param int $tokenDigits (optional). The number of decimal places to use in the payment URI of this token.
	 * @return string
	 */
	public function createPaymentURI(string $address, float $amountBCH, float $amountToken = 0.0, string $tokenID = "", int $tokenDigits = 8): string {
		$address = preg_replace("/.+:/i", "", $address);
		// we use the bitcoincash URI if tokens are disabled because bitcoin.com and other wallets only support this as of June 2019
		//$uri = "simpleledger:$address?amount=$amountBCH";
		$uri = sprintf("bitcoincash:%s?amount=%s", $address, number_format($amountBCH, $tokenDigits, ".", ""));
		if ($amountToken > 0.0) {
			if (empty($tokenID))
				throw new \Error("A payment URI with SLP tokens must use the tokenID parameter.");
			if ($amountBCH > 0.0)
				$uri = sprintf("simpleledger:%s?amount=%s&", $address, number_format($amountBCH, $tokenDigits, ".", ""));
			else
				$uri = sprintf("simpleledger:%s?", $address);
			$uri .= sprintf("amount1=%s-%s", number_format($amountToken, $tokenDigits), $tokenID, ".", "");
		}
		return $uri;
	}
	
	/**
	 * Get the HTML code of a BadgerButton. See https://badger.bitcoin.com/
	 * @param array $btnConf associative array with buttom config
	 * 		text (string) The text of the button
	 * 		callback (string, optional) The name of a callback function present on the global window to be called after payment. Parameters: string chainTxID
	 * 		cssClass (string, optional) Additional CSS classes for the button.
	 * 		forceIndludeJs (bool, optional) default false - Include the JavaScript library again. Only use this if you are generating HTML for multiple pages.
	 * @param string $address The receiving BCH (or SLP) address.
	 * @param float $amountBCH The amount in BCH to receive.
	 * @param float $amountToken (optional) The amount of SLP tokens to receive.
	 * @param string $tokenID (optional) The hex ID of the SLP token. Required if $amountToken > 0.
	 * @return string The button HTML.
	 */
	public function getBadgerButton(array $btnConf, string $address, float $amountBCH, float $amountToken = 0.0, string $tokenID = ""): string {
		if (!isset($btnConf['text']) || empty($btnConf['text']))
			throw new \Error("BadgerButton text can not be empty.");
		$useTokenPayments = $amountToken > 0.0 && empty($tokenID);
		if ($useTokenPayments === true)
			throw new \Error("A payment URI with SLP tokens must use the tokenID parameter.");
		
		$btnConf = array_merge($btnConf, array(
				'includedButtonCode' => $this->includedButtonCode,
				'address' => $address,
				'amountBCH' => $amountBCH,
				'tokenAmount' => $amountToken,
				'sats' => static::toSatoshis($amountBCH),
				'tokenID' => $tokenID,
				'useTokenPayments' => $useTokenPayments,
				'buttonLibSrc' => CashP::BADGER_LIB_URL,
				'forceIndludeJs'=> isset($btnConf['forceIndludeJs']) && $btnConf['forceIndludeJs'] === true,
				'script' => file_get_contents(__DIR__ . '/../tpl/js/bundle.js') // we don't know our web root, so we must include it inline
		));
		ob_start();
		include __DIR__ . '/../tpl/badgerButton.php';
		$buttonHtml = ob_get_contents();
		ob_end_clean();
		$this->includedButtonCode = $btnConf['includedButtonCode'];
		return $buttonHtml;
	}
	
	/**
	 * Check if a BCH address is valid.
	 * @param string $bchAddress The address in CashAddress format starting with 'bitcoincash:'
	 * @return bool True if the address is valid, false otherwise.
	 */
	public function isValidBchAddress(string $bchAddress): bool {
		if (empty($bchAddress))
			return false;
		// TODO improve this by checking the actual address format
		$bchAddress = strtolower(trim($bchAddress));
		if (preg_match("/^bitcoincash:/", $bchAddress) !== 1)
			return false;
		if (strlen($bchAddress) !== 54)
			return false;
		$addressParts = explode(':', $bchAddress);
		if (count($addressParts) !== 2)
			return false;
		return preg_match("/^[a-z0-9]+$/", $addressParts[1]) === 1;
	}
	
	/**
	 * Check if a SLP address is valid.
	 * @param string $slpAddress The address starting with 'simpleledger:'
	 * @return bool True if the address is valid, false otherwise.
	 */
	public function isValidSlpAddress(string $slpAddress): bool {
		if (empty($slpAddress))
			return false;
		// TODO improve this by checking the actual address format
		$slpAddress = strtolower(trim($slpAddress));
		if (preg_match("/^simpleledger:/", $slpAddress) !== 1)
			return false;
		if (strlen($slpAddress) !== 55)
			return false;
		$addressParts = explode(':', $slpAddress);
		if (count($addressParts) !== 2)
			return false;
		return preg_match("/^[a-z0-9]+$/", $addressParts[1]) === 1;
	}
	
	/**
	 * Convert a hex string to base64.
	 * @param string $hex
	 * @return string
	 */
	public static function hexToBase64(string $hex): string {
		$return = '';
		$split = str_split($hex, 2);
		foreach($split as $pair) {
			$return .= chr(hexdec($pair));
		}
		return base64_encode($return);
	}
	
	/**
	 * Reverse bytes in a hex string (to deal with endian-ness).
	 * @param string $hex
	 * @return string
	 */
	public static function reverseBytes(string $hex): string {
		$return = '';
		$split = str_split($hex, 2);
		for($i = count($split)-1; $i >= 0; $i--) {
			$return .= $split[$i];
		}
		return $return;
	}
}
?>