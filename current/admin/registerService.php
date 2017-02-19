<?php
    
	/* 
		Registeration Service code
 	*/
	ini_set('display_errors', 'On');
	
	require_once("Rest.inc.php");
	
	class API extends REST {
	
		public $data = "";
		
		const DB_SERVER = "localhost";
		const DB_USER = "root";
		const DB_PASSWORD = "suemans@123";
		const DB = "ezpgdb";
		private $updateValue="";
		
		private $db = NULL;
	
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		/*
		 *  Database connection 
		*/
		private function dbConnect(){
			$this->db = mysqli_connect(self::DB_SERVER,self::DB_USER,self::DB_PASSWORD,self::DB);
				//mysqli_select_db(self::DB,$this->db);
		}
		
		/*
		 * Public method for access api.
		 * This method dynmically call the method based on the query string
		 *
		 */
		public function processApi(){
			$func = strtolower(trim(str_replace("/","",$_REQUEST['rquest'])));
			if((int)method_exists($this,$func) > 0)
				$this->$func();
			else
				$this->response('',404);				// If the method not exist with in this class, response would be "Page not found".
		}
		
		/*
		 * close sql connection
		 */
		private function closeConnection(){
			mysqli_close($this->db);
		}
		
		/*
		 *Check if email id or contact number exists
		 */
		
		
		private function checkIfExists($pgId){
			if(!empty($pgId)){
						$result = mysqli_query($this->db,"SELECT receiptNo FROM paymentreceipttable WHERE pgId = '$pgId' LIMIT 1");
						if(mysqli_num_rows($result)>0){
							$row=mysqli_fetch_assoc($result);
							$this->updateValue=intval($row["receiptNo"])+1;
							return true;
						}
			}
			return false;
		}
		
		/* 
		 *	Register API
		 *  Register must be POST method
		 */
		
		private function getReceiptNo(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			
			$pgId = $this->_request['pgId'];
			
			// Input validations
			
			if(!empty($pgId)){
				if(!$this->checkIfExists($pgId)){
					$receiptNo=1;
					$insrtStmt = $this->db->prepare("INSERT into paymentreceipttable VALUES (?,?)");
					$insrtStmt->bind_param("ii", $pgId,$receiptNo);
					if($insrtStmt->execute()){
						$success = array('receiptNo' => $pgId."1");
						$this->response($this->json($success), 200);
						
					}else{
					    $error = array('status' => "Failed", "msg" => "Could not fetch Receipt No");
					    $this->response($this->json($error), 400);
					}
				}else{
					$updateStmt = $this->db->prepare("UPDATE paymentreceipttable SET receiptNo = @receiptNo := receiptNo + 1 WHERE pgId=? LIMIT 1");
					$updateStmt->bind_param("i", $pgId);
					if($updateStmt->execute()){
						$success = array('receiptNo' => $pgId.$this->updateValue);
						$this->response($this->json($success), 200);
					}else{
						$error = array('status' => "Failed", "msg" => "Could not fetch Receipt No");
						$this->response($this->json($error), 400);
					}
				}
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "Internal Error/Bad Request");
			$this->response($this->json($error), 400);
		}
		
		/*
		 *	Encode array into JSON
		*/
		private function json($data){
			if(is_array($data)){
				return json_encode($data);
			}
		}
	}
	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>