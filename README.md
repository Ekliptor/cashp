# CashP - PHP library for Bitcoin Cash (BCH) and SLP token payments
This is a PHP library to enable [BitCoin Cash (BCH)](https://www.bitcoincash.org/) and [SLP token](https://simpleledger.cash/) payments.
You can easily use this with WordPress, Laravel and other PHP frameworks.

#### Installation
With composer (recommended):
```
composer require "ekliptor/cashp"
```

Manual installation:

1. Download the source code and include `cashp.php` from the root directory of this library.
2. Download the source of this [PHP QR-code package](https://github.com/Ekliptor/qr-code) and extract it to `src/phpqrcode`

#### Requirements
```
PHP >= 7.1
```

## Features
* on-chain payments without going through a 3rd party
* configurable open-source API backends such as: [rest.bitcoin.com](https://github.com/Bitcoin-com/rest.bitcoin.com) or [BCHD](https://github.com/gcash/bchd)
* 1-time address generation for payments using xPub (so your wallet's private key is not stored on the server)
* generate QR codes with BCH and SLP payment URIs

## Docs
Take a look at [code examples](https://github.com/Ekliptor/cashp/tree/master/examples).

#### CashP class
##### __construct(CashpOptions $options = null)
Create the main API class.
* `CashpOptions $options` - (optional) API options (see below)

##### getRate(): ExchangeRate 
Return the exchange rate API.

##### getBlockchain(): AbstractBlockchainApi
Return the Blockchain API to generate addresses, check balances, transactions,...

##### generateQrCodeForAddress(string $fileLocal, string $address, float $amountBCH, float $amountToken = 0.0, string $tokenID = ""): bool
Generate a QR code for a payment.
* `string $fileLocal` - A path on your local filesystem to store the QR code file. This should be accessible from the web if you want to display the QR code to users.
If the given file already exists it will NOT be overwritten (QR codes are meant to be generated & cached in your web temp directory).
* `string $address` - The (1-time) BCH (or SLP) address created for this payment.
* `float $amountBCH` - The amount in BCH. Can be 0 if the user pays the full amount in SLP tokens. 
* `float $amountToken` - (optional) The amount of the optional token to be received.
* `string $tokenID` - (optional) The hex ID of the SLP token. Required if $amountToken > 0.

returns `bool` - true on success, false otherwise

---

##### createPaymentURI(string $address, float $amountBCH, float $amountToken = 0.0, string $tokenID = ""): string
Return a payment URI (starting with "bitcoincash:" or "simpleledger:" if $amountToken > 0) for the given $address.
* `string $address` - The receiving BCH (or SLP) address.
* `float $amountBCH` - The amount in BCH to receive.
* `float $amountToken` - (optional) The amount of SLP tokens to receive.
* `string $tokenID` - (optional) The hex ID of the SLP token. Required if $amountToken > 0.

returns `string`

---

##### getBadgerButton(array $btnConf, string $address, float $amountBCH, float $amountToken = 0.0, string $tokenID = ""): string
Get the HTML code of a BadgerButton. See https://badger.bitcoin.com/
* `array $btnConf` - associative array with buttom config
    * text (string) The text of the button
    * callback (string, optional) The name of a callback function present on the global window to be called after payment. Parameters: string chainTxID
    * cssClass (string, optional) Additional CSS classes for the button.
    * forceIndludeJs (bool, optional) default false - Include the JavaScript library again. Only use this if you are generating HTML for multiple pages.
* `string $address` - The receiving BCH (or SLP) address.
* `float $amountBCH` - The amount in BCH to receive.
* `float $amountToken` - (optional) The amount of SLP tokens to receive.
* `string $tokenID` - (optional) The hex ID of the SLP token. Required if $amountToken > 0.

returns `string` - The button HTML.

---

##### isValidBchAddress(string $bchAddress): bool
Check if a BCH address is valid.
* `string $bchAddress` - The address in CashAddress format starting with 'bitcoincash:'

returns `bool` - True if the address is valid, false otherwise.

---

##### isValidSlpAddress(string $slpAddress): bool
Check if a SLP address is valid.
* `string $slpAddress` - The address starting with 'simpleledger:'

returns `bool` - True if the address is valid, false otherwise.

---


#### CashpOptions class
A set of advanced config properties.
* `$httpAgent = null` - The HTTP implementation used to make HTTP requests.
Values: BasicHttpAgent|CurlHttpAgent|WordpressHttpAgent
Defaults to BasicHttpAgent, but you should use a better one according to your PHP setup.
* `$exchangeRateExpirationMin = 60` - How long the crawled exchange rate shall stay in cache. Currently rates are not saved beyond script execution.
* `$httpTimeoutSec = 10` - The timeout for HTTP requests to the REST API backend.
* `$blockchainApiImplementation = "BitcoinComRestApi"` - The REST API backend implementation to use. Allowed values: BitcoinComRestApi|BchdProtoGatewayApi|SlpDbApi


#### ExchangeRate class
An API to get BCH exchanges rates to fiat currencies.

##### getRate(string $currency = "USD"): float
Get the current exchange rate for BCH.
* `string $currency` - A fiat currency such as USD|EUR|JPY

returns `float`

---


#### BlockchainApi class
The Blockchain API to generate addresses, check balances, transactions,...

##### static setLogger(callable $loggerFn): void
Set a logger function for errors and debug output. Use this to write to a logfile or database.
If no function is provided everything will be printed using 'echo'.
* `callable $loggerFn(string $subject, mixed $error, mixed $data = null)` - parameters of the PHP callable

##### setHttpAgent(AbstractHttpAgent $agent): void
Set a a HTTP implementation for requests (cURL, Wordpress HTTP API,...)
* `AbstractHttpAgent $agent` - 

##### getConfirmationCount(string $transactionID): int
Return the number of confirmation for the given blockchain transaction ID.
* `string $transactionID` - 

returns `int` - The number of confirmations or -1 if the $transactionID doesn't exist.

---

##### createNewAddress(string $xPub, int $addressCount, string $hdPathFormat = '0/%d'): ?BchAddress
Creates a new address from the xPub.
* `string $xPub` - The extended public key. Called 'Master Public Key' in Electron Cash.
* `int $addressCount` - The number of the next address to generate a unique address. Usually this should be an incrementing integer.
* `string $hdPathFormat` - (optional) The HD path to be used for creating address children.

returns `BchAddress` - the address or `null` on failure

---

##### getTokenInfo(string $tokenID): ?SlpToken
Get general (network-wide) info about a SLP token.
* `string $tokenID` - 

returns `SlpToken` - The token or `null` on failure

---

##### getAddressBalance(string $address): float
Return the BCH balance of the given address (including unconfirmed transactions).
* `string $address` - The BCH address in CashAddress format.

returns `float` - The balance or -1 if the address doesn't exist.

---

##### getAddressTokenBalance(string $address, string $tokenID): float
Return the token balance of the given SLP address (including unconfirmed transactions).
* `string $address` - 
* `string $tokenID` - 

returns `float` - The balance or -1 if the address doesn't exist.

---

##### getAddressDetails(string $address): ?BchAddress
Return the BCH Address with all its properties such as balance, TXIDs,...
* `string $address` - The BCH address in CashAddress format.

returns `BchAddress` - the address or `null` on failure

---

##### getSlpAddressDetails(string $address, string $tokenID): ?SlpTokenAddress
Return the SLP token details of a given address include balance, TXIDs,...
* `string $address` - 
* `string $tokenID` - 

returns `SlpTokenAddress` - The token or `null` on failure

---


## Testing
To run unit tests type the following command in the project root directory (requires PHPUnit, installed automatically with Composer):

`./vendor/bin/phpunit --bootstrap vendor/autoload.php tests`


## ToDo
* add SLP address verification
* implement more functions of the REST API
* implement address creation using xPub with SLPDB (after it's supported)


## Contact
[Twitter](https://twitter.com/ekliptor)

[WordPress plugin](https://cashtippr.com/)
