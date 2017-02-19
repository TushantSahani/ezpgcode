<?php
ini_set('display_errors', 'On');
    
	/* 
		Delete pg code
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
		
		/*
		 *  Database connection 
		*/
		private function dbConnect(){
			$this->db = mysqli_connect(DB_SERVER,DB_USER,DB_PASSWORD,DB);
				//mysqli_select_db(DB,$this->db);
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
		
		private function decodeJWT($jwt){
			$decoded = JWT::decode($jwt, JWT::$key, array('HS256'));
			return $decoded;
		}
		
		private function encodeJWT($token){
			$jwt = JWT::encode($token, JWT::$key);
			return $jwt;
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
		
     
         //delete pg details
        // Send ID thorugh POST
        
        private function deleteIt(){

            if($this->get_request_method() != "POST"){
                $this->response('',406);
            }
			$jwtToken=$this->checkSession();
			if(!$jwtToken->admin){
				$failed = array('status' => "fail", "msg" => "Only admin can delete PG's");
				$this->response($this->json($failed), 401);
			}
			$id = (int)$this->_request['id'];
			$table = $this->_request['for'];
			$userId = $jwtToken->uid;
			$tableName="";
			if($table=='pg'){
				$tableName='pgTable';
			}
			if($id > 0 && $tableName!="")
            {    
                $deleteStmt ="UPDATE $tableName SET isDeleted='y' WHERE id = $id";
                if (mysqli_query($this->db, $deleteStmt)){
			$tokenGenereated=$this->encodeJWT($this->getToken(true,$userId,$jwtToken));
			$tokenGenereated=base64_encode($tokenGenereated);
			$success = array('status' => "Success", "msg" => "Record Deleted Successfully.");
			$this->add_header('Authorization', $tokenGenereated);
			$this->response($this->json($success),200);
                }
                else{
                    $error = array('status' => "error", "msg" => "deletion failed");
                    $this->response($this->json($error),400);
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
