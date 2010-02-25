<?php
/**
 * Authentication class
 * 
 * @package photon
 * @version 1.0
 * @author LightCube Solutions <info@lightcubesolutions.com>
 * @copyright LightCube Solutions, LLC. 2010
 * @license 
 */
 
class Authentication
{

    // A JavaScript function which is embedded in the HTML header.
    public $login_script = '<script type="text/javascript" src="JavaScript/login.js"></script>';

    public $hash;
    public $login_form = true;

    // Set the session timeout to 30 minutes.
    private $_timeout = 1800;
    
    const CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890123456789";

    /**
     * login function.
     * 
     * @access public
     * @param mixed $hash
     * @param mixed $user
     * @return boolean
     */
    function login($hash, $user)
    {
        $retval = false;
        $pass = $this->getPassword($user);
        if ($this->checkHash($_SESSION['key'], $pass, $hash)) {
            $retval = true;
        }
        return $retval;
    }


    /**
     * setSessionInfo function.
     * 
     * @access public
     * @param mixed $user
     * @return void
     */
    function setSessionInfo($user)
    {
        $db = new DBConn;
        if ($db->getData('Users', '', array('LoginName'=>$user))) {
            $data = $db->cursor->getNext();
            $_SESSION['LoginName']   = $data['LoginName'];
            $_SESSION['FirstName']   = ucwords(strtolower($data['FirstName']));
            $_SESSION['LastName']    = ucwords(strtolower($data['LastName']));
            $_SESSION['FullName']    = "$_SESSION[FirstName] $_SESSION[LastName]";
        }
    }


    /**
     * checkSession function.
     * 
     * @access public
     * @return void
     */
    function checkSession()
    {
        $dt = new DateTime;
        $timestamp = $dt->format('U');
        $id = session_id();
        $db = new DBConn;

        if ($db->getData('ActiveSessions', '', array('SessionID'=>$id))) {
            $data = $db->cursor->getNext();
            if ($data['IP'] == ip2long($_SERVER['REMOTE_ADDR'])) {
                // Session has already been recorded & the IP address matches
                // Just make sure the key exists and the session hasn't expired. 
                if (empty($_SESSION['key']) || ($timestamp - $_SESSION['timestamp']) >= $this->_timeout) {
                    $this->resetSession();
                } else {
                    $this->login_form = false;
                }
            } else {
                // Session recorded, but the IP address doesn't match.
                $this->resetSession();
            }
        } else {
            // This is either a new session, or the session info has been lost in the
            // database somehow. If the action is anything but 'login', force a login.
            $this->login_form = ($_REQUEST['a'] != 'login') ? true : false;
        }
        $_SESSION['timestamp'] = $timestamp;
    }


    /**
     * resetSession function.
     * 
     * @access public
     * @return void
     */
    function resetSession()
    {
        $id = session_id();
        $db = new DBConn;
        $this->login_form = true;
        $col = $db->db->ActiveSessions;
        $col->remove(array('SessionID'=>$id));
        $_SESSION = array();
    }


    /**
     * logout function.
     * 
     * @access public
     * @return void
     */
    function logout()
    {
        $this->resetSession();
        setcookie('PHPSESSID', '', time()-42000, '/');
        session_destroy();
    }


    /**
     * getPassword function.
     * 
     * @access public
     * @param mixed $user
     * @return string|boolean
     */
    function getPassword($user)
    {
        $retval = false;
        $db = new DBConn;
        if ($db->getData('Users', '', array('LoginName'=>"$user", 'IsEnabled'=>1))) {
            $row = $db->cursor->getNext();
            $retval = $row['Password'];
        }
        return $retval;
    }


    /**
     * recordLogin function.
     * 
     * @access public
     * @return boolean
     */
    function recordLogin()
    {
        $retval = false;
        $id = session_id();
        $db = new DBConn;
        $col = $db->db->ActiveSessions;
        try {
            $col->insert(array('SessionID'=>$id, 'IP'=>ip2long($_SERVER['REMOTE_ADDR'])), true);
            $retval = true;
        } catch(MongoCursorException $e) {
            $db->error = $e;
        }
        return $retval;
    }


    /**
     * randomString function.
     * 
     * @access public
     * @param int $length
     * @return string
     */
    function randomString($length)
    {
        $str = NULL;
        for ($i = 0; $i < $length; ++$i) {
            $str .= substr(self::CHARS, rand(0, 71), 1);
        }
        return $str;
    }


    /**
     * checkHash function.
     * 
     * @access public
     * @param mixed $key
     * @param mixed $pass
     * @param mixed $sent
     * @return boolean
     */
    function checkHash($key, $pass, $sent)
    {
        $retval = false;
        $this->hash = sha1($key.$pass);
        $retval = ($this->hash == $sent) ? true : false;
        return $retval;
    }

}

?>