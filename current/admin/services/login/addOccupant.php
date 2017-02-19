<?php
error_reporting(E_ALL);
    ini_set('display_errors', 'On');
	/* 
		Add Occupant Service code
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
		
		private function getFirstDayOfNextMonth($date){
			$effectiveDate = date('Y-m-d', strtotime("first day of next month", strtotime($date)));
			return $effectiveDate;
		}
		
		private function getLastDayOfMonth($date){
			$lastDay = date('t', strtotime("last day of month", strtotime($date)));
			return $lastDay;
		}
		
		private function getRemainingDays($date,$firstofNextMonth){
			$dateTime1=strtotime($date);
			$dateTime2=strtotime($firstofNextMonth);
			$secs = $dateTime2 - $dateTime1;// == <seconds between the two times>
			$days = ($secs / 86400)-1;
			return $days;
		}
		
		private function getDueAmount($bedId,$date,$firstofNextMonth,$withFood){
			$result = mysqli_query($this->db,"SELECT bedPrice,foodPrice FROM pgBedsTable WHERE id = '$bedId'");
			$row = mysqli_fetch_array($result);
			$bedPrice=$row[0];
			$foodPrice=$row[1];
			$price=$bedPrice;
			if($withFood=='y'){
				$price=$price+$foodPrice;
			}
			$days=$this->getRemainingDays($date,$firstofNextMonth);
			$lastDay=$this->getLastDayOfMonth($date);
			$daysDiff=$lastDay-$days;
			if($daysDiff>2){
				$price=$price/$days;
			}
			return $price;
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
				//mysqli_select_db(DB,$this->db);
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
		
        /****** add pg details
        *******/
        private function addOccupant(){
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
			$gender = $this->_request['gender'];
			$email = $this->_request['email'];
			$contact = $this->_request['contact'];
			$city = $this->_request['city'];
			
			$pgId = $this->_request['pgId'];
			$floorId = $this->_request['floorId'];
			$roomId = $this->_request['roomId'];
			$bedId = $this->_request['bedId'];
			$deposit = $this->_request['deposit'];
			$checkInDt = $this->_request['checkInDt'];
			$withFood = $this->_request['withFood'];

			$userId = $jwtToken->uid;
			$dueDate=$this->getFirstDayOfNextMonth($checkInDt);
			$dueAmount=$this->getDueAmount($bedId,$checkInDt,$dueDate,$withFood);
			
			
			
			// Input validations
			if(!empty($firstName) and !empty($lastName)) {
					// Set autocommit to off
					mysqli_autocommit($this->db,FALSE);
					$addOccupantStmt = $this->db->prepare("INSERT into occupantTable (userId,firstName,lastName,dob,panNo,address,currentStatus,workingCompany,field,collegeName,degree,gender,city,contact,email,pgId,roomId,floorId,bedId,deposit,withFood,checkInDate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
					
					$addOccupantStmt->bind_param("issssssssssssssiiiiiss", $userId,$firstName,$lastName,$dob,$panNo,$address,$currentStatus,$workingCompany,$field,$collegeName,$degree,$gender,$city,$contact,$email,$pgId,$roomId,$floorId,$bedId,$deposit,$withFood,$checkInDt);
					if($addOccupantStmt->execute()){
							$occupantId=mysqli_insert_id($this->db);
							$addDueStmt = $this->db->prepare("INSERT into paymentDuesTable (occupantId,bedId,pgId,dueDate,dueAmount) VALUES (?,?,?,?,?)");
							
							$addDueStmt->bind_param("iiisi",$occupantId,$bedId,$pgId,$dueDate,$dueAmount);
							if($addDueStmt->execute()){
								$success = array('status' => "Success", "msg" => "Occupant Added Successfully");
								mysqli_commit($this->db);
								$this->response($this->json($success), 200);
							}else{
								mysqli_rollback($this->db);
								$error = array('status' => "Failed", "msg" => "Occupant Add Failed");
								$this->response($this->json($error), 400);
							}
					}else{
					     mysqli_rollback($this->db);
					    $error = array('status' => "Failed", "msg" => "Occupant Add Failed");
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
