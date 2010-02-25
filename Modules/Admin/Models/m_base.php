<?php
/**
 * m_base.php - Base Model Class
 * The base file contains the basic strcuture of all Models.
 * All methods contained in this class should be useable by multiple models
 * 
 * @package photon 
 * @version 1.0-a
 * @author LightCube Solutions <info@lightcubesolutions.com>
 * @copyright LightCube Solutions, LLC. 2010
 */
defined("__photon") || die();



class BaseModel extends DBConn
{

	public $data;		//Data to be inserted
	public $criteria;	//Search criteria for find
	public $error;		//Errors from MongoDB

	/*
	 * addUnique function - Inserts a document if key is unique
	 * @access public
	 * @return boolean
	 */
	public function addUnique()
	{
		$exists = $this->col->findOne($this->criteria);
		if (empty($exists)) {
			unset($this->data['add']);
			try{
	            $this->col->insert($this->data);
			}
			catch(MongoCursorException $e) {
            	$db->error = $e;
            	
        	}
		}
	}
	
	/**
	 * update function
	 * Updates document in MongoDB based on _id
	 * @return void
	 */
	public function update()
	{
		unset($this->data['update']);
		$id = new MongoID($this->data['_id']);
		unset($this->data['_id']);
		$this->col->update(array('_id'=>$id), array('$set'=>$this->data));		
	}
	
	/**
	 * delete function
	 * Removes document from MongoDB based on _id 
	 * @return void
	 */
	public function delete()
	{
		 $this->col->remove(array('_id'=>new MongoID($this->data['_id'])));
	}
	
	/**
	 * add function inserts data into MongoDB only if ActionName is unique
	 * @return boolean
	 */
	public function add()
	{
		$retval = false;
		if (empty($this->data)){
			//Can't insert empty data!!!			
			$retval = false;
		}
		else{			
			//Add unique document if it is unique
			$retval = $this->addUnique();				
		}		
		return $retval;  		
	}	
}

?>