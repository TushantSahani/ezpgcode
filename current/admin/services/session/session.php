<?php
ini_set('display_errors', 'On');
require_once __DIR__ . '/vendor/autoload.php';
use \Firebase\JWT\JWT;

//$data = openssl_random_pseudo_bytes(16);
//$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
//$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
//$key = vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
//$token = array(
//    "iss" => "http://example.org",
//    "aud" => "http://example.com",
//    "iat" => 1356999524,
//    "nbf" => 1357000000
//);
//
///**
// * IMPORTANT:
// * You must specify supported algorithms for your application. See
// * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
// * for a list of spec-compliant algorithms.
// */
//$jwt = JWT::encode($token, $key);
//echo $key;
////print_r($jwt);
//$decoded = JWT::decode($jwt, $key, array('HS256'));
//
////print_r($decoded);
//
///*
// NOTE: This will now be an object instead of an associative array. To get
// an associative array, you will need to cast it as such:
//*/
//
//$decoded_array = (array) $decoded;
//
///**
// * You can add a leeway to account for when there is a clock skew times between
// * the signing and verifying servers. It is recommended that this leeway should
// * not be bigger than a few minutes.
// *
// * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
// */
//JWT::$leeway = 60; // $leeway in seconds
//$decoded = JWT::decode($jwt, $key, array('HS256'));
require_once("../lib/Rest.inc.php");
include("/opt/config/config.php");

class SessionAPI extends REST{
   
    
    
    private $db = NULL;
	
    public function __construct(){
	parent::__construct();				// Init parent contructor
	$this->dbConnect();					// Initiate Database connection
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
    
    /*
    *	Encode array into JSON
    */
    private function json($data){
	if(is_array($data)){
	    return json_encode($data);
	}
    }
    
    private function decodeJWT($jwt){
        $decoded = JWT::decode($jwt, JWT::$key, array('HS256'));
        return $decoded;
    }
    private function encodeJWT($token){
        $jwt = JWT::encode($token, JWT::$key);
        return $jwt;
    }
    private function getTimeInMiliseconds(){
	date_default_timezone_set("Asia/Kolkata");
	$date = date_create();
	return date_timestamp_get($date);
    }
    
    private function getPgList($userId){
	$resultList=array();
	if(!empty($userId)){
		$sql = mysql_query("SELECT ID FROM pgTable WHERE userId = '$userId'and isDeleted ='n'", $this->db);
		if(mysql_num_rows($sql) > 0){
		    while($result = mysql_fetch_array($sql,MYSQL_ASSOC))
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
    
    private function getToken($admin,$userId,$currentTime){
	$pgs=$this->getPgList($userId);
        return $token = array(
            "iss" => "https://ezpgms.com",
            "aud" => "https://ezpgms.com",
            "iat" => $currentTime,
            "nbf" => $currentTime-60,
	    "exp" => $currentTime+1800,
            "admin" => $admin,
            "uid" => $userId,
	    "pgIds" => $pgs
        );
    }
    
    private function generateKey(){
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
    }
    
    private function checkSession(){
        if($this->get_request_method() != "POST"){
				$this->response('',406);
	}
        //$jwtToken = $this->_request['token'];
	$jwtToken = null;
	$headers = apache_request_headers();
	if(isset($headers['Authorization'])){
	    $jwtToken = base64_decode($headers['Authorization']);
	}
        if(!empty($jwtToken)){
            try{
                $jwtObj=$this->decodeJWT($jwtToken);
		$success = array('admin' => $jwtObj->admin, "msg" => "valid");
		$this->response($this->json($success), 200);
                //return $jwtObj;
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
    
    private function login(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			
			$email = $this->_request['email'];		
			$password = $this->_request['pwd'];
			
			// Input validations
			if(!empty($email) and !empty($password)){
				if(filter_var($email, FILTER_VALIDATE_EMAIL)){
					$sql = mysql_query("SELECT user_id,verified,isActivated,dbName,wizard FROM login_tb WHERE email_id = '$email' AND password = '$password' LIMIT 1", $this->db);
					if(mysql_num_rows($sql) > 0){
						$result = mysql_fetch_array($sql,MYSQL_ASSOC);
						
						// If success everythig is good send header as "OK" and user details
						//$this->startSession($result[0],$result[1],$result[2],$result[3],$result[4],$result[5],'admin');
                                                $currentTime=$this->getTimeInMiliseconds();
                                                $tokenGenereated=$this->encodeJWT($this->getToken(true,$result['user_id'],$currentTime));
                                                $tokenGenereated=base64_encode($tokenGenereated);
                                                $success = array('status' => "Success", "msg" => "loggedIn");
                                                $this->add_header('Authorization', $tokenGenereated);
                                                $this->response($this->json($success), 200);

					}
					$this->response('', 204);	// If no records "No Content" status
				}
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "Invalid Email address or Password");
			$this->response($this->json($error), 400);
    }
 
}
$api = new SessionAPI;
$api->processApi();
?>
