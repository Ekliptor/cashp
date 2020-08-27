<?php
declare(strict_types=1);
namespace Ekliptor\CashP\Tests;

use PHPUnit\Framework\TestCase;
use Ekliptor\CashP\CashP;
use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\CashpOptions;

final class RestBackendTest extends TestCase {
	public function testCurrencyRateFetch(): void {
		$cashp = $this->getCashpForTesting();
		$usdRate = $cashp->getRate()->getRate("USD");
		$this->assertIsFloat($usdRate, "Returned currency rate is not of type float.");
		$this->assertGreaterThan(0.0, $usdRate, "Currency rate can not be negative");
	}
	
	public function testTokenBalance(): void {
		$cashp = $this->getCashpForTesting();
		$tokenID = "7278363093d3b899e0e1286ff681bf50d7ddc3c2a68565df743d0efc54c0e7fd";
		$address = "simpleledger:qrg3pzge6lhy90p4semx2a60w6624nudagqzycecg4";
		$tokenBalance = $cashp->getBlockchain()->getAddressTokenBalance($address, $tokenID);
		$this->assertGreaterThan(0.0, $tokenBalance, "Test token balance is empty");
	}
	
	public function testAddressCreation(): void {
		$cashp = $this->getCashpForTesting();
		$xPub = "xpub6CphSGwqZvKFU9zMfC3qLxxhskBFjNAC9imbSMGXCNVD4DRynJGJCYR63DZe5T4bePEkyRoi9wtZQkmxsNiZfR9D6X3jBxyacHdtRpETDvV";
		$address = $cashp->getBlockchain()->createNewAddress($xPub, 3);
		$this->assertInstanceOf(BchAddress::class, $address, "BCH address creation failed");
	}
	
	protected function getCashpForTesting(): CashP {
		$opts = new CashpOptions();
		$opts->blockchainApiImplementation = 'BitcoinComRestApi';
		return new CashP($opts);
	}
}
?>