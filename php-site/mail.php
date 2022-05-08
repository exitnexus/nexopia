<?


	$data = $_POST;


	ob_start();

	echo "Post data:\n";
	print_r($_POST);

	echo "\n\nCheck data:\n";
	print_r($result);

	$output = ob_get_clean();

	mail("Timo <timo@enternexus.com>", "IPN Paypal notification", $output, "From: ipn <nobody@enternexus.com>");



