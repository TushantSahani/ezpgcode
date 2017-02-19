<?php
	//ini_set('display_errors', 'On');
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
		
		
		private function getRooms(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			$jwtToken=$this->checkSession();
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			$pgId= $this->_request['pgId'];
			$floorId=$this->_request['floorId'];
			$roomId=$this->_request['roomId'];
			$userId = $jwtToken->uid;		
			
			// Input validations
			if(!empty($userId)){
					if(!empty($roomId)){
						$sql = mysql_query("SELECT ID,kitchen,flatType,bathroom,flatNo,description FROM pgRoomsTable WHERE pgId = '$pgId' and floorId = '$floorId' and ID = '$roomId'", $this->db);
					}else{
						$sql = mysql_query("SELECT ID,kitchen,flatType,bathroom,flatNo,description FROM pgRoomsTable WHERE pgId = '$pgId' and floorId = '$floorId'", $this->db);
					}
					$resultList=array();
					$resultAllJsonFull=array();
					if(mysql_num_rows($sql) > 0){
						while($result = mysql_fetch_array($sql,MYSQL_ASSOC))
						{
							array_push($resultList,$result);
							$resultAllJson=array('id' => $result['ID'], 'kitchen' => $result['kitchen'], 'type' => $result['flatType'], 'flatNo' => $result['flatNo']);
							$roomId=$result['ID'];
							$sqlBeds = mysql_query("SELECT ID,description,position,bedPlaced,bedDeposit,bedPrice,foodPrice,withoutFood FROM pgBedsTable WHERE pgId = '$pgId' and floorId = '$floorId' and roomId = '$roomId'", $this->db);
							$kitchenBeds=array();
							$hallBeds=array();
							$roomBeds=array();
							$bedroom1Beds=array();
							$bedroom2Beds=array();
							$bedroom3Beds=array();
							$bedroom4Beds=array();
							while($resultBed = mysql_fetch_array($sqlBeds,MYSQL_ASSOC)){
								if($resultBed['bedPlaced']=="Room"){
									array_push($roomBeds,array('id' => $resultBed['ID'],'description' => $resultBed['description'],'position' => $resultBed['position'],'bedDeposit' => $resultBed['bedDeposit'],'bedPrice' => $resultBed['bedPrice'],'foodPrice' => $resultBed['foodPrice'],'withoutFood' => $resultBed['withoutFood']));
								}elseif($resultBed['bedPlaced']=="Hall"){
									array_push($hallBeds,array('id' => $resultBed['ID'],'description' => $resultBed['description'],'position' => $resultBed['position'],'bedDeposit' => $resultBed['bedDeposit'],'bedPrice' => $resultBed['bedPrice'],'foodPrice' => $resultBed['foodPrice'],'withoutFood' => $resultBed['withoutFood']));
								}elseif($resultBed['bedPlaced']=="Kitchen"){
									array_push($kitchenBeds,array('id' => $resultBed['ID'],'description' => $resultBed['description'],'position' => $resultBed['position'],'bedDeposit' => $resultBed['bedDeposit'],'bedPrice' => $resultBed['bedPrice'],'foodPrice' => $resultBed['foodPrice'],'withoutFood' => $resultBed['withoutFood']));
								}elseif($resultBed['bedPlaced']=="Bedroom1"){
									array_push($bedroom1Beds,array('id' => $resultBed['ID'],'description' => $resultBed['description'],'position' => $resultBed['position'],'bedDeposit' => $resultBed['bedDeposit'],'bedPrice' => $resultBed['bedPrice'],'foodPrice' => $resultBed['foodPrice'],'withoutFood' => $resultBed['withoutFood']));
								}elseif($resultBed['bedPlaced']=="Bedroom2"){
									array_push($bedroom2Beds,array('id' => $resultBed['ID'],'description' => $resultBed['description'],'position' => $resultBed['position'],'bedDeposit' => $resultBed['bedDeposit'],'bedPrice' => $resultBed['bedPrice'],'foodPrice' => $resultBed['foodPrice'],'withoutFood' => $resultBed['withoutFood']));
								}elseif($resultBed['bedPlaced']=="Bedroom3"){
									array_push($bedroom3Beds,array('id' => $resultBed['ID'],'description' => $resultBed['description'],'position' => $resultBed['position'],'bedDeposit' => $resultBed['bedDeposit'],'bedPrice' => $resultBed['bedPrice'],'foodPrice' => $resultBed['foodPrice'],'withoutFood' => $resultBed['withoutFood']));
								}elseif($resultBed['bedPlaced']=="Bedroom4"){
									array_push($bedroom4Beds,array('id' => $resultBed['ID'],'description' => $resultBed['description'],'position' => $resultBed['position'],'bedDeposit' => $resultBed['bedDeposit'],'bedPrice' => $resultBed['bedPrice'],'foodPrice' => $resultBed['foodPrice'],'withoutFood' => $resultBed['withoutFood']));
								}
							}
							if(sizeof($roomBeds)>0){
								$resultAllJson['roomProp'] = array('beds' => $roomBeds);
							}elseif(sizeof($kitchenBeds)>0){
								$resultAllJson['kitchenProp'] = array('beds' => $kitchenBeds);
							}elseif(sizeof($hallBeds)>0){
								$resultAllJson['hallProp'] = array('beds' => $hallBeds);
							}elseif(sizeof($bedroom1Beds)>0){
								$resultAllJson['bedroom1Prop'] = array('beds' => $bedroom1Beds);
							}elseif(sizeof($bedroom3Beds)>0){
								$resultAllJson['bedroom2Prop'] = array('beds' => $bedroom2Beds);
							}elseif(sizeof($bedroom3Beds)>0){
								$resultAllJson['bedroom3Prop'] = array('beds' => $bedroom3Beds);
							}elseif(sizeof($bedroom4Beds)>0){
								$resultAllJson['bedroom4Prop'] = array('beds' => $bedroom4Beds);
							}
							array_push($resultAllJsonFull,$resultAllJson);
						}
						
						// If success everythig is good send header as "OK" and user details
						$this->response($this->json($resultAllJsonFull), 200);
					}
					$this->response('', 204);	// If no records "No Content" status
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "No floors found");
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
