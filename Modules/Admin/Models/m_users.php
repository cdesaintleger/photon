<?php
/**
 * m_users.php - Users Model Class
 * 
 * @package photon 
 * @version 1.0-a
 * @author LightCube Solutions <info@lightcubesolutions.com>
 * @copyright LightCube Solutions, LLC. 2010
 */

class UsersModel extends Model
{
	protected $criteria;    //Search criteria for find
	/**
     * __construct function initializes collection name
     * @access public
     * @return void
     */
	function __construct()
	{
		parent::__construct();
		$this->col = $this->db->Users;		
	}

	/**
	 * add function inserts data into MongoDB only if Username is unique
	 * @return boolean
	 */
	function add($data)
	{
		//Setup the criteria
		$this->criteria = array('Username'=>$data['Username']);
		return parent::add($data);
	}
	
	/**
	 * getGroups function
	 * find all direct groups assigned to a user
	 * 
	 * @param $id
	 * @return mixed
	 */
	function getGroups($id)
	{
	    $retval = false;
	    // Pull all groups for a user
	    $user = $this->col->findOne(array('_id'=>new MongoID($id), 'IsEnabled'=>'1'));
	    if (!empty($user['Groups'])) {
	        $retval = $user['Groups'];
	    }
	    return $retval;
	}
}

?>