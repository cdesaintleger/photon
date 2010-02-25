<?php
/**
 * MongoHandler Class
 * Manages all MongoDB  transactions. Collects returned data into easily
 * accessible arrays and reports all errors.
 *
 * @version 1.3
 * @author LightCube Solutions
 * @copyright 2010 LightCube Solutions, LLC.
 * @license http://www.lightcubesolutions.com/LICENSE
 */

class MongoHandler
{
    protected $dbname;   // The database name
    protected $dbuser;   // The database user name
    protected $dbpass;   // The database user's password

    public $cursor;      // The Mongo cursor for returned data
    public $error;       // The last error message issued by the Server
    public $db;          // The actual DB object.
    public $grid; 		 // Grid object
    public $fileid; 	 // The mongoid of a stored file
    
    private $_link;      // The link to the server.
    private $_connected; // Status of the server connection.
  
    /**
     * __construct function.
     * 
     * Initializes a database connection which will be present while the object is in use.
     * @access public
     * @return void
     */
    function __construct()
    {
        // Make the initial connection
        // FIXME: could probably allow for all the PHP options, here, like server URL, etc
        $this->_link = new Mongo();
        
        // Select the DB
        $this->db = $this->_link->selectDB($this->dbname);

        // Authenticate
        $result = $this->db->authenticate($this->dbuser, $this->dbpass);
        if ($result['ok'] == 0) {
            // Authentication failed.
            $this->error = $result['errmsg'];
            $this->_connected = false;
        } else {
            $this->_connected = true;
        }
    }

    /**
     * __destruct function.
     * 
     * Closes the database connection when the object is no longer used.
     * @access public
     * @return void
     */
    function __destruct()
    {
        $this->_link->close();
    }
    
    /**
     * getData function.
     * Return all data from a collection with optional sorting and filtering
     * 
     * @param string $collection
     * @param array $sort
     * @param array $where
     * @return boolean
     */
    function getData($collection, $sort = array(), $where = array())
    {
        $retval = false;
        // Only try if the connection has been established.
        if ($this->_connected) {
            // Specify the collection.
            $col = $this->db->$collection;
                        
            // Grab the data
            $this->cursor = $col->find($where);

            if ($this->cursor->count() > 0) {
                $retval = true;
                $this->cursor->sort($sort);
            }
        }
        return $retval;
    }
    
    /**
     * saveFile function
     * Saves one file to the database with a specificed path
     * 
     * @param string $path
     * @param array $info
     * @return boolean
     */
    function saveFile($path, $info = array())
    {
    	$retval = false;
    	// Only try if the connection and path has been established.
 		
    	if ($this->getGrid() && isset($path)){
    		$this->fileid = $this->grid->storeFile($path, $info);
    		$retval = true;
    	}
    	return $retval;
    }
    
    /**
     * removeFile function
     * Removes one or more files from Mongo depending on the specification
     * @param array $where
     * @param boolean $isRemoved
     * @return boolean
     */
    function removeFile($where = array())
    {
    	$retval = false;
    	//Only try if the connection and where is set
    	if ($this->getGrid() && isset($where))
    	{
    		//Remove the file Mongo based on the $where param
			$isRemoved = $this->grid->remove($where);
			$retval = ($isRemoved == TRUE)? TRUE : FALSE;
    	}	
    	return $retval;
    }
    
    /**
     * getGrid funtion
     * Gets the GridFS Object once the connection is made to Mongo
     * @return boolean
     */
    function getGrid()
    {
    	$retval = false;
    	if ($this->_connected){
    		//Get GridFS Object
    		$this->grid = $this->db->getGridFS();
    		$retval = true;
    	}
    	return $retval;
    }
}

?>