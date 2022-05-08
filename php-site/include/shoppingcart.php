<?

class shoppingcart{

	var $db;
/*
tables:
 -productcats
 -productinputchoices
 -productpics
 -productprices
 -products
 -producttext
 -shoppingcart
 -invoice
 -invoiceitems
*/

	var $paymentmethods = array(
								'debit' => "Debit",
								'cash' => "Cash",
								'cheque' => "Cheque",
								'mc' => "MC",
								'moneyorder' => "Money Order",
								'paypal' => "Paypal",
								'visa' => "Visa",
								'payg' => "Voucher",
								'emailmoneytransfer' => "EMT",
								);

	var $paymentcontacts = array(
									"Petra",
									"Mail",
									"Blue Shift Gaming",
									"Glasshouse",
									"Vitamin Guy",
									"Melina",
									"Timo",
									"Melissa",
									);

	function shoppingcart( & $db ){
		$this->db = & $db;
	}

//product categories
	function insertcat($name, $description){
		$this->db->prepare_query("INSERT INTO productcats SET name = ?, description = ?, priority = ?", $name, $description, getMaxPriority($this->db, "productcats"));
	}

	function updatecat($id, $name, $description){
		$this->db->prepare_query("UPDATE productcats SET name = ?, description = ? WHERE id = ?", $name, $description, $id);
	}

	function deletecat($id){
		global $msgs;

		$this->db->prepare_query("SELECT id FROM products WHERE category = ?", $id);

		if($this->db->numrows()){
			$msgs->addMsg("This category is not empty");
			return false;
		}

		setMaxPriority($this->db, "productcats", $id);
		$this->db->prepare_query("DELETE FROM productcats WHERE id = ?", $id);
	}

	function moveupcat($id){
		increasepriority($this->db, "productcats", $id);
	}

	function movedowncat($id){
		decreasepriority($this->db, "productcats", $id);
	}

//products
	function insertproduct($data){
		global $msgs;

		$this->db->prepare_query("INSERT INTO products SET category = ?, name = ?, description = ?, active = ?, size = ?, caseqty = ?, burntime = ?, picname = ?, pricers = ?, pricerss = ?, pricerc = ?, pricercs = ?, pricewc = ?, pricewcs = ?, pricewu = ?, pricewus = ?",
				$data['category'], $data['name'], $data['description'], $data['active'], $data['size'], $data['caseqty'], $data['burntime'], $data['picname'], $data['pricers'], $data['pricerss'], $data['pricerc'], $data['pricercs'], $data['pricewc'], $data['pricewcs'], $data['pricewu'], $data['pricewus']);

		$msgs->addMsg("Item added");
	}

	function updateproduct($id, $data){
		global $msgs;

		$this->db->prepare_query("UPDATE products SET category = ?, name = ?, description = ?, active = ?, size = ?, caseqty = ?, burntime = ?, picname = ?, pricers = ?, pricerss = ?, pricerc = ?, pricercs = ?, pricewc = ?, pricewcs = ?, pricewu = ?, pricewus = ? WHERE id = ?",
				$data['category'], $data['name'], $data['description'], $data['active'], $data['size'], $data['caseqty'], $data['burntime'], $data['picname'], $data['pricers'], $data['pricerss'], $data['pricerc'], $data['pricercs'], $data['pricewc'], $data['pricewcs'], $data['pricewu'], $data['pricewus'], $id);

		$msgs->addMsg("Item Updated");
	}

	function moveupproduct($id){
		$this->db->prepare_query("SELECT category FROM products WHERE id = #", $id);

		$cat = $this->db->fetchfield();

		increasepriority($this->db, $id, "products", $this->db->prepare("category = #", $cat));
	}

	function movedownproduct($id){
		$this->db->prepare_query("SELECT category FROM products WHERE id = #", $id);

		$cat = $this->db->fetchfield();

		decreasepriority($this->db, $id, "products", $this->db->prepare("category = #", $cat));
	}

//shopping cart
	function addtoCart($id, $quantity, $input){
		global $userData, $msgs;

		$this->db->prepare_query("SELECT unitprice, bulkpricing, input, validinput FROM products WHERE id = #", $id);
		$line = $this->db->fetchrow();



		if($line['validinput'] && !$line['validinput']($input)){
			$msgs->addMsg("Invalid Input");
			return false;
		}

		$price = $line['unitprice'];

		if($line['bulkpricing'] == 'y' && $quantity > 1){
			$this->db->prepare_query("SELECT price FROM productprices WHERE productid = # && minimum <= # ORDER BY minimum DESC LIMIT 1", $id, $quantity);

			if($this->db->numrows())
				$price = $this->db->fetchfield();
		}

		$this->db->prepare_query("INSERT INTO shoppingcart SET userid = #, productid = #, quantity = #, price = ?, input = ?", $userData['userid'], $id, $quantity, $price, $input);
	}

//update!!!!

	function checkout(){
		global $userData;

		$this->db->query("LOCK TABLES shoppingcart WRITE, invoice WRITE, invoiceitems WRITE");

		$this->db->prepare_query("SELECT count(*) as count, SUM(ROUND(price*quantity,2)) as total FROM shoppingcart WHERE userid = #", $userData['userid']);

		$line = $this->db->fetchrow();

		if($line['count'] == 0){
			$this->db->query("UNLOCK TABLES");
			return false;
		}

		$this->db->prepare_query("INSERT INTO invoice SET userid = #, username = ?, creationdate = #, total = ?", $userData['userid'], $userData['username'], time(), $line['total']);

		$invoiceid = $this->db->insertid();

		$this->db->prepare_query("INSERT INTO invoiceitems (invoiceid,productid,quantity,price,input) SELECT ? as invoiceid,productid,quantity,price,input FROM shoppingcart WHERE userid = #", $invoiceid, $userData['userid']);

		$this->db->prepare_query("DELETE FROM shoppingcart WHERE userid = #", $userData['userid']);

		$this->db->query("UNLOCK TABLES");

		return $invoiceid;
	}

//invoice

	function updateInvoice($id, $method, $contact, $amount, $txnid, $completed){
		$this->db->prepare_query("UPDATE invoice SET txnid = ?, paymentdate = #, paymentmethod = ?, paymentcontact = ?, amountpaid = ? WHERE id = #",
			$txnid, time(), $method, $contact, $amount, $id);

		$output = "";

		if($completed)
			$output = $this->completeinvoice($id);

		return $output;
	}

	function completeinvoice($id){

		$this->db->query("LOCK TABLES invoice WRITE");

		$this->db->prepare_query("SELECT completed FROM invoice WHERE id = #", $id);

		$line = $this->db->fetchrow();

		if($line['completed'] == 'y'){
			$this->db->query("UNLOCK TABLES");
			return "Already complete";
		}

		$this->db->prepare_query("UPDATE invoice SET completed = 'y' WHERE id = #", $id);

		$this->db->query("UNLOCK TABLES");

		$result = $this->db->prepare_query("SELECT products.callback, invoiceitems.input, invoiceitems.quantity FROM invoiceitems, products WHERE invoiceitems.productid=products.id && invoiceitems.invoiceid = #", $id);

		$output = "";

		while($line = $this->db->fetchrow($result))
			if(!empty($line['callback']))
				$output .= $line['callback']($line['input'], $line['quantity']) . "\n"; //do callbacks

		return $output . "Completed";
	}
}
