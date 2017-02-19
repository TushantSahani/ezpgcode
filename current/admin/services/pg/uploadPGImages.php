<?php
	/* 
		Upload Images Service code
 	*/
	error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', '1');
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
				JWT::$leeway = 400;
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
		
		private function clean($string) {
		    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
		 
		    return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
		}
		
		private function copyImageToLocation(){
		    $imgArr=array();
		    foreach( $_FILES as $imgfile ) {
			if(!empty($imgfile['name'])){
			    //$pgImage1 =file_get_contents($imgfile['tmp_name']);
			    $imageFileType = strtolower(pathinfo($imgfile['name'],PATHINFO_EXTENSION));
			    if ($imgfile['tmp_name']["size"] > 5242880) {
				continue;
			    }
			    else if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
				continue;
			    }
			    else{
				try{
				    $imgData=addslashes(file_get_contents($imgfile['tmp_name']));
				    array_push($imgArr,array('key'=>$imgfile['name'],'imgData'=>$imgData));
				}catch(Exception $ex){
				    $error = array('status' => "Failed", "msg" => $ex->getMessage());
				    $this->response($this->json($error), 400);
				}
			    }
			}
			
		    }
		    return $imgArr;
		    
		}
		
        /******
	 * upload pg image details
        *******/
        private function uploadImages(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			//$jwtToken=$this->checkSession();
			//if(!$jwtToken->admin){
			//	$failed = array('status' => "fail", "msg" => "Only admin can add PG's");
			//	$this->response($this->json($failed), 401);
			//}
			$jwtToken=$this->checkSession();
			$pgId=$this->_request['pgId'];
			$userId=$jwtToken->uid;
			$sucessArr=array();
			$imgArr=$this->copyImageToLocation();
			$counter=0;
			foreach( $imgArr as $imgfile ) {
			    $imageDes=$imgfile['key'];
			    $imgData=$imgfile['imgData'];
			    try{
				$sql = "INSERT INTO pgImageTable(userId, pgId, imageDes, image) VALUES ($userId,$pgId,'$imageDes','{$imgData}');";
				if(!mysqli_query($this->db,$sql)){
					array_push($sucessArr,array('fileName' => $imageDes, "msg" => mysqli_error($this->db)));
				}else{
					array_push($sucessArr,array('fileName' => $imageDes, "msg" => "success"));
				}
			    }
			    catch(Exception $ex){
				array_push($sucessArr,array('fileName' => $imageDes, "msg" => "fail"));
			    }
			}
			$success = array('status' => "Success", "msg" => $sucessArr);
			$this->response($this->json($success), 200);
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
