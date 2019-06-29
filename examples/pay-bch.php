<?php
// This demo creates a new BCH address from your xPub to be used as a 1-time payment address.
// It then prints the address along with a QR code containing the payiment URI.

//include '../cashp.php'; // uncomment this if you are not using Composer

use Ekliptor\CashP\BlockchainApi\AbstractBlockchainApi;
use Ekliptor\CashP\BlockchainApi\Http\BasicHttpAgent;
use Ekliptor\CashP\CashP;
use Ekliptor\CashP\CashpOptions;


// user variables
$xPub = "xpub6CphSGwqZvKFU9zMfC3qLxxhskBFjNAC9imbSMGXCNVD4DRynJGJCYR63DZe5T4bePEkyRoi9wtZQkmxsNiZfR9D6X3jBxyacHdtRpETDvV";
$requestAmountBCH = 0.002;
$addressCounter = 1; // increment this and store it (in database) to generate unique addresses
$qrCodeFile = "./example-qr.png";


// setup library
$cashpOptions = new CashpOptions();
$cashpOptions->httpAgent = new BasicHttpAgent(function (string $subject, $error, $data = null) {
	// immplementing logger functions is optional. If omitted, all errors will be printed to stdOut
	echo "HTTP error: " . $subject;
});
$cashp = new CashP($cashpOptions);
AbstractBlockchainApi::setLogger(function (string $subject, $error, $data = null) {
	echo "BCH API error: " . $subject;
});

// now you can use the API (mutliple calls possible)
$address = $cashp->getBlockchain()->createNewAddress($xPub, $addressCounter);
print_r($address);

@unlink($qrCodeFile); // ensure it doesn't exist for this example
print_r($cashp->generateQrCodeForAddress($qrCodeFile, $address->cashAddress, $requestAmountBCH));
echo '<img src="example-qr.png" alt="qr-code">' . "\n";

// check the address balance (inlcuding TX)
$addressUpdated = $cashp->getBlockchain()->getAddressDetails($address->cashAddress);
print_r($addressUpdated);

// check the number of confirmations (of the 1st transaction)
if (count($addressUpdated->transactions) !== 0)
	echo "Confirmations: " . $cashp->getBlockchain()->getConfirmationCount($addressUpdated->transactions[0]) . "\n";
?>