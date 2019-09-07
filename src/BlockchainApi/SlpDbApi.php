<?php
namespace Ekliptor\CashP\BlockchainApi;

use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\BlockchainApi\Structs\SlpToken;
use Ekliptor\CashP\BlockchainApi\Structs\SlpTokenAddress;

class SlpDbApi extends AbstractBlockchainApi {
	/** @var array */
	protected $queryCache = array(); // associative array with SHA2(base64 query) as key
	
	protected function __construct(string $blockchainApiUrl = '') {
		parent::__construct($blockchainApiUrl);
		if (empty($this->blockchainApiUrl))
			$this->blockchainApiUrl = "https://slpdb.fountainhead.cash/q/";
	}
	
	public function getConfirmationCount(string $transactionID): int {
		// should be easy since they return the block number for each TX, so just the difference?
		throw new BlockchainException("Getting confirmation count is not yet supported by SLPDB.");
	}
	
	public function getBlocktime(string $transactionID): int { 
		// should be easy (approx): block number * 10min + offset
		throw new BlockchainException("Getting the blocktime is not yet supported by SLPDB.");
	}
	
	public function createNewAddress(string $xPub, int $addressCount, string $hdPathFormat = '0/%d'): ?BchAddress {
		throw new BlockchainException("Creating new addresses is not yet supported by SLPDB.");
	}
	
	public function getTokenInfo(string $tokenID): ?SlpToken {
		$response = $this->executeQuery('{
			  "v": 3,
			  "q": {
			    "db": ["t"],
			    "find": {"tokenDetails.tokenIdHex": "%s"},
			    "limit": 10
			  }
			}', $tokenID);
		if ($this->isValidQueryResponse($response, 't') === false)
			return null;
		return SlpToken::fromJson($response->t[0]->tokenDetails);
	}
	
	public function getAddressBalance(string $address): float {
		throw new BlockchainException("Creating new addresses is not yet supported by SLPDB.");
	}
	
	public function getAddressTokenBalance(string $address, string $tokenID): float {
		$response = $this->executeQuery('{
			  "v": 3,
			  "q": {
			    "db": ["a"],
			    "find": {"address": "%s", "tokenDetails.tokenIdHex": "%s"},
			    "limit": 10
			  }
			}', $address, $tokenID);
		if ($this->isValidQueryResponse($response, 'a') === false)
			return -1.0;
		return (float)$response->a[0]->token_balance; // or satoshis_balance
	}
	
	public function getAddressDetails(string $address): ?BchAddress {
		
	}
	
	public function getSlpAddressDetails(string $address, string $tokenID): ?SlpTokenAddress {
		$info = $this->getTokenInfo($tokenID);
		if ($info === null)
			return null;
		//$balance = $this->getAddressTokenBalance($address, $tokenID);
		$slpToken = SlpTokenAddress::copyProperties(new SlpTokenAddress($address), $info);
		$slpToken->transactions = $this->getTokenTransactionIDs($address, $tokenID);
		return $slpToken;
	}
	
	protected function getTokenTransactionIDs(string $address, string $tokenID): array {
		$transactions = array();
		$response = $this->executeQuery('{
			  "v": 3,
			  "q": {
			    "db": ["x"],
			    "aggregate": [
			      { "$match": {"address": "%s"}}, 
			      { "$lookup": { "from": "confirmed", "localField": "txid", "foreignField": "tx.h", "as": "txn" }}, 
			      { "$unwind": "$txn" }, 
			      { "$project": { "txid": "$txn.tx.h", "slp": "$txn.slp", "block": "$txn.blk.i", "_id": 0 } }
			    ],
			    "limit": 10
			  }
			}', $address);
		if ($this->isValidQueryResponse($response, 'x') === false)
			return $transactions;
		
		foreach ($response->x as $tx) {
			if (isset($tx->txid) && isset($tx->slp) && isset($tx->slp->valid) && $tx->slp->valid === true)
				$transactions[] = $tx->txid;
		}
		return $transactions;
	}
	
	protected function executeQuery(string $mongoJson, $args) {
		$args = func_get_args();
		if (count($args) > 1) {
			$args = array_slice($args, 1);
			$mongoJson = vsprintf($mongoJson, $args);
		}
		$key = hash('sha512', $mongoJson);
		if (isset($this->queryCache[$key]))
			return $this->queryCache[$key];
		
		$url = sprintf($this->blockchainApiUrl . '%s', base64_encode($mongoJson));
		$response = $this->httpAgent->get($url);
		if ($response === false)
			return false;
		$jsonRes = json_decode($response);
		$this->queryCache[$key] = $jsonRes;
		return $jsonRes;
	}
	
	protected function isValidQueryResponse($response, string $table, $allowEmpty = false): bool  {
		if ($response === false || !isset($response->$table))
			return false;
		if ($allowEmpty === false && empty($response->$table))
			return false;
		return true;
	}
}
?>