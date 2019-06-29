<?php
namespace Ekliptor\CashP\BlockchainApi\Structs;

/**
 * Represents a specific SLP token address.
 *
 */
class SlpTokenAddress extends SlpToken {
	/** @var string */
	public $slpAddress = '';
	
	/**
	 * An indexed array with strings of BCH TXIDs
	 * @var array
	 */
	public $transactions = array();
	
	public function __construct(string $slpAddress = '') {
		parent::__construct();
		$this->slpAddress = $slpAddress;
	}
	
	public static function fromAddressJson(array $jsonArr, SlpTokenAddress $instance = null, string $tokenID = ""): SlpTokenAddress {
		if ($instance === null)
			$instance = new SlpTokenAddress();
		if (!empty($tokenID))
			$instance->id = $tokenID;
		//$instance = SlpToken::fromJson($json, $instance); // on the REST API response for TX most properties exist on each TX
		foreach ($jsonArr as $tx) {
			if (!isset($tx->tokenDetails) || $tx->tokenDetails->valid !== true)
				continue;
			$txDetail = $tx->tokenDetails->detail;
			if (empty ($instance->name)) { // get token info from the first TX
				$instance->id = $txDetail->tokenIdHex;
				$instance->name = $txDetail->name;
				$instance->symbol = $txDetail->symbol;
				$instance->documentHash = $txDetail->documentSha256Hex;
				$instance->documentUri = $txDetail->documentUri;
				$instance->decimals = $txDetail->decimals;
				//$instance->timestamp_unix = ; // the time when the token has been created is not present here
			}
			$instance->transactions[] = $tx->txid;
		}
		return $instance;
	}
}
?>