<?php
namespace Ekliptor\CashP;

use Ekliptor\CashP\BlockchainApi\Http\AbstractHttpAgent;

class CurrencyRate {
	/** @var int */
	public $timestamp = 0;
	/** @var float */
	public $rate = 0.0;
	
	public function __construct(float $rate) {
		$this->rate = $rate;
		$this->timestamp = time();
	}
}

/**
 * This class crawls and caches currency exchange rates.
 *
 */
class ExchangeRate {
	/** @var int */
	protected $exchangeRateExpirationMin;
	/** @var AbstractHttpAgent */
	protected $httpAgent;
	/** @var array */
	protected $cache = array(); // (currency string, CurrencyRate)
	
	public function __construct(AbstractHttpAgent $httpAgent, int $exchangeRateExpirationMin) {
		$this->httpAgent = $httpAgent;
		$this->exchangeRateExpirationMin = $exchangeRateExpirationMin;
	}
	
	/**
	 * Get the current exchange rate for BCH.
	 * @param string $currency A fiat currency such as USD|EUR|JPY
	 * @return float
	 */
	public function getRate(string $currency = "USD"): float {
		if (isset($this->cache[$currency]) && $this->cache[$currency]->timestamp + $this->exchangeRateExpirationMin*60 > time())
			return $this->cache[$currency]->rate;
		
		$res = $this->httpAgent->get('https://index-api.bitcoin.com/api/v0/cash/price/usd');
		if ($res === false) {
			return 0.0;
		}
		$json = json_decode($res);
		if ($json === null || empty($json->price) || $json->price <= 0.0) {
			return 0.0;
		}
		$this->cache[$currency] = new CurrencyRate($json->price);
		return $this->cache[$currency]->rate;
	}
}
?>