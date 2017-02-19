<?php
    ini_set('display_errors', 'On');
	/* 
		Delete Room Service code
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
		
		private function checkBed($pgId,$roomId){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if(!empty($pgId) and !empty($roomId)){
						//$resultLimit = mysqli_query($this->db,"SELECT roomsLimit FROM pgFloorsTable WHERE id = '$floorId'");
						$resultRowCount = mysqli_query($this->db,"SELECT ID FROM pgBedsTable WHERE pgId='$pgId' and roomId = '$roomId'");
						if(mysqli_num_rows($resultRowCount)>0){
							$error = array('status' => "Failed", "msg" => "delete room");
							$this->response($this->json($error), 400);
						}
			}
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
		
		private function checkPgId($pgIds,$pg_id){
			if($pgIds==""){
				$failed = array('status' => "fail", "msg" => "Not Allowed");
				$this->response($this->json($failed), 401);
			}else{
				$found=false;
				$pgArr=explode(",",$pgIds);
				foreach ($pgArr as $value){
					if($value==$pg_id){
						$found=true;
						break;
					}
				}
				if(!$found){
					$failed = array('status' => "fail", "msg" => "Not Allowed");
					$this->response($this->json($failed), 401);
				}
			}
		}
		
        /****** delete room 
        *******/
        private function deleteRoom(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$jwtToken=$this->checkSession();
			if(!$jwtToken->admin){
				$failed = array('status' => "fail", "msg" => "Only admin can delete Rooms's");
				$this->response($this->json($failed), 401);
			}
			$pg_id = $this->_request['pgId'];
			$floorId = $this->_request['floorId'];
			$roomId = $this->_request['roomId'];
			$userId = $jwtToken->uid;
			$pgIds = $jwtToken->pgIds;
			$this->checkPgId($pgIds,$pg_id);
			// Input validations
			if(!empty($roomId) and !empty($userId)) {
					$this->checkBed($pg_id,$roomId);
					$deleteFloorStmt = $this->db->prepare("Delete from pgRoomsTable where userId=? and ID=? and pgId=?");
					$deleteFloorStmt->bind_param("iii", $userId,$roomId,$pg_id);
					if($deleteFloorStmt->execute()){
							//$pgId=mysqli_insert_id($this->db);
							$success = array('status' => "Success", "msg" => "Room Deleted Successfully");
							//mysqli_commit($this->db);
							$this->response($this->json($success), 200);
						
					}else{
					    $error = array('status' => "Failed", "msg" => "Room Delete Failed");
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
