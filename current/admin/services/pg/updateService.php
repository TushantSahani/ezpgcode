<?php
    
	/* 
		Registeration Service code
 	*/
	
	require_once("../lib/Rest.inc.php");
	
	class API extends REST {
	
		public $data = "";
		
		const DB_SERVER = "127.0.0.1";
		const DB_USER = "subli8eu_expg";
		const DB_PASSWORD = "suemans@123";
		const DB = "subli8eu_ezpgdb";
		
		private $db = NULL;
	
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		/*
		 *  Database connection 
		*/
		private function dbConnect(){
			$this->db = mysqli_connect(self::DB_SERVER,self::DB_USER,self::DB_PASSWORD,self::DB);
				//mysqli_select_db(self::DB,$this->db);
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
		
        //update pg
          private function updatePg(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			
			//$pg_name = $this->_request['pg_name'];
			//$description = $this->_request['description'];
			(isset($this->_request['pg_name'])) ? $pg_name = $this->_request['pg_name'] : $pg_name = "check";
			(isset($this->_request['description'])) ? $description = $this->_request['description'] : $description = "check";
			(isset($this->_request['pg_rules'])) ? $pg_rules = $this->_request['pg_rules'] : $pg_rules ="check";
			(isset($this->_request['gender_type'])) ? $gender_type = $this->_request['gender_type'] : $gender_type ="check";
			(isset($this->_request['pg_street'])) ? $pg_street = $this->_request['pg_street'] : $pg_street ="check";
			(isset($this->_request['pg_landmark'])) ? $pg_landmark = $this->_request['pg_landmark'] : $pg_landmark ="check";
			(isset($this->_request['pg_city'])) ? $pg_city = $this->_request['pg_city'] : $pg_city ="check";
			(isset($this->_request['pg_area'])) ? $pg_area = $this->_request['pg_area'] : $pg_area ="check";
			(isset($this->_request['pg_state'])) ? $pg_state = $this->_request['pg_state'] : $pg_state ="check";
			(isset($this->_request['pg_pin'])) ? $pg_pin = $this->_request['pg_pin'] : $pg_pin ="check";
			(isset($this->_request['pg_contact'])) ? $pg_contact = $this->_request['pg_contact'] :$pg_contact = "check";
			(isset($this->_request['pg_alternate_contact'])) ? $pg_alternate_contact = $this->_request['pg_alternate_contact'] : $pg_alternate_contact ="check";
			(isset($this->_request['pg_email'])) ? $pg_email = $this->_request['pg_email'] : $pg_email ="check";
			(isset($this->_request['pg_alternate_email'])) ? $pg_alternate_email = $this->_request['pg_alternate_email'] : $pg_alternate_email ="check";
			(isset($this->_request['selected_amenities'])) ? $selected_amenities = $this->_request['selected_amenities'] :  $selected_amenities ="check";
			(isset($this->_request['pg_long'])) ? $pg_long = $this->_request['pg_long'] : $pg_long ="check";
			(isset($this->_request['pg_lat'])) ? $pg_lat = $this->_request['pg_lat'] : $pg_lat ="check";
			(isset($this->_request['food_type'])) ? $food_type = $this->_request['food_type'] : $food_type = "check";
			//(isset($this->_request['userId'])) ? $userId = $this->_request['userId'] : $userId = "check";
              $userId = $this->_request['userId'];
              //match with db
              //$deleteStmt = "SELECT * FROM 'pgtable' where 'id'=6";
              //$row = mysqli_query($this->db, $deleteStmt);
               //echo $row['pg_name'];
              
                $data = "SELECT * FROM pgtable WHERE userId='$userId'";
                $result = mysqli_query($this->db, $data);
                $row = mysqli_fetch_assoc($result);
              if ($row > 0){
                    $pg_name = "check" ? $pg_name = $row["pg_name"] : 0;
                    $description = "check" ? $description = $row["description"] : 0;
                    $pg_rules = "check" ? $pg_rules = $row["pg_rules"] : 0;
                    $gender_type = "check" ? $gender_type = $row["gender_type"] : 0;
                    $pg_street = "check" ? $pg_street = $row["pg_street"] : 0;
                    $pg_landmark = "check" ? $pg_landmark = $row["pg_landmark"] : 0;
                    $pg_city = "check" ? $pg_city = $row["pg_city"] : 0;
                    $pg_area = "check" ? $pg_area = $row["pg_area"] : 0;
                    $pg_state = "check" ? $pg_state = $row["pg_state"] : 0;
                    $pg_pin = "check" ? $pg_pin = $row["pg_pin"] : 0;
                    $pg_contact = "check" ? $pg_contact = $row["pg_contact"] : 0;
                    $pg_alternate_contact = "check" ? $pg_alternate_contact = $row["pg_alternate_contact"] : 0;
                    $pg_email = "check" ? $pg_email = $this->_request['pg_email'] :$pg_email = $row["pg_email"] ;
                    $pg_alternate_email = "check" ? $pg_alternate_email = $row["pg_alternate_email"] : 0;
                    $selected_amenities = "check" ? $selected_amenities = $row["selected_amenities"] : 0;
                    $pg_long = "check" ? $pg_long = $row["pg_long"] : 0;
                    $pg_lat = "check" ? $pg_lat = $row["pg_lat"] : 0;
                    $food_type = "check" ? $food_type = $row["food_type"] : 0;
                    $userId = "check" ? $userId = $row["userId"] : 0;
                    
                }
                else{
                    $error = array('status' => "error", "msg" => "retrival failed");
                    $this->response($this->json($error),400);
                }
              $deleteStmt ="UPDATE pgtable SET pg_name=$pg_name, description=$description, pg_rules= $pg_rules, gender_type=$gender_type , pg_street=$pg_street ,pg_landmark = $pg_landmark,pg_city =$pg_city , pg_area=$pg_area ,pg_state=$pg_state ,pg_pin=$pg_pin,pg_contact=$pg_contact ,pg_alternate_contact=$pg_alternate_contact ,pg_email=$pg_email ,pg_alternate_email=$pg_alternate_email ,pg_alternate_email=$pg_alternate_email ,selected_amenities=$selected_amenities ,pg_long=$pg_long ,pg_lat=$pg_lat ,food_type=$food_type ,userId=$userId  WHERE 1";
                if (mysqli_query($this->db, $deleteStmt)){
                    $success = array('status' => "Success", "msg" => "Record Deleted Successfully.");
                    $this->response($this->json($success),200);
                }
                else{
                    $error = array('status' => "error", "msg" => "deletion failed");
                    $this->response($this->json($error),400);
                }
               
              /*****
			// Input validations
			if(!empty($pg_email)  and !empty($pg_name) ) {
				if(filter_var($pg_email, FILTER_VALIDATE_EMAIL)){
					// Set autocommit to off
					mysqli_autocommit($this->db,FALSE);
					$registerStmt = $this->db->prepare("INSERT into pgtable (pg_name,description,pg_rules,gender_type,pg_street,pg_landmark,pg_city,pg_area,pg_state,pg_pin,pg_contact,pg_alternate_contact,pg_email,pg_alternate_email,selected_amenities,pg_long,pg_lat,food_type,userId) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
					
                    $registerStmt->bind_param("ssssssssssssssssssi", $pg_name,$description,$pg_rules,$gender_type,$pg_street,$pg_landmark,$pg_city,$pg_area,$pg_state,$pg_pin,$pg_contact,$pg_alternate_contact ,$pg_email,$pg_alternate_email,$selected_amenities,$pg_long,$pg_lat,$food_type,$userId);
                    
					if($registerStmt->execute()){
							mysqli_commit($this->db);
							//$this->mailToPg($pg_name,$pg_email,true);
							$success = array('status' => "Success", "msg" => "Registeration Completed");
							$this->response($this->json($success), 200);
						
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
			*************
              
            }
            else
            {
                $this->response('',204); // If no records "No Content" status
            }*/
            
            
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "Internal Error/Bad Request");
			$this->response($this->json($error), 400);
		}
        
         /****** add pg details
        *******/
        private function addPg(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			
			$pg_name = $this->_request['pg_name'];
			$description = $this->_request['description'];
			$pg_rules = $this->_request['pg_rules'];
			$gender_type = $this->_request['gender_type'];
			$pg_street = $this->_request['pg_street'];
			$pg_landmark = $this->_request['pg_landmark'];
			$pg_city = $this->_request['pg_city'];
			$pg_area = $this->_request['pg_area'];
			$pg_state = $this->_request['pg_state'];
			$pg_pin = $this->_request['pg_pin'];
			$pg_contact = $this->_request['pg_contact'];
			$pg_alternate_contact = $this->_request['pg_alternate_contact'];
			$pg_email = $this->_request['pg_email'];
			$pg_alternate_email = $this->_request['pg_alternate_email'];
			$selected_amenities = $this->_request['selected_amenities'];
			$pg_long = $this->_request['pg_long'];
			$pg_lat = $this->_request['pg_lat'];
			$food_type = $this->_request['food_type'];
			$userId = $this->_request['userId'];
			
			// Input validations
			if(!empty($pg_email)  and !empty($pg_name) and !empty($pg_contact) ) {
				if(filter_var($pg_email, FILTER_VALIDATE_EMAIL)){
					// Set autocommit to off
					mysqli_autocommit($this->db,FALSE);
					$registerStmt = $this->db->prepare("INSERT into pgtable (pg_name,description,pg_rules,gender_type,pg_street,pg_landmark,pg_city,pg_area,pg_state,pg_pin,pg_contact,pg_alternate_contact,pg_email,pg_alternate_email,selected_amenities,pg_long,pg_lat,food_type,userId) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
					
                    $registerStmt->bind_param("ssssssssssssssssssi", $pg_name,$description,$pg_rules,$gender_type,$pg_street,$pg_landmark,$pg_city,$pg_area,$pg_state,$pg_pin,$pg_contact,$pg_alternate_contact ,$pg_email,$pg_alternate_email,$selected_amenities,$pg_long,$pg_lat,$food_type,$userId);
					if($registerStmt->execute()){
							mysqli_commit($this->db);
							//$this->mailToPg($pg_name,$pg_email,true);
							$success = array('status' => "Success", "msg" => "Registeration Completed");
							$this->response($this->json($success), 200);
						
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