<?php
namespace Ekliptor\CashP\BlockchainApi;

use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\BlockchainApi\Structs\SlpToken;
use Ekliptor\CashP\BlockchainApi\Structs\SlpTokenAddress;
use Ekliptor\CashP\BlockchainApi\Structs\Transaction;

class BitcoinComRestApi extends AbstractBlockchainApi {
	
	protected function __construct(string $blockchainApiUrl = '') {
		parent::__construct($blockchainApiUrl);
		if (empty($this->blockchainApiUrl))
			$this->blockchainApiUrl = "https://rest.bitcoin.com/v2/";
	}
	
	public function getConfirmationCount(string $transactionID): int {
		$txDetails = $this->getTransactionDetails($transactionID);
		if (!$txDetails || !isset($txDetails->confirmations))
			return -1; // not found
		return (int)$txDetails->confirmations;
	}
	
	public function getBlocktime(string $transactionID): int { 
		$txDetails = $this->getTransactionDetails($transactionID);
		if (!$txDetails || !isset($txDetails->blocktime))
			return -1; // not found
		return (int)$txDetails->blocktime;
	}
	
	public function createNewAddress(string $xPub, int $addressCount, string $hdPathFormat = '0/%d'): ?BchAddress {
		$nextHdPath = sprintf($hdPathFormat, $addressCount); // since update on 2019-06-05 only works with plain numbers
		$url = sprintf($this->blockchainApiUrl . 'address/fromXPub/%s?hdPath=%s', $xPub, $nextHdPath);
		$response = $this->httpAgent->get($url);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if (!$jsonRes)
			return null;
		else if (isset($jsonRes->error) && $jsonRes->error) {
			$this->logError("Error creating new address", $jsonRes->error);
			return null;
		}
		return new BchAddress($jsonRes->cashAddress, $jsonRes->legacyAddress, $jsonRes->slpAddress);
	}
	
	public function getTokenInfo(string $tokenID): ?SlpToken {
		$url = sprintf($this->blockchainApiUrl . 'slp/list/%s', $tokenID);
		$response = $this->httpAgent->get($url);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if (!$jsonRes)
			return null;
		return SlpToken::fromJson($jsonRes);
	}
	
	public function getAddressBalance(string $address): float {
		$bchAddress = $this->getAddressDetails($address);
		if ($bchAddress === null || !isset($bchAddress->balance))
			return -1.0;
		return $bchAddress->balance;
	}
	
	public function getAddressTokenBalance(string $address, string $tokenID): float {
		//$url = sprintf($this->blockchainApiUrl . 'slp/balancesForAddress/%s', $address); // address can have multiple tokens
		$url = sprintf($this->blockchainApiUrl . 'slp/balance/%s/%s', $address, $tokenID);
		$response = $this->httpAgent->get($url);
		if ($response === false)
			return -1.0;
		$jsonRes = json_decode($response);
		if (!$jsonRes || !isset($jsonRes->balance))
			return -1.0;
		return $jsonRes->balance;
	}
	
	public function getAddressDetails(string $address): ?BchAddress {
		$url = sprintf($this->blockchainApiUrl . 'address/details/%s', $address);
		$response = $this->httpAgent->get($url);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if (!$jsonRes)
			return null;
		else if (isset($jsonRes->error) && $jsonRes->error) {
			$this->logError("Error on receiving BCH address details", $jsonRes->error);
			return null;
		}
		$bchAddress = new BchAddress($jsonRes->cashAddress, $jsonRes->legacyAddress, $jsonRes->slpAddress);
		$bchAddress->addProperties($jsonRes);
		return $bchAddress;
	}
	
	public function getSlpAddressDetails(string $address, string $tokenID): ?SlpTokenAddress {
		$url = sprintf($this->blockchainApiUrl . 'slp/transactions/%s/%s', $tokenID, $address);
		$response = $this->httpAgent->get($url);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		//if (!$jsonRes) // can be empty array
		if ($jsonRes === null)
			return null;
		$slpToken = SlpTokenAddress::fromAddressJson($jsonRes, new SlpTokenAddress($address), $tokenID);
		return $slpToken;
	}
	
	public function getTransaction(string $transactionID): ?Transaction {
		throw new \Exception("getTransaction() is not yet implemented on " . get_class($this)); // TODO
	}
	
	protected function getTransactionDetails(string $transactionID): ?\stdClass {
		if (isset($this->transactionCache[$transactionID]))
			return $this->transactionCache[$transactionID];
		
		$url = sprintf($this->blockchainApiUrl . 'transaction/details/%s', $transactionID); // we could also use /slp/txDetails/{txid}
		$response = $this->httpAgent->get($url);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if ($jsonRes)
			$this->transactionCache[$transactionID] = $jsonRes;
		return $jsonRes;
	}
}
?>