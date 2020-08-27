<?php
declare(strict_types=1);
namespace Ekliptor\CashP\Tests;

use PHPUnit\Framework\TestCase;
use Ekliptor\CashP\CashP;
use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\BlockchainApi\Http\BasicHttpAgent;
use Ekliptor\CashP\BlockchainApi\Http\CurlHttpAgent;
use Ekliptor\CashP\BlockchainApi\BchdProtoGatewayApi;
use Ekliptor\CashP\CashpOptions;

final class BchdBackendTest extends TestCase {
	public function testCurrencyRateFetch(): void {
		$cashp = $this->getCashpForTesting();
		// this uses index-api.bitcoin.com but we keep the test here to ensure it works with BCHD too
		$usdRate = $cashp->getRate()->getRate("USD");
		$this->assertIsFloat($usdRate, "Returned currency rate is not of type float.");
		$this->assertGreaterThan(0.0, $usdRate, "Currency rate can not be negative");
	}
	
	public function testConfirmationCount(): void {
		$cashp = $this->getCashpForTesting();
		$txHash = 'ca87043999ad7c441193ced336577b4ba50fc7a45fbaf6c0bbda825cc42d7fc5';
		$tokenBalance = $cashp->getBlockchain()->getConfirmationCount($txHash);
		$this->assertGreaterThan(0, $tokenBalance, "Number of confirmations must be greater than 0 for TXID: $txHash");
	}
	
	public function testBlocktime(): void {
		$cashp = $this->getCashpForTesting();
		$txHash = 'ca87043999ad7c441193ced336577b4ba50fc7a45fbaf6c0bbda825cc42d7fc5';
		$timestamp = $cashp->getBlockchain()->getBlocktime($txHash);
		$this->assertEquals(1593197218, $timestamp, "Block timestamp must be $timestamp for TXID: $txHash");
	}
	
	public function testTokenInfo(): void {
		$cashp = $this->getCashpForTesting();
		$tokenID = '7278363093d3b899e0e1286ff681bf50d7ddc3c2a68565df743d0efc54c0e7fd';
		$info = $cashp->getBlockchain()->getTokenInfo($tokenID);
		$this->assertIsObject($info, "Token Info must be an object.");
		$this->assertEquals("WPT", $info->symbol, "Token Ticker symbol must be 'WPT'");
	}
	
	public function testAddressBalance(): void {
		$cashp = $this->getCashpForTesting();
		$balance = $cashp->getBlockchain()->getAddressBalance('bitcoincash:qz7j7805n9yjdccpz00gq7d70k3h3nef9yj0pwpelz');
		$this->assertGreaterThan(0.0, $balance, 'BCH address balance must be greater than 0.');
	}
	
	public function testAddressTokenBalance(): void {
		$cashp = $this->getCashpForTesting();
		$tokenID = '0be40e351ea9249b536ec3d1acd4e082e860ca02ec262777259ffe870d3b5cc3';
		$balance = $cashp->getBlockchain()->getAddressTokenBalance('simpleledger:qz7j7805n9yjdccpz00gq7d70k3h3nef9y75245epu', $tokenID);
		$this->assertGreaterThan(0.0, $balance, 'SLP address balance must be greater than 0.');
	}
	
	public function testGetSlpAddressDetails(): void {
		$cashp = $this->getCashpForTesting();
		$tokenID = '0be40e351ea9249b536ec3d1acd4e082e860ca02ec262777259ffe870d3b5cc3';
		$address = $cashp->getBlockchain()->getSlpAddressDetails('simpleledger:qz7j7805n9yjdccpz00gq7d70k3h3nef9y75245epu', $tokenID);
		$this->assertEquals($tokenID, $address->id, "SLP token ID must be: $tokenID");
	}
	
	public function testAddressCreation(): void {
		$cashp = $this->getCashpForTesting();
		$xPub = "xpub6CphSGwqZvKFU9zMfC3qLxxhskBFjNAC9imbSMGXCNVD4DRynJGJCYR63DZe5T4bePEkyRoi9wtZQkmxsNiZfR9D6X3jBxyacHdtRpETDvV";
		$address = $cashp->getBlockchain()->createNewAddress($xPub, 3);
		$this->assertInstanceOf(BchAddress::class, $address, "BCH address creation failed");
	}
	
	protected function getCashpForTesting(): CashP {
		$opts = new CashpOptions();
		$opts->blockchainApiImplementation = 'BchdProtoGatewayApi';
		$opts->httpAgent = new BasicHttpAgent();
		return new CashP($opts);
	}
}
?>