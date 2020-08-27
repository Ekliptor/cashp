<?php 
namespace Ekliptor\CashP\BlockchainApi\Structs;

class BchAddress {
	/** @var string */
	public $cashAddress;
	/** @var string */
	public $legacyAddress;
	/** @var string */
	public $slpAddress;
	/** @var float */
	public $balance = 0.0;
	/** @var int */
	public $balanceSat = 0;
	/** @var float */
	public $totalReceived = 0.0;
	/** @var int */
	public $totalReceivedSat = 0;
	/** @var float */
	public $totalSent = 0.0;
	/** @var int */
	public $totalSentSat = 0;
	/** @var float */
	public $unconfirmedBalance = 0.0;
	/** @var int */
	public $unconfirmedBalanceSat = 0;
	/** @var int */
	public $unconfirmedTxApperances = 0;
	/** @var int */
	public $txApperances = 0;
	/**
	 * An indexed array with strings of BCH TXIDs
	 * @var array
	 */
	public $transactions = array();
	
	// TODO add paging using currentPage and pagesTotal for big addresses
	
	public function __construct(string $cashAddress, string $legacyAddress = '', string $slpAddress = '') {
		if (substr($cashAddress, 0, 12) !== 'bitcoincash:')
			$cashAddress = 'bitcoincash:' . $cashAddress;
		$this->cashAddress = $cashAddress;
		$this->legacyAddress = $legacyAddress;
		$this->slpAddress = $slpAddress;
	}
	
	/**
	 * Adds known properties of a BchAddress from the supplied JSON.
	 * If a property already exists, it will be overwritten.
	 * @param \stdClass $json
	 */
	public function addProperties(\stdClass $json): void {
		if (isset($json->cashAddress) && $json->cashAddress !== $this->cashAddress)
			throw new \Exception("Can not change the BCH CashAddr supplied in addProperties() - supplied value: " . $json->cashAddress);
		
		$keys = array("balance", "balanceSat", "totalReceived", "totalReceivedSat", "totalSent", "totalSentSat", "unconfirmedBalance",
				"unconfirmedBalanceSat", "unconfirmedTxApperances", "txApperances", "transactions",
				//"legacyAddress", "cashAddress", "slpAddress" // values from consteuctor
		);
		foreach ($keys as $key) {
			if (isset($json->$key))
				$this->$key = $json->$key;
		}
	}
}
?>