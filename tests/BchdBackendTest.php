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
		$this->assertNotEmpty($address->transactions, 'SLP address has no transactions');
	}
	
	public function testAddressCreation(): void {
		$cashp = $this->getCashpForTesting();
		$xPub = "xpub6CphSGwqZvKFU9zMfC3qLxxhskBFjNAC9imbSMGXCNVD4DRynJGJCYR63DZe5T4bePEkyRoi9wtZQkmxsNiZfR9D6X3jBxyacHdtRpETDvV";
		$address = $cashp->getBlockchain()->createNewAddress($xPub, 3);
		$this->assertInstanceOf(BchAddress::class, $address, "BCH address creation failed");
	}
	
	public function testGetTransaction(): void {
		$cashp = $this->getCashpForTesting();
		$txHash = 'ca87043999ad7c441193ced336577b4ba50fc7a45fbaf6c0bbda825cc42d7fc5';
		$tx = $cashp->getBlockchain()->getTransaction($txHash);
		if (count($tx->outputs) !== 2)
			$this->fail("expected 2 outputs in TX $txHash");
		$this->assertEquals(5027, $tx->outputs[1]->value, "TX $txHash output has wrong value");
		
		$returnAddress = $cashp->getReturnAddress($tx);
		$this->assertEquals('bitcoincash:qpwk4x4pz7xd5mxg7w40v95vhjk2qcuawsmusryd7y', $returnAddress, 'wrong BCH return address');
	}
	
	public function testGetSlpTransaction(): void {
		$cashp = $this->getCashpForTesting();
		$txHash = '1407222af22676f9706847b629d26350eb118c8763875a656abbc3f5df786d18';
		$tx = $cashp->getBlockchain()->getTransaction($txHash);
		if (count($tx->outputs) !== 4)
			$this->fail("expected 4 outputs in TX $txHash");
		$this->assertEquals(806745, $tx->outputs[3]->value, "TX $txHash output has wrong value");
		
		$returnAddress = $cashp->getReturnSlpAddress($tx);
		$this->assertEquals('simpleledger:qzapwgc088xj9hf8pcsrzsey8j7svcqysyp9ygxmq8', $returnAddress, 'wrong SLP return address');
	}
	
	public function testGetTokenMetadata(): void {
		$cashp = $this->getCashpForTesting();
		
		// on an address with 0 transactions token_meta on GetAddressUnspentOutputs is empty. call GetTokenMetadata explicitly
		$slpAddress = 'simpleledger:qpfdgdftjj43f9fhzm2k4ysrcuwlae2l3vd4pvmhy7';
		$tokenID = 'c4b0d62156b3fa5c8f3436079b5394f7edc1bef5dc1cd2f9d0c4d46f82cca479';
		$address = $cashp->getBlockchain()->getSlpAddressDetails($slpAddress, $tokenID);
		$this->assertEquals('c4b0d62156b3fa5c8f3436079b5394f7edc1bef5dc1cd2f9d0c4d46f82cca479', $address->id, 'invalid token ID returned from API');
		$this->assertEmpty($address->transactions, 'SLP ddress already has transactions. GetTokenMeta function not called.');
	}
	
	protected function getCashpForTesting(): CashP {
		$opts = new CashpOptions();
		$opts->blockchainApiImplementation = 'BchdProtoGatewayApi';
		//$opts->httpAgent = new BasicHttpAgent();
		$opts->httpAgent = new CurlHttpAgent();
		return new CashP($opts);
	}
}
?>