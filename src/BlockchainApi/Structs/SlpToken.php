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
	/** @var string */
	public $icon = '';
	
	public function __construct() {
	}
	
	public static function fromJson(\stdClass $json, SlpToken $instance = null): SlpToken {
		if ($instance === null)
			$instance = new SlpToken();
		foreach ($instance as $key => $value) {
			if (isset($json->$key))
				$instance->$key = $json->$key;
		}
		return $instance;
	}
}
?>