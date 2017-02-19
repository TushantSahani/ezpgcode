<?php
    //ini_set('display_errors', 'On');
	/* 
		Update Occupant Service code
 	*/
	require_once '../session/vendor/autoload.php';
	
	use \Firebase\JWT\JWT;
	
	require_once("../lib/Rest.inc.php");
        include("/opt/config/config.php");
	
	class API extends REST {
	
		public $data = "";
		
		
		private $db = NULL;
	
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		private function decodeJWT($jwt){
			$decoded = JWT::decode($jwt, JWT::$key, array('HS256'));
			return $decoded;
		}

		private function checkSession(){
			$token = null;
			$headers = apache_request_headers();
			if(isset($headers['Authorization'])){
				$token = base64_decode($headers['Authorization']);
			}
			//$failed = array('status' => "fail", "msg" => "$token");
			//$this->changeContentType("text/plain");
			
			if(!empty($token)){
			    try{
				$jwtObj=$this->decodeJWT($token);
				return $jwtObj;
			    }
			    catch(ExpiredException $expEx){
				$failed = array('status' => "fail", "msg" => "Expired Token");
				$this->response($this->json($failed), 401);
			    }
			    catch(BeforeValidException $expEx){
				$failed = array('status' => "fail", "msg" => "Expired Token");
				$this->response($this->json($failed), 401);
			    }
			    catch(SignatureInvalidException $expEx){
				$failed = array('status' => "fail", "msg" => "Invalid Token");
				$this->response($this->json($failed), 401);
			    }
			    catch(DomainException $expEx){
				$failed = array('status' => "fail", "msg" => "Invalid Domain");
				$this->response($this->json($failed), 401);
			    }
			    catch(UnexpectedValueException $expEx){
				$failed = array('status' => "fail", "msg" => "Unexpected Value Exception");
				$this->response($this->json($failed), 401);
			    }
			    catch(InvalidArgumentException $expEx){
				$failed = array('status' => "fail", "msg" => "Invalid Argument Exception");
				$this->response($this->json($failed), 401);
			    }
			}
			
		}
		
		/*
		 *  Database connection 
		*/
		private function dbConnect(){
			$this->db = mysqli_connect(DB_SERVER,DB_USER,DB_PASSWORD,DB);
				//mysqli_select_db(self::DB,$this->db);
		}
		
		/*
		 * Upload Image function
		 */
		//private function uploadImage(){
		//	
		//}
		
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
		
        /****** updateOccupant details
        *******/
	
        private function updateOccupant(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$jwtToken=$this->checkSession();
			//if(!$jwtToken->admin){
			//	$failed = array('status' => "fail", "msg" => "Only admin can add Floor's");
			//	$this->response($this->json($failed), 401);
			//}
			$firstName = $this->_request['firstName'];
			$lastName = $this->_request['lastName'];
			$dob = $this->_request['dob'];
			$panNo = $this->_request['panNo'];
			$address = $this->_request['address'];
			$currentStatus = $this->_request['currentStatus'];
			$workingCompany = $this->_request['workingCompany'];
			$field = $this->_request['field'];
			$collegeName = $this->_request['collegeName'];
			$degree = $this->_request['degree'];
			$userId = $jwtToken->uid;
			$occupantId = $this->_request['occupantId'];
			
			
			
			// Input validations
			if(!empty($firstName) and !empty($lastName)) {
					// Set autocommit to off
					//mysqli_autocommit($this->db,FALSE);
					$updateOccupantStmt = $this->db->prepare("UPDATE occupantTable SET firstName=?,lastName=?,dob=?,panNo=?,address=?,currentStatus=?,workingCompany=?,field=?,collegeName=?,degree=? WHERE ID=? and userId=?");
					
					$updateOccupantStmt->bind_param("ssssssssssii", $firstName,$lastName,$dob,$panNo,$address,$currentStatus,$workingCompany,$field,$collegeName,$degree,$occupantId,$userId);
					if($updateOccupantStmt->execute()){
							//$pgId=mysqli_insert_id($this->db);
							$success = array('status' => "Success", "msg" => "Occupant Updated Successfully");
							//mysqli_commit($this->db);
							$this->response($this->json($success), 200);
						
					}else{
					     //mysqli_rollback($this->db);
					    $error = array('status' => "Failed", "msg" => "Occupant Update Failed");
					    $this->response($this->json($error), 400);
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
