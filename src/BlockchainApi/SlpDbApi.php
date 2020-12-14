<?php
namespace Ekliptor\CashP\BlockchainApi;

use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\BlockchainApi\Structs\SlpToken;
use Ekliptor\CashP\BlockchainApi\Structs\SlpTokenAddress;
use Ekliptor\CashP\BlockchainApi\Structs\Transaction;

class SlpDbApi extends AbstractBlockchainApi {
	/** @var array */
	protected $queryCache = array(); // associative array with SHA2(base64 query) as key
	
	protected function __construct(string $blockchainApiUrl = '') {
		parent::__construct($blockchainApiUrl);
		if (empty($this->blockchainApiUrl))
			$this->blockchainApiUrl = "https://slpdb.fountainhead.cash/q/";
	}
	
	public function getConfirmationCount(string $transactionID): int {
		$response = $this->executeQuery('{
			  "v": 3,
			  "q": {
			    "find": {"tx.h": "%s"},
			    "limit": 10
			  }
			}', $transactionID);
		
		// if TX is unconfirmed -> 1 item in the "u" collection, confirmed -> in the "c" collection
		if ($this->isValidQueryResponse($response, 'u') === false) {
			if ($this->isValidQueryResponse($response, 'c') === false)
				return -1; // the TX doesn't exist (yet)
			// TODO return:  "current block height" - $response->c[0]->blk->i
			return 3;
		}
		return 0; // the TX exists and is unconfirmed (not in a block yet)
	}
	
	public function getBlocktime(string $transactionID): int { 
		$response = $this->executeQuery('{
			  "v": 3,
			  "q": {
			    "find": {"tx.h": "%s"},
			    "limit": 10
			  }
			}', $transactionID);
		
		if ($this->isValidQueryResponse($response, 'c') === false)
			return -1; // not found
		if (!isset($response->c[0]->blk) || !isset($response->c[0]->blk->t))
			return -1; // invalid response, shouldn't happen
		return $response->c[0]->blk->t; // in seconds
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
		throw new BlockchainException("Getting BCH address balances is not yet supported by SLPDB.");
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
	
	public function getTransaction(string $transactionID): ?Transaction {
		throw new \Exception("getTransaction() is not yet implemented on " . get_class($this)); // TODO
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