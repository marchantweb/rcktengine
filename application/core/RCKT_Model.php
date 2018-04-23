<?php

class RCKT_Model extends CI_Model {
	
	// Database Constants
	const DB_TABLE = 'abstract';
	const DB_TABLE_PK = 'abstract';
	const DB_FIELDS_SETUP = array();
	
	// Database Variables
	private $DB_LOADED = FALSE;
	private $DB_FIELDS = array();
	private $JIT_PENDING_FUNCTIONS = array();
	private $JIT_LOADED_FUNCTIONS = array();
	
	// Construct
	function __construct($data = NULL){
        parent::__construct();
		$this->{$this::DB_TABLE_PK} = NULL;
		foreach ($this::DB_FIELDS_SETUP as $database_field) {
			$this->{$database_field} = NULL;
			$this->DB_FIELDS[$database_field] = $this->{$database_field};
		}
		if(isset($GLOBALS['SYSTEM_CACHE_'.$this::DB_TABLE]) === FALSE){
			$GLOBALS['SYSTEM_CACHE_'.$this::DB_TABLE] = array();
		}
		$methods = get_class_methods($this);
		foreach ($methods as $method) {
		  if (strpos($method, 'JIT_') !== false) {
			 $target_method = str_replace("JIT_","",$method);
			 $this->JIT_PENDING_FUNCTIONS[] = $target_method;
		  }
		}
		if($data != NULL){
			$this->load($data);
		}
   }
	
	// JIT (Just-in-time) Initialization
	public function __call($target_method, $args)
    {
		if(in_array($target_method, $this->JIT_PENDING_FUNCTIONS)){
			// If it's a JIT method that hasn't loaded yet
			if (($key = array_search($target_method, $this->JIT_PENDING_FUNCTIONS)) !== false) {
				unset($this->JIT_PENDING_FUNCTIONS[$key]);
			}
			$this->JIT_LOADED_FUNCTIONS[] = $target_method;
			$callname = "JIT_".$target_method;
			if($this->$callname($args)){
				return $this;
			}else{
				return FALSE;
			} 
		}else if(in_array($target_method, $this->JIT_LOADED_FUNCTIONS)){
			// If it's a JIT method that has already loaded (cached)
			return $this;
		}else{
			// Any other method call
			return FALSE;
		}   
    }
	
	// Deep Caching
	public function __clone() {
		foreach($this as $key => $val) {
			if (is_object($val) || (is_array($val))) {
				$this->{$key} = unserialize(serialize($val));
			}
		}
	}
	
	// Gather Data Prior to Saving
	private function output_data(){
		$temparray = array();
		foreach($this->DB_FIELDS as $key => $value){
			if($this->{$key} != NULL){
				$temparray[$key] = $this->{$key};
			}else{
				$temparray[$key] = "";
			}
		}
		return $temparray;
	}	
		
	// Insert a New Record
	private function insert(){		
		$this->db->insert($this::DB_TABLE, $this->output_data());
		$this->{$this::DB_TABLE_PK} = $this->db->insert_id();
	}
	
	// Update a Record
	private function update(){
		$this->db->where($this::DB_TABLE_PK, $this->{$this::DB_TABLE_PK});
		$this->db->update($this::DB_TABLE, $this->output_data());
	}

	// Populate a Record using a database row object
	public function populate($row){		
		foreach ($row as $key => $value){
			if(in_array($this->$key, $this->DB_FIELDS)) {
				$this->$key = $value;
			}
		}
		$this->DB_LOADED = TRUE;
		
		if(isset($GLOBALS['SYSTEM_CACHE_'.$this::DB_TABLE][$this->{$this::DB_TABLE_PK}]) === FALSE){
			$GLOBALS['SYSTEM_CACHE_'.$this::DB_TABLE][$this->{$this::DB_TABLE_PK}] = clone $this;
		}
		$this->ID = $this->{$this::DB_TABLE_PK};
		///////////////////////////////////
		// Auto-Formatting Common Fields //
		///////////////////////////////////
		if(isset($this->phone)){
			$this->phone = preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '($1) $2-$3', $this->phone);
		}
		if(isset($this->email)){
			$this->email = strtolower($this->email);
		}
		if(isset($this->first) && isset($this->last)){
			$this->first = ucwords(strtolower($this->first));
			$this->last = ucwords(strtolower($this->last),"- (");
			$this->display_name = $this->first.' '.$this->last;
		}
        // JSON Representation
		if($this->post_load()){
			$this->JSON_REPRESENTATION = htmlentities(json_encode($this));
			return TRUE;
		}else{
			return FALSE;
		}
		
	}
	
	// Load from the Database
	public function load($data){
		if( (is_array($data)) || (is_object($data)) ){
			$this->db->limit(1);
			$query = $this->db->get_where($this::DB_TABLE, $data);
			$this->db->flush_cache();
		}else{
			$id = intval($data);
			if (is_a($GLOBALS['SYSTEM_CACHE_'.$this::DB_TABLE][$id], get_class())) {
				return $this->populate($GLOBALS['SYSTEM_CACHE_'.$this::DB_TABLE][$id]);
			}else{
				$this->db->limit(1);
				$query = $this->db->get_where($this::DB_TABLE, array(
					$this::DB_TABLE_PK => $id,
				));	
				$this->db->flush_cache();
			}
		}
		if($query->num_rows() > 0){
			return $this->populate($query->row());
		}else{
			return FALSE;
		}	
	}

	
	// Static post_load action, gets replaced by sub-classes if needed
	public function post_load(){
		return TRUE;
	}
	
	// Delete the current record
	public function delete(){
		$this->db->delete($this::DB_TABLE, array(
			$this::DB_TABLE_PK => $this->{$this::DB_TABLE_PK},
		));
		unset($this->{$this::DB_TABLE_PK});
	}
	
	// Save the record, either with insert() or update()
	public function save(){
		if($this->{$this::DB_TABLE_PK} > 0){
			$this->update();
		}else{
			$this->insert();
		}
	}
	
		
	
}

/* End of file RCKT_Model.php */
/* Location: application/core/RCKT_Model.php */
