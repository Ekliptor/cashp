<?php
namespace Ekliptor\CashP;

use Ekliptor\CashP\BlockchainApi\Http\AbstractHttpAgent;


/**
 * Configuration options for the library.
 *
 */
class CashpOptions {
	// ===== BASIC OPTIONS =====
	/**
	 * The extended public key (xPub) used to generate new addresses.
	 * In Electron Cash this can be found under Wallet -> Information -> Master Public Key
	 * @var string
	 */
	//public $xPub = "";
	
	/**
	 * The HTTP implementation used to make HTTP requests.
	 * Values: BasicHttpAgent|CurlHttpAgent|WordpressHttpAgent
	 * Defaults to BasicHttpAgent, but you should use a better one according to your PHP setup.
	 * @var AbstractHttpAgent
	 */
	public $httpAgent = null;
	
	
	// ===== ADVANCED OPTIONS =====
	/**
	 * How long the crawled exchange rate shall stay in cache.
	 * Currently rates are not saved beyond script execution. // TODO add support for backend caching using redis, memcached, mysql (Laravel cache abstraction),...
	 * @var int
	 */
	public $exchangeRateExpirationMin = 60;
	
	/** 
	 * The timeout for HTTP requests to the REST API backend.
	 * @var int
	 */
	public $httpTimeoutSec = 10;
	
	/** The format to create new wallet addreses using xPub. This must contain %d for the incrementing address
	 * counter starting with $addressStart.
	 * @var string
	 */
	//public $hdPathFormat = "0/%d";
	
	/**
	 * The REST API backend implementation to use. Allowed values: BitcoinComRestApi|BchdProtoGatewayApi|SlpDbApi
	 * @var string
	 */
	public $blockchainApiImplementation = "BitcoinComRestApi";
	
	/**
	 * The URL of the chosen $blockchainApiImplementation REST API to query. Leave empty to use each implementations default value.
	 * @var string
	 */
	public $blockchainApiUrl = "";
	
	/**
	 * The first value to pass into $hdPathFormat when creating a new address.
	 * @var integer
	 */
	//public $addressStart = 0;
}
?>