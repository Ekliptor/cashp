<?php
namespace Ekliptor\CashP\BlockchainApi\Structs;

/**
 * Reprsents general information about a SLP token.
 * properties from https://rest.bitcoin.com/#/slp/listSingle
 *
 */
class SlpToken {
	/** @var string */
	public $id = '';
	/** @var string */
	public $name = '';
	/** @var string */
	public $symbol = '';
	/** @var string: type1|nft1_group|nft1_child */
	public $type = 'type1';
	/** @var string */
	public $documentHash = '';
	/** @var string */
	public $documentUri = '';
	/** @var int */
	public $decimals = 0;
	/** @var int */
	public $timestamp_unix = 0;
	/** @var float */
	public $rate = 0.0;
	/** @var bool Whether to update the token rate from an API. */
	public $dynamicRate = false;
	/** @var string */
	public $icon = '';
	/** @var bool */
	public $enabled = true;
	
	public function __construct() {
	}
	
	public static function fromJson(\stdClass $json, SlpToken $instance = null): SlpToken {
		if ($instance === null)
			$instance = new SlpToken();
		foreach ($instance as $key => $value) {
			if (isset($json->$key))
				$instance->$key = $json->$key;
			
			// SLP DB conversions
			else if ($key === 'id' && isset($json->tokenIdHex))
				$instance->id = $json->tokenIdHex;
			else if ($key === 'documentHash' && isset($json->documentSha256Hex))
				$instance->documentHash = $json->documentSha256Hex;
		}
		return $instance;
	}
}
?>