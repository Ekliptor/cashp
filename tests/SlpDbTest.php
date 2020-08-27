<?php
declare(strict_types=1);
namespace Ekliptor\CashP\Tests;

use PHPUnit\Framework\TestCase;
use Ekliptor\CashP\CashP;
use Ekliptor\CashP\BlockchainApi\Structs\BchAddress;
use Ekliptor\CashP\CashpOptions;

final class SlpDbTestTest extends TestCase {
	public function testGetTokenInfo(): void {
		$cashp = $this->getSlpDbCashP();
		$tokenInfo = $cashp->getBlockchain()->getTokenInfo('c4b0d62156b3fa5c8f3436079b5394f7edc1bef5dc1cd2f9d0c4d46f82cca479');
		$this->assertNotEmpty($tokenInfo->id, "The returned SLP token has no ID");
		$this->assertNotEmpty($tokenInfo->name, "The returned SLP token has no name");
		
		$details = $cashp->getBlockchain()->getSlpAddressDetails('simpleledger:qqk4nvsryxlr40fduqys60552ah5f5mvrqss5r8ez0', 'c4b0d62156b3fa5c8f3436079b5394f7edc1bef5dc1cd2f9d0c4d46f82cca479');
		$this->assertNotEmpty($details->transactions, "Transaction IDs of address are empty.");		
	}
	
	public function testAddressBalance(): void {
		$cashp = $this->getSlpDbCashP();
		$balance = $cashp->getBlockchain()->getAddressTokenBalance('simpleledger:qqk4nvsryxlr40fduqys60552ah5f5mvrqss5r8ez0', 'c4b0d62156b3fa5c8f3436079b5394f7edc1bef5dc1cd2f9d0c4d46f82cca479');
		$this->assertGreaterThan(0.0, $balance, "SLP token balance on address is 0.");
	}
	
	public function testTransactionResult(): void {
		$txid = '2e8222cc5ed6f90f8c419333f32bf09d9a701f780ddb49341f2690792fb94047';
		$cashp = $this->getSlpDbCashP();
		$confirmations = $cashp->getBlockchain()->getConfirmationCount($txid);
		$this->assertGreaterThan(1, $confirmations, "TX has 0 confirmations.");
		
		$blocktimeSec = $cashp->getBlockchain()->getBlocktime($txid);
		$this->assertEquals(1567707868, $blocktimeSec, "The blocktime (UNIX timestamp) of the TX does not match 1567707868.");
	}
	
	protected function getSlpDbCashP() {
		$options = new CashpOptions();
		$options->blockchainApiImplementation = 'SlpDbApi';
		return new CashP($options);
	}
}
?>