<?php
    ini_set('display_errors', 'On');
	/* 
		Add Bed Service code
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
		
		private function checkLimit($pgId,$floorId,$roomId,$position,$bedPlaced){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if(!empty($pgId) and !empty($floorId) and !empty($roomId)){
						//$resultLimit = mysqli_query($this->db,"SELECT roomsLimit FROM pgFloorsTable WHERE id = '$floorId'");
						$resultRowCount = mysqli_query($this->db,"SELECT position,bedPlaced FROM pgBedsTable WHERE pgId='$pgId' and floorId = '$floorId' and roomId = '$roomId' and bedPlaced='$bedPlaced'");
						if(mysqli_num_rows($resultRowCount)>0){
							//$limitArray=mysqli_fetch_array($resultLimit);
							$rowCount=mysqli_num_rows($resultRowCount);
							//$limit=intval($limitArray[0]);
							$sameFlag=false;
							while ($row = mysqli_fetch_array($resultRowCount)) {
								if(intval($row[0])==intval($position) and $bedPlaced==$row[1]){
									$sameFlag=true;
									break;
								}
							}
							if($bedPlaced=="Kitchen")
							    $limit=2;
							else if(strpos($bedPlaced,"Bedroom")==0)
							    $limit=4;
							else if($bedPlaced=="Hall" or $bedPlaced=="Room")
							    $limit=6;
							if($rowCount>=$limit){
								$error = array('status' => "limit_exceded", "msg" => "bed limit");
								$this->response($this->json($error), 400);
							}elseif($sameFlag){
								$error = array('status' => "same_position", "msg" => "same bed position");
								$this->response($this->json($error), 400);
							}
							else{
								return true;
							}
						}else{
						    return true;
						}
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "Internal Error/Bad Request");
			$this->response($this->json($error), 400);
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
		
        /****** add pg details
        *******/
        private function addBed(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$jwtToken=$this->checkSession();
			if(!$jwtToken->admin){
				$failed = array('status' => "fail", "msg" => "Only admin can add Bed's");
				$this->response($this->json($failed), 401);
			}
			$pg_id = $this->_request['pgId'];
			$floorId = $this->_request['floorId'];
			$roomId = $this->_request['roomId'];
			$description = $this->_request['description'];
			$position = $this->_request['position'];
			$bedPlaced = $this->_request['bedPlaced'];
			$bedDeposit = $this->_request['bedDeposit'];
			$bedPrice = $this->_request['bedPrice'];
			$foodPrice = $this->_request['foodPrice'];
			$withoutFood = $this->_request['withoutFood'];
			$userId = $jwtToken->uid;
			
			
			
			// Input validations
			if(!empty($pg_id) and !empty($roomId) and !empty($userId) and !empty($floorId)) {
					$this->checkLimit($pg_id,$floorId,$roomId,$position,$bedPlaced);
					// Set autocommit to off
					mysqli_autocommit($this->db,FALSE);
					$addBedStmt = $this->db->prepare("INSERT into pgBedsTable (pgId,userId,floorId,roomId,description,position,bedPlaced,bedDeposit,bedPrice,foodPrice,withoutFood) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
					$addBedStmt->bind_param("iiiisisiiis", $pg_id,$userId,$floorId,$roomId,$description,$position,$bedPlaced,$bedDeposit,$bedPrice,$foodPrice,$withoutFood);
					if($addBedStmt->execute()){
							//$pgId=mysqli_insert_id($this->db);
							$success = array('status' => "Success", "msg" => "Bed Added Successfully");
							mysqli_commit($this->db);
							$this->response($this->json($success), 200);
						
					}else{
					     mysqli_rollback($this->db);
					    $error = array('status' => "Failed", "msg" => "Bed Add Failed");
					    $this->response($this->json($error), 400);
					}
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "Internal Error/Bad Request2");
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
