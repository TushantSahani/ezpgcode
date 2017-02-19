<?php
    //ini_set('display_errors', 'On');
	/* 
		Add PG Service code
 	*/
	require_once '../session/vendor/autoload.php';
	
	use \Firebase\JWT\JWT;
	
	require_once("../lib/Rest.inc.php"); 
        include("/opt/config/config.php");
	
	class API extends REST {
	
		public $data = "";
		
		const DB_SERVER = "localhost";
		
		
		private $db = NULL;
	
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		private function decodeJWT($jwt){
			$decoded = JWT::decode($jwt, JWT::$key, array('HS256'));
			return $decoded;
		}
		
		private function getPgList($userId){
			$resultList=array();
			if(!empty($userId)){
				$sql = mysqli_query($this->db,"SELECT ID FROM pgTable WHERE userId = '$userId'and isDeleted ='n'");
				if(mysqli_num_rows($sql) > 0){
				    while($result = mysqli_fetch_assoc($sql))
				    {
					array_push($resultList,$result['ID']);
				    }
				}
			}
			if(sizeof($resultList)==0){
			    return "";
			}else{
			    return implode(",",$resultList);
			}
		}
		    
		private function getToken($admin,$userId,$jwtToken){
			$pgs=$this->getPgList($userId);
			return $token = array(
			    "iss" => "https://ezpgms.com",
			    "aud" => "https://ezpgms.com",
			    "iat" => $jwtToken->iat,
			    "nbf" => $jwtToken->nbf,
			    "exp" => $jwtToken->exp,
			    "admin" => $admin,
			    "uid" => $userId,
			    "pgIds" => $pgs
			);
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
		
		private function encodeJWT($token){
			$jwt = JWT::encode($token, JWT::$key);
			return $jwt;
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
        private function addPg(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$jwtToken=$this->checkSession();
			if(!$jwtToken->admin){
				$failed = array('status' => "fail", "msg" => "Only admin can add PG's");
				$this->response($this->json($failed), 401);
			}
			$pg_name = $this->_request['pgName'];
			$description = $this->_request['description'];
			$gender_type = $this->_request['gender'];
			$pg_street = $this->_request['street'];
			$pg_city = $this->_request['city'];
			$pg_area = $this->_request['area'];
			$pg_state = $this->_request['state'];
			$pg_pin = $this->_request['pincode'];
			$pg_contact = $this->_request['ContactNo'];
			$selected_amenities = $this->_request['selected_amenities'];
			$pg_long = $this->_request['long'];
			$pg_lat = $this->_request['lat'];
			$food_type = $this->_request['foodType'];
			$userId = $jwtToken->uid;
			
			//non mandatory fields
			$pg_alternate_contact="";
			$pg_email="";
			$pg_alternate_email="";
			$pg_landmark="";
			if (array_key_exists("altContactNo",$this->_request))
				$pg_alternate_contact = $this->_request['altContactNo'];
			if (array_key_exists("email",$this->_request))
				$pg_email = $this->_request['email'];
			if (array_key_exists("altEmail",$this->_request))
				$pg_alternate_email = $this->_request['altEmail'];
			if (array_key_exists("rules",$this->_request))
				$pg_rules = $this->_request['rules'];
			if (array_key_exists("landmark",$this->_request))
				$pg_landmark = $this->_request['landmark'];
			
			
			// Input validations
			if(!empty($pg_name) and !empty($pg_contact) and !empty($description) and !empty($gender_type) and !empty($pg_street) and !empty($pg_city) and !empty($pg_state) and !empty($pg_pin) and !empty($selected_amenities) and !empty($pg_long) and !empty($pg_lat) and !empty($food_type) and !empty($userId)) {
					// Set autocommit to off
					//mysqli_autocommit($this->db,FALSE);
					$addPGStmt = $this->db->prepare("INSERT into pgTable (pg_name,description,pg_rules,gender_type,pg_street,pg_landmark,pg_city,pg_area,pg_state,pg_pin,pg_contact,pg_alternate_contact,pg_email,pg_alternate_email,selected_amenities,pg_long,pg_lat,food_type,userId) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
					
					$addPGStmt->bind_param("ssssssssssssssssssi", $pg_name,$description,$pg_rules,$gender_type,$pg_street,$pg_landmark,$pg_city,$pg_area,$pg_state,$pg_pin,$pg_contact,$pg_alternate_contact ,$pg_email,$pg_alternate_email,$selected_amenities,$pg_long,$pg_lat,$food_type,$userId);
					if($addPGStmt->execute()){
							$pgId=mysqli_insert_id($this->db);
							$tokenGenereated=$this->encodeJWT($this->getToken(true,$userId,$jwtToken));
							$tokenGenereated=base64_encode($tokenGenereated);
							$success = array('status' => "Success", "msg" => "PG Added Successfully","PgId"=>$pgId);
							$this->add_header('Authorization', $tokenGenereated);
							//mysqli_commit($this->db);
							$this->response($this->json($success), 200);
						
					}else{
					     //mysqli_rollback($this->db);
					    $error = array('status' => "Failed", "msg" => "PG Add Failed");
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
