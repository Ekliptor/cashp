<?php
namespace Ekliptor\CashP\BlockchainApi;

use Ekliptor\CashP\CashP;
use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\BlockchainApi\Structs\SlpToken;
use Ekliptor\CashP\BlockchainApi\Structs\SlpTokenAddress;
use Ekliptor\CashP\BlockchainApi\Structs\Transaction;
use Ekliptor\CashP\BlockchainApi\Structs\TransactionInput;
use Ekliptor\CashP\BlockchainApi\Structs\TransactionBaseData;
use Ekliptor\CashP\BlockchainApi\Structs\TransactionOutput;
use Ekliptor\CashP\BlockchainApi\Structs\SlpTransactionData;

class BchdProtoGatewayApi extends AbstractBlockchainApi {
	
	protected function __construct(string $blockchainApiUrl = '') {
		parent::__construct($blockchainApiUrl);
		if (empty($this->blockchainApiUrl))
			$this->blockchainApiUrl = "https://bchd.ny1.simpleledger.io/v1/";
			//$this->blockchainApiUrl = "http://localhost:8080/v1/";
	}
	
	public function getConfirmationCount(string $transactionID): int {
		$txDetails = $this->getTransactionDetails($transactionID);
		if (!$txDetails || !isset($txDetails->transaction))
			return -1; // not found
		if (!isset($txDetails->transaction->confirmations))
			return 0;
		return (int)$txDetails->transaction->confirmations;
	}
	
	public function getBlocktime(string $transactionID): int { 
		$txDetails = $this->getTransactionDetails($transactionID);
		if (!$txDetails || !isset($txDetails->transaction) || !isset($txDetails->transaction->timestamp))
			return -1; // not found
		return (int)$txDetails->transaction->timestamp;
	}
	
	public function createNewAddress(string $xPub, int $addressCount, string $hdPathFormat = '0/%d'): ?BchAddress {
		// hdPathFormat not needed for BCHD
		$url = $this->blockchainApiUrl . 'GetBip44HdAddress';
		$data = array(
				'xpub' => $xPub,
				'change' => true,
				'address_index' => $addressCount,
		);
		$response = $this->httpAgent->post($url, $data);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if (!$jsonRes)
			return null;
		else if (isset($jsonRes->error) && $jsonRes->error) {
			$this->logError("Error creating new address", $jsonRes->error);
			return null;
		}
		return new BchAddress($jsonRes->cash_addr, '', $jsonRes->slp_addr); // TODO remove legacy addresses?
	}
	
	public function getTokenInfo(string $tokenID): ?SlpToken {
		$tokenIDBase64 = static::ensureBase64Encoding($tokenID, false);
		$url = $this->blockchainApiUrl . 'GetTokenMetadata';
		$data = array(
				'token_ids' => array($tokenIDBase64),
		);
		$response = $this->httpAgent->post($url, $data);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if (!$jsonRes || empty($jsonRes->token_metadata))
			return null;
		$token = new SlpToken();
		$token->id = $tokenID;
		$this->addTokenMetadata($token, $jsonRes->token_metadata);
		return $token;
	}
	
	public function getAddressBalance(string $address): float {
		$bchAddress = $this->getAddressDetails($address);
		if ($bchAddress === null || !isset($bchAddress->balance))
			return -1.0;
		return $bchAddress->balance;
	}
	
	public function getAddressTokenBalance(string $address, string $tokenID): float {
		$slpAddress = $this->getSlpAddressDetails($address, $tokenID);
		if ($slpAddress === null || !isset($slpAddress->balance))
			return -1.0;
		return $slpAddress->balance;
	}
	
	public function getAddressDetails(string $address): ?BchAddress {
		$url = $this->blockchainApiUrl . 'GetAddressUnspentOutputs';
		$data = array(
				'address' => $address,
				'include_mempool' => true, // TODO add parameter to decide if we want confirmed balance only (needed in all subclasses)
				'include_token_metadata' => false,
		);
		$response = $this->httpAgent->post($url, $data);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if ($jsonRes === null || $response === '{}') // empty object if address is unknown = valid response
			return null;
		else if (isset($jsonRes->error) && $jsonRes->error) {
			$this->logError("Error on receiving BCH address details", $jsonRes->error);
			return null;
		}
		$bchAddress = new BchAddress($address, '', '');
		if (!isset($jsonRes->outputs) || empty($jsonRes->outputs)) // grpc proxy returns empty array []
			return $bchAddress;
		
		foreach ($jsonRes->outputs as $output) {
			$curSats = (int)$output->value;
			if ($curSats > CashP::DUST_LIMIT)
				$bchAddress->balanceSat += $curSats;
		}
		$bchAddress->balance = CashP::fromSatoshis($bchAddress->balanceSat);
		$bchAddress->transactions = $this->getAddressTransactions($address);
		return $bchAddress;
	}
	
	public function getSlpAddressDetails(string $address, string $tokenID): ?SlpTokenAddress {
		$url = $this->blockchainApiUrl . 'GetAddressUnspentOutputs';
		$data = array(
				'address' => $address,
				'include_mempool' => true,
				'include_token_metadata' => true,
		);
		$response = $this->httpAgent->post($url, $data);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if ($jsonRes === null || $response === '{}') 
			return null; // empty object if address is unknown = valid response, check again later once a TX with this token happened
		else if (isset($jsonRes->error) && $jsonRes->error) {
			$this->logError("Error on receiving SLP address details", $jsonRes->error);
			return null;
		}
		if (empty($jsonRes->outputs)) {
			// on an address with 0 transactions token_meta on GetAddressUnspentOutputs is empty. we call GetTokenMetadata explicitly
			$jsonRes->token_metadata = $this->getTokenMetadataRaw($tokenID);
		}
		if (!isset($jsonRes->token_metadata) || empty($jsonRes->token_metadata)) {
			$this->logError("Missing token metadata on SLP address details", $jsonRes);
			return null;
		}
		
		$token = new SlpToken();
		$token->id = $tokenID;
		$this->addTokenMetadata($token, $jsonRes->token_metadata);
		$slpAddress = SlpTokenAddress::withToken($token, $address);
		if (!isset($jsonRes->outputs) || empty($jsonRes->outputs)) // grpc proxy returns empty array []
			return $slpAddress;
		
		foreach ($jsonRes->outputs as $output) {
			if (!isset($output->slp_token))
				continue;
			$curTokenID = bin2hex(base64_decode($output->slp_token->token_id));
			if ($curTokenID !== $tokenID)
				continue;
			$slpAddress->balance += (int)$output->slp_token->amount;
		}
		if ($slpAddress->decimals > 0)
			$slpAddress->balance /= pow(10, $slpAddress->decimals);
		$slpAddress->transactions = $this->getAddressTransactions($address);
		
		return $slpAddress;
	}
	
	public function getTransaction(string $transactionID): ?Transaction {
		$tx = $this->getTransactionDetails($transactionID);
		if (empty($tx) || !isset($tx->transaction) || (isset($tx->error) && !empty($tx->error)))
			return null;
		
		$txID = bin2hex(base64_decode($tx->transaction->hash));
		$transaction = new Transaction($txID);
		foreach ($tx->transaction->inputs as $in) {
			$input = new TransactionInput();
			if (isset($in->index)) // not present on index == 0
				$input->index = $in->index;
			$input->value = intval($in->value);
			$input->address = 'bitcoincash:' . $in->address;
			if (isset($in->slp_token))
				$this->addSlpTransactionData($input, $in->slp_token);
			$transaction->inputs[] = $input;
		}
		foreach ($tx->transaction->outputs as $out) {
			$output = new TransactionOutput();
			if (isset($out->index)) // not present on index == 0
				$output->index = $out->index;
			if (isset($out->value)) // missing on OP_RETURN outputs (value 0)
				$output->value = intval($out->value);
			if (isset($out->address) && !empty($out->address)) // missing on OP_RETURN outputs (value 0)
				$output->address = 'bitcoincash:' . $out->address;
			if (isset($out->slp_token))
				$this->addSlpTransactionData($output, $out->slp_token);
			$transaction->outputs[] = $output;
		}
		
		return $transaction;
	}
	
	protected function getTransactionDetails(string $transactionID): ?\stdClass {
		$transactionID = static::ensureBase64Encoding($transactionID);
		if (isset($this->transactionCache[$transactionID]))
			return $this->transactionCache[$transactionID];
		
		$url = $this->blockchainApiUrl . 'GetTransaction';
		$data = array(
				'hash' => $transactionID,
				'include_token_metadata' => true
		);
		$response = $this->httpAgent->post($url, $data);
		if ($response === false)
			return null;
		$jsonRes = json_decode($response);
		if ($jsonRes)
			$this->transactionCache[$transactionID] = $jsonRes;
		// {"error":"transaction not found","code":5,"message":"transaction not found"}
		return $jsonRes;
	}
	
	protected function getTokenMetadataRaw(string $tokenID): array {
		$tokenIDBase64 = static::ensureBase64Encoding($tokenID, false);
		$url = $this->blockchainApiUrl . 'GetTokenMetadata';
		$data = array(
				'token_ids' => array($tokenIDBase64),
		);
		$response = $this->httpAgent->post($url, $data);
		if ($response === false)
			return array();
		$jsonRes = json_decode($response);
		if ($jsonRes === null)
			return array();
		else if (isset($jsonRes->error) && $jsonRes->error === true)
			return array();
		else if (empty($jsonRes->token_metadata))
			return array();
		return  $jsonRes->token_metadata; // array with 1 element
	}
	
	protected function getAddressTransactions(string $address, $confirmedOnly = false): array {
		$transactions = array();
		$url = $this->blockchainApiUrl . 'GetAddressTransactions';
		$data = array(
				'address' => $address,
				'nb_skip' => 0,
				'nb_fetch' => 0,
				//'hash' => '',
				'height' => 0
		);
		$response = $this->httpAgent->post($url, $data);
		if ($response === false)
			return $transactions;
		$jsonRes = json_decode($response);
		if ($jsonRes === null)
			return $transactions;
		if (isset($jsonRes->confirmed_transactions)) {
			foreach ($jsonRes->confirmed_transactions as $tx) {
				if (!isset($tx->hash))
					continue;
				$txHex =  CashP::reverseBytes(bin2hex(base64_decode($tx->hash)));
				$transactions[] = $txHex;
			}
		}
		if ($confirmedOnly === false && isset($jsonRes->unconfirmed_transactions)) {
			foreach ($jsonRes->unconfirmed_transactions as $tx) {
				if (!isset($tx->transaction) || !isset($tx->transaction->hash))
					continue;
				$txHex =  CashP::reverseBytes(bin2hex(base64_decode($tx->transaction->hash)));
				$transactions[] = $txHex;
			}
		}
		
		return $transactions;
	}
	
	protected function addTokenMetadata(SlpToken $token, array $bchdTokenMetadata): void {
		foreach ($bchdTokenMetadata as $meta) {
			$id = bin2hex(base64_decode($meta->token_id));
			if ($id !== $token->id)
				continue;
			
			// Type 1 and NFT1 Group types same, its just the versionType field is 0x01 and 0x81
			// NFT1 Child different
			$type = $meta->token_type;
			$typeKey = "type$type";
			$typeInfo = $meta->$typeKey;
			$token->symbol = base64_decode($typeInfo->token_ticker);
			$token->name = base64_decode($typeInfo->token_name);
			if (isset($typeInfo->decimals)) // not present with all tokens
				$token->decimals = $typeInfo->decimals;
			else
				//$token->decimals = 9;
				throw new \Exception("SLP token must return number of decimals from BCHD - tokenID: $id");
			
			// add the token type
			if (isset($meta->type1))
				$token->type = 'type1';
			else if (isset($meta->nft1_group))
				$token->type = 'nft1_group';
			else if (isset($meta->nft1_child))
				$token->type = 'nft1_child';
			return;
		}
		$this->logError("Unable to find desired token metadata", $bchdTokenMetadata);
	}
	
	protected function addSlpTransactionData(TransactionBaseData $tx, \stdClass $rawSlpData): void {
		if (empty($rawSlpData || empty($rawSlpData->token_id)))
			return;
		
		$tx->slp = new SlpTransactionData();
		$tx->slp->tokenID = bin2hex(base64_decode($rawSlpData->token_id));
		$tx->slp->amount = intval($rawSlpData->amount);
		if (isset($rawSlpData->address) && !empty($rawSlpData->address)) // not present on inputs
			$tx->slp->address = 'simpleledger:' . $rawSlpData->address;
		if (isset($rawSlpData->decimals)) // not present on inputs
			$tx->slp->decimals = intval($rawSlpData->decimals);
	}
	
	protected static function ensureBase64Encoding(string $hash, bool $reverseBytes = true): string {
		if (preg_match("/^[0-9a-f]+$/i", $hash) !== 1)
			return $hash;
		// BCHD wants TX hashes in reverse order as shown on block explorer
		if ($reverseBytes === true)
			$hash = CashP::reverseBytes($hash);
		// the protobuf gateway expects all byte slices in base64 encoding
		return CashP::hexToBase64($hash);
	}
}
?>