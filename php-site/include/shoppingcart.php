<?

class shoppingcart{

	public $db;

//tables:
// -productcats
// -productinputchoices
// -productpics
// -productprices
// -products
// -producttext
// -shoppingcart
// -invoice
// -invoiceitems


	public $paymentmethods = array(
								'debit' => "Debit",
								'cash' => "Cash",
								'cheque' => "Cheque",
								'mc' => "MC",
								'moneyorder' => "Money Order",
								'paypal' => "Paypal",
								'visa' => "Visa",
								'bp' => "Billing People",
								'payg' => "Voucher",
								'emailmoneytransfer' => "EMT",
								);

	public $paymentcontacts = array(
									"Petra",
									"Mail",
									"Blue Shift Gaming",
									"Glasshouse",
									"Vitamin Guy",
									"Melina",
									"Timo",
									"Melissa",
									"Automatic",
									"Jade",
									"Elle",
									);

	function __construct( & $db ){
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

		$res = $this->db->prepare_query("SELECT id FROM products WHERE category = ?", $id);

		if($res->fetchrow()){
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
		$res = $this->db->prepare_query("SELECT category FROM products WHERE id = #", $id);

		$cat = $res->fetchfield();

		increasepriority($this->db, $id, "products", $this->db->prepare("category = #", $cat));
	}

	function movedownproduct($id){
		$res = $this->db->prepare_query("SELECT category FROM products WHERE id = #", $id);

		$cat = $res->fetchfield();

		decreasepriority($this->db, $id, "products", $this->db->prepare("category = #", $cat));
	}

//shopping cart
	function addtoCart($id, $quantity, $input){
		global $userData, $msgs;

		$res = $this->db->prepare_query("SELECT unitprice, bulkpricing, input, validinput FROM products WHERE id = #", $id);
		$line = $res->fetchrow();



		if($line['validinput'] && !$line['validinput']($input)){
			$msgs->addMsg("Invalid Input");
			return false;
		}

		$price = $line['unitprice'];

		if($line['bulkpricing'] == 'y' && $quantity > 1){
			$res = $this->db->prepare_query("SELECT price FROM productprices WHERE productid = # && minimum <= # ORDER BY minimum DESC LIMIT 1", $id, $quantity);
			$product = $res->fetchrow();

			if($product)
				$price = $product['price'];
		}

		$this->db->prepare_query("INSERT INTO shoppingcart SET userid = #, productid = #, quantity = #, price = ?, input = ?", $userData['userid'], $id, $quantity, $price, $input);
	}

//update!!!!

	function checkout(){
		global $userData;

		$this->db->query("LOCK TABLES shoppingcart WRITE, invoice WRITE, invoiceitems WRITE");

		$res = $this->db->prepare_query("SELECT count(*) as count, SUM(ROUND(price*quantity,2)) as total FROM shoppingcart WHERE userid = #", $userData['userid']);

		$line = $res->fetchrow();

		if($line['count'] == 0){
			$this->db->query("UNLOCK TABLES");
			return false;
		}

		$this->db->prepare_query("INSERT INTO invoice SET userid = #, creationdate = #, total = ?", $userData['userid'], time(), $line['total']);

		$invoiceid = $this->db->insertid();

		$this->db->prepare_query("INSERT INTO invoiceitems (invoiceid,productid,quantity,price,input) SELECT ? as invoiceid,productid,quantity,price,input FROM shoppingcart WHERE userid = #", $invoiceid, $userData['userid']);

		$this->db->prepare_query("DELETE FROM shoppingcart WHERE userid = #", $userData['userid']);

		$this->db->query("UNLOCK TABLES");

		return $invoiceid;
	}

//invoice

	function updateInvoice($id, $method, $contact, $amount, $txnid, $completed){
		$this->db->prepare_query("UPDATE invoice SET txnid = ?, paymentmethod = ?, paymentcontact = ?, amountpaid = ? WHERE id = #",
			$txnid, $method, $contact, $amount, $id);

		$output = "";

		if($completed)
			$output = $this->completeinvoice($id);

		return $output;
	}

	function completeinvoice($id){

		$this->db->query("LOCK TABLES invoice WRITE");

		$res = $this->db->prepare_query("SELECT userid, completed FROM invoice WHERE id = #", $id);

		$invoice = $res->fetchrow();

		if($invoice['completed'] == 'y'){
			$this->db->query("UNLOCK TABLES");
			return "Already complete";
		}

		$this->db->prepare_query("UPDATE invoice SET completed = 'y', paymentdate = # WHERE id = #", time(), $id);

		$this->db->query("UNLOCK TABLES");

		$result = $this->db->prepare_query("SELECT products.callback, invoiceitems.input, invoiceitems.quantity FROM invoiceitems, products WHERE invoiceitems.productid=products.id && invoiceitems.invoiceid = #", $id);

		$output = "";

		while($line = $result->fetchrow())
			if(!empty($line['callback']))
				$output .= $line['callback'](array(	'input' => $line['input'],
													'quantity' => $line['quantity'],
													'buyer' => $invoice['userid'],
													'invoice' => $id,
												)
											) . "\n"; //do callbacks

		return $output . "Completed";
	}
}

class Basket {
	private $id;
	private $contains;
	private $payments;
	private $uid;
	private $completed;
	private $modification; //a version number for the object, it needs to be checked against the database before updates for consistency
	
	function __construct($id=0) {
		$this->contains = array();
		$this->payments = array();
		$this->uid = 0;
		$this->modification = 0;
		$this->completed = false;
		$this->id = $id;
		if ($id) {
			if (!$this->load()) {
				throw new Exception("Invalid ID");
			}
		} else {
			$this->save();	
		}
	}
	
	function setUser($uid) {
		$this->uid = $uid;
	}
	
	function getUser() {
		return $this->uid;
	}
	
	function getID() {
		return $this->id;
	}
	
	//return a decimal representation of the total
	function getTotal() {
		$total = 0;
		foreach ($this->contains as &$item) {
			$total += $item->getTotal();
		}
		return $total;
	}
	
	function completePurchase() {
		foreach ($this->contains as $item) {
			$item->completePurchase();
		}
		$this->completed = true;
		$this->save();
	}
	
	function completePayment($paymentID) {
		if (!$this->completed) {
			$total = 0;
			foreach ($this->payments as $payment) {
				$total += $payment->getAmountApproved();
			}
			if ($total > $this->getTotal()-0.005) {
				$this->completePurchase();
			}
		} else {
			$this->save();
		}
	}
	
	function addItem(Item $item) {
		foreach ($this->contains as &$currentItem) {
			if ($item->equals($currentItem)) {
				$currentItem->combine($item);
				return Item::MERGED;
			}
		}
		//if we get here we don't have a matching item in the list
		$this->contains[] = $item;
		$this->save();
		return Item::INSERTED;
	}
	
	function removeItem($itemtype) {
		foreach ($this->contains as $key => &$item) {
			if ($item->getType() == $itemtype) {
				unset($this->contains[$key]);
				$this->save();
				return true;
			}
		}
		return false; //item type not found
	}
	
	function save() { //save state to the database
		$set = array();				$params = array();
		$set[] = "uid = #";			$params[] = $this->uid;
		$set[] = "completed = ?";	$params[] = ($this->completed ? 'y' : 'n');
		Payment::$db->query("START TRANSACTION");
		if ($this->id) {
			$params[] = $this->id;
			$params[] = $this->modification;
			$result = Payment::$db->prepare_array_query("UPDATE baskets SET " . implode(", ", $set).", modification = modification+1 WHERE id = # && modification = #", $params);
			if (!$result->affectedrows()) {
				throw new Exception("Object out of date.");
			} else {
				$this->modification++;
			}
		} else {
			$result = Payment::$db->prepare_array_query("INSERT INTO baskets SET " . implode(", ", $set), $params);
			$this->id = $result->insertid();
		}
		Payment::$db->prepare_query("DELETE FROM basketcontents WHERE basketid = #", $this->id);
		foreach ($this->contains as &$item) {
			$item->save($this->id);
		}
		Payment::$db->query("COMMIT");
	} 
	
	function load() { //load state from the database
		unset($this->contains);
		$this->contains = array();
		$this->uid = 0;
		$this->completed = false;
		
		if ($this->id) {
			Payment::$db->query("START TRANSACTION");
			$result = Payment::$db->prepare_query("SELECT * FROM baskets WHERE id = #", $this->id);
			if ($line = $result->fetchrow()) {
				$this->uid = $line['uid'];
				$this->completed = $line['completed'] == 'y';
				$this->modification = $line['modification'];
				$items = Payment::$db->prepare_query("SELECT * FROM basketcontents WHERE basketid = #", $this->id);
				while ($item = $items->fetchrow()) {
					$this->contains[] = new Item($this, $item['type'], $item['quantity'], $item['subtype']);
				}
				$paymentresult = Payment::$db->prepare_query("SELECT id FROM payments WHERE basketid = #", $this->id);
				while ($payment = $paymentresult->fetchrow()) {
					$this->payments[$payment['id']] = Payment::createPayment($payment['id'],$this);
				}  
				Payment::$db->query("COMMIT");
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function showBasket($display=false) {
		$template = new template('payments/showBasket');
		$template->set('id', $this->id);
		$items = array();
		foreach ($this->contains as $item) {
			$items[] = $item->getArray();
		}
		$template->set('items', $items);
		$template->set('completed', $this->completed);
		$template->set('uid', $this->uid);
		$template->set('username', getUserName($this->uid));
		$template->set('total', number_format($this->getTotal(),2));
		$template->set('classes', array("body", "body2"));
		if ($display) {
			$template->display();
		} else {
			return $template->toString();
		}
	}
	
	function showPayments($display=false) {
		$template = new template('payments/showPayments');
		$payment_arrays = array();
		$total = 0;
		foreach ($this->payments as $payments) {
			$payment_arrays[] = $payment->getArray();
			$total += $payment->getAmountApproved();
		}
		$template->set('payments', $payment_arrays);
		$template->set('total', number_format($this->getTotal(),2));
		$template->set('classes', array("body", "body2"));
		if ($display) {
			$template->display();
		} else {
			return $template->toString();
		}
	}
	
	function showCompleted() {
		global $msgs;
		$msgs->addMsg("Thank you for completing your purchase.");
		incHeader();
		$this->showBasket(true);
		$this->showPayments(true);
		incFooter();
	}
	
	function getPayments() {
		return $this->payments;
	}
}

class Item {
	const FAILED = 0;
	const INSERTED = 1;
	const MERGED = 2;
	const PLUS = 1; //plus type #
	
	private $type;
	private $quantity;
	private $prices; //quantity=>price
	private $subtype; //item dependent, for plus it is the recipient uid
	private $basket; //the basket this item belongs to
	private $callback; //name of the callback function;
	private $name;
	
	function __construct($basket, $type, $quantity, $subtype=0) {
		$this->basket = $basket;
		$this->type = $type;
		$this->quantity = $quantity;
		$this->subtype = $subtype;
		$this->prices = array();
		Payment::$db->query("START TRANSACTION");
		$result = Payment::$db->prepare_query("SELECT * FROM products WHERE id = #", $this->type);
		$line = $result->fetchrow();
		if (!$line) {
			throw new Exception("Invalid product type.");
		}
		$this->prices[0] = $line['unitprice'];
		$this->name = $line['name'];
		$this->callback = $line['callback'];
		if ($line['bulkpricing'] == 'y') {
			$result = Payment::$db->prepare_query("SELECT * FROM productprices WHERE productid = #", $this->type);
			while ($price = $result->fetchrow()) {
				$this->prices[$price['minimum']] = $price['price'];
			}
		}
		Payment::$db->query("COMMIT");
	}
	
	function save($basketid) {
		Payment::$db->prepare_query("REPLACE INTO basketcontents SET basketid = #, type = #, subtype = #, quantity = #", $basketid, $this->type, $this->subtype, $this->quantity);
	}
	
	function getPrice() {
		$largestMinimum = 0;
		$bestPrice = 0;
		foreach ($this->prices as $minimum => $price) {
			if ($this->quantity >= $minimum && $minimum >= $largestMinimum) {
				$largestMinimum = $minimum;
				$bestPrice = $price;
			}
		}
		return $bestPrice;
	}
	
	function equals($item) {
		if (get_class($item) != get_class($this)) {
			return false;
		} elseif ($this->type != $item->type) {
			return false;
		} elseif ($this->subtype != $item->subtype) {
			return false;
		} else {
			return true;
		}
	}
	
	function getTotal() {
		return $this->getPrice()*$this->quantity;
	}
	
	function getType() {
		return $this->type;
	}
	
	function getQuantity() {
		return $this->quantity;
	}
	
	function combine(Item $item) {
		$this->quantity += $item->quantity;
	}
	
	function bulkpricing() {
		return (count($this->prices) > 1);
	}
	
	function completePurchase() {
		call_user_func(array($this, $this->callback));		
	}
	
	function getSubtypeName() {
		switch ($this->type) {
			case Item::PLUS:
				return getUserName($this->subtype);
			default:
				return $this->subtype;
		}
	}
	
	function getArray() {
		$values = array();
		$values['type'] = $this->type;
		$values['quantity'] = $this->quantity;
		$values['price'] = number_format($this->getPrice(), 2);
		$values['subtype'] = $this->subtype;
		$values['name'] = $this->name;
		$values['subname'] = $this->getSubtypeName();
		$values['total'] = number_format($this->getPrice()*$this->quantity,2);
		return $values;
	}
	/////////////////////////////////////////////
	//Callback functions for purchase completions
	/////////////////////////////////////////////
	function purchasePlus() {
		$userid = $this->subtype;
		$duration = $this->quantity;
		$fromid = $this->basket->getUser();
		$trackid = $this->basket->getID();
		return addPlus($userid, $duration, $fromid, $trackid);
	}
	
}

//*/