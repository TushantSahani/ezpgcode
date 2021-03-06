<?php
	ini_set('display_errors', 'On');
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
			$this->db = mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
			if($this->db)
				mysql_select_db(DB,$this->db);
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
		
		
		private function getPgImage(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			//$jwtToken=$this->checkSession();
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			
			//$userId = $jwtToken->uid;
			$pgId= $this->_request['pgId'];
			$imgName= $this->_request['imgName'];
			$encode=$this->_request['encode'];
			
			// Input validations
			if(!empty($pgId) and !empty($imgName) and !empty($encode)){
					$row=mysql_fetch_array(mysql_query("SELECT image,imageDes FROM pgImageTable WHERE pgId = '$pgId' and imageDes LIKE '$imgName%'", $this->db));
					$extension=split('\.',$row['imageDes']);
					$ext=strtolower($extension[1]);
					if($encode=='n'){
					    header("Content-type: image/".$ext);
					    print $row['image'];
					}else{
					    $baseEncodedString=base64_encode($row['image']);
					    $success = array('ext' => $ext, "image" => $baseEncodedString);
					    $this->response($this->json($success), 200);
					}
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "No image found");
			$this->response($this->json($error), 400);
		}
		
		
		private function getPGImageList(){
			$jwtToken=$this->checkSession();
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			
			$userId = $jwtToken->uid;
			$pgId = $this->_request['pgId'];
			
			// Input validations
			if(!empty($userId)){
					$sql = mysql_query("SELECT imageDes FROM pgImageTable WHERE userId = '$userId'and pgId ='$pgId'", $this->db);
					$resultList=array();
					if(mysql_num_rows($sql) > 0){
						while($result = mysql_fetch_array($sql,MYSQL_ASSOC))
						{
							array_push($resultList,$result['imageDes']);
						}
						
						// If success everythig is good send header as "OK" and user details
						$this->response($this->json($resultList), 200);
					}
					$this->response('', 204);	// If no records "No Content" status
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "No Images found");
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
