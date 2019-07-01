<?php
// This file prints the HTML of a BadgerWallet button.
// Badger buttons can be used to send BCH and SLP tokens.

include '../cashp.php'; // uncomment this if you are not using Composer

use Ekliptor\CashP\CashP;

// user variables
$receiveAddress = "bitcoincash:qpfpalf9dn35g8kl74rywqfe8xqpwcwjsvvwycwepg"; // you can use CashP to generate addresses, see pay-bch.php
$requestAmountBCH = 0.00002;

$cashp = new CashP();
$btnConf = array(
		'text' => 'Pay now',
		'callback' => 'onBadgerPayment'
);
// $btnConf is 1st parameter and the rest are the same parameters as createPaymentURI()
$buttonHtml = $cashp->getBadgerButton($btnConf, $receiveAddress, $requestAmountBCH);
?>

<html>
<head>
  <title>BadgerButton demo</title>
</head>
<body>
<p>Click the button to send BCH or SLP tokens</p>
<?php echo $buttonHtml;?>
<script>
function onBadgerPayment(txid) {
	console.log("Badger payment success with TXID: " + txid);
}
</script>
</body>
</html>