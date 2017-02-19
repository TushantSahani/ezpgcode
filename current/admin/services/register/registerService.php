<?php
    
	/* 
		Registeration Service code
 	*/
	
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
		
		/*
		 *Password generator
		 */
		
		public static function rand_password( $length ) {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			return substr(str_shuffle($chars),0,$length);
		}
		
		/*
		 *Check if email id or contact number exists
		 */
		
		private function checkIfExists(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$contactNo = $this->_request['contactNo'];
			$email = $this->_request['email'];
			if(!empty($email) and !empty($contactNo)){
					if(filter_var($email, FILTER_VALIDATE_EMAIL)){
						$flagEmail=false;
						$flagContact=false;
						$resultEmail = mysqli_query($this->db,"SELECT id FROM register_tb WHERE email_id = '$email'");
						if(mysqli_num_rows($resultEmail)>0)
							$flagEmail=true;
						$resultContact = mysqli_query($this->db,"SELECT id FROM register_tb WHERE contact_no = '$contactNo'");
						if(mysqli_num_rows($resultContact)>0)
							$flagContact=true;
						$success = array('status' => "Success", "email" => $flagEmail,"contact" => $flagContact);
						$this->response($this->json($success), 200);
					}else{
					    $error = array('status' => "Failed", "msg" => "Registration Failed - Register");
					    $this->response($this->json($error), 400);
					}
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "Internal Error/Bad Request");
			$this->response($this->json($error), 400);	
		}
		
		/* 
		 *	Resend Mail API
		 *  Resend Mail must be POST method
		 */
		private function resendMail(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$email = $this->_request['email'];
			$pname = $this->_request['pname'];
			if(!empty($email) and !empty($pname)){
				$resultEmail = mysqli_query($this->db,"SELECT password FROM login_tb WHERE email_id = '$email' LIMIT 1");
				while ($row=mysqli_fetch_row($resultEmail))
					{
						$this->sendMail($pname,$row[0],$email,false);
					}
			}
		}
		
		/* 
		 *	Mail API
		 *  Mail must be POST method
		 */
		
		private function sendMail($username,$password,$to_address,$internal){
			$name       = 'EzPGMS'; 
			$email       = 'support@ezpgms.com'; 
			$subject    = 'Your Credentials';
			//$to_address = "amstel91@gmail.com";
			$space='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			//$password="asdsa";
			//$username="amstel21@gmail.com";
			$message    = 'Hi '.$username.',<br/><br/>'.$space."Your EzPGMS credentials are given below:<br/>".$space."Username: ".$to_address."<br/>".$space."Password: ".$password."<br/><br/>Regards,<br/>EzPGMS Team";
			$message=stripslashes($message);
			$replyTo=$email;
			$headers = "From: ".$name." <".$replyTo.">\r\n"
							."Reply-To: ".$replyTo."\r\n"
							."Return-Path: " .$replyTo. "\r\n"
							."MIME-Version: 1.0\r\n"	
							."Content-Type: text/html; charset=UTF-8\r\n"
							.'X-Mailer: PHP/' . phpversion();
			$result=@mail("$to_address","$subject","$message","$headers");
			if($result){
				if($internal){
					return true;
				}else{
					$success = array('status' => "Success", "msg" => "Mail Sent");
					$this->response($this->json($success), 200);
				}
			}
			else{
				if($internal){
					return false;
				}else{
					$error = array('status' => "Failed", "msg" => "Mail not sent");
					$this->response($this->json($error), 400);
				}
			}
		}
		
		/* 
		 *	Register API
		 *  Register must be POST method
		 */
		
		private function register(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			
			$firstName = $this->_request['firstName'];
			$lastName = $this->_request['lastName'];
			$contactNo = $this->_request['contactNo'];
			$billingAddress = $this->_request['billingAddress'];
			$addressCity = $this->_request['addressCity'];
			$addressPin = $this->_request['addressPin'];
			$addressState = $this->_request['addressState'];
			$email = $this->_request['email'];		
			$password = API::rand_password(10);
                        
			
			// Input validations
			if(!empty($email) and !empty($password) and !empty($firstName) and !empty($lastName) and !empty($contactNo) and !empty($billingAddress) and !empty($addressCity) and !empty($addressPin) and !empty($addressState)){
				if(filter_var($email, FILTER_VALIDATE_EMAIL)){
					// Set autocommit to off
					mysqli_autocommit($this->db,FALSE);
					$registerStmt = $this->db->prepare("INSERT into register_tb (first_name,last_name,contact_no,email_id,billing_address,address_city,address_pin,address_state) VALUES (?,?,?,?,?,?,?,?)");
					$registerStmt->bind_param("ssssssss", $firstName, $lastName,$contactNo, $email, $billingAddress,$addressCity,$addressPin,$addressState);
					if($registerStmt->execute()){
						$userId=mysqli_insert_id($this->db);
						$dbName=$userId.'_db';
						$loginStmt=$this->db->prepare("INSERT into login_tb (user_id,email_id,password,dbName) VALUES (?,?,?,?)");
						$loginStmt->bind_param("ssss",$userId,$email,md5($password),$dbName);
						if($loginStmt->execute()){
							mysqli_commit($this->db);
							$this->sendMail($firstName,$password,$email,true);
							$success = array('status' => "Success", "msg" => "Registeration Completed");
							$this->response($this->json($success), 200);
						}else{
							mysqli_rollback($this->db);
							$error = array('status' => "Failed", "msg" => "Registration Failed - Login");
							$this->response($this->json($error), 400);
						}
						
					}else{
					     mysqli_rollback($this->db);
					    $error = array('status' => "Failed", "msg" => "Registration Failed - Register");
					    $this->response($this->json($error), 400);
					}
				}else{
					$error = array('status' => "Failed", "msg" => "Registeration Failed - Invalid Email ID");
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
