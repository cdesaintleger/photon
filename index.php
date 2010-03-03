<?php
/**
 * index.php - The entry point for the application
 *
 * @package photon
 * @version 1.0
 * @author LightCube Solutions <info@lightcubesolutions.com>
 * @copyright LightCube Solutions, LLC. 2010
 * @license http://www.lightcubesolutions.com/LICENSE
 */

// Include site configuration.
require('config.php');

// Auto include necessary classes - PHP runs this whenever it is asked
// to use a Class it doesn't yet know about.
function __autoload($class)
{
    $try = "Library/$class.php";
    if (file_exists($try)) {
        require($try);
    } else {
        // Maybe this is a Model
        $name = str_replace('model', '', strtolower($class));
        $modules = scandir('Modules');
        foreach ($modules as $module) {
            if (is_dir("Modules/$module")) {
                $try = "Modules/$module/Models/m_$name.php";
                if (file_exists($try)) {
                    require($try);
                    break;
                }
            }
        }
    }
}

// Instantiate new global instances of classes we will reuse.
$dispatch = new Dispatcher;
$auth = new Authentication;
$view = new View;

// Set the use tidy option.
$view->usetidy = $usetidy;

// Set the theme.
$view->theme = $theme;

// Start the PHP session
session_name($appkey);
session_start();

// Defined constant to prevent subfiles from being accessed directly.
define('__photon', true);

// Set default TimeZone
date_default_timezone_set($tz);

// Make sure the Session isn't expired or the recorded IP is invalid
$auth->checkSession();

// Parse the $_REQUEST array, look for the action
$dispatch->parse($_REQUEST);

if ($dispatch->status === false) {
    // No matching action found in the DB. Just use the default 
    $dispatch->controller = 'Modules/Home/Controllers/c_home.php';
}

if (!empty($dispatch->special)) {

    // Handle the special login or logout request if it was given
    // FIXME: Have view output the below.
    switch ($dispatch->special) {

        case 'login':
            if ($auth->login($_REQUEST['h'], $_REQUEST['u'])) {
                $auth->recordLogin();
                $auth->setSessionInfo($_REQUEST['u']);

                // Set the action - use a previously requested query, if it was requested before login
                // Otherwise, use the default
                $redirect = (empty($_SESSION['prev_query'])) ? "a=$default_action" : $_SESSION['prev_query'];

                // Perform the redirect
                // It's odd, but IE needs something here before the script, so add a space char '&nbsp;'
                echo "&nbsp;
                  <script>
                    window.location = '?$redirect';
                  </script>";
            } else {
                echo "Login Failed
                  <script>
                    document.getElementById('login_status').className = 'error';
                  </script>";
            }
            break;

        case 'logout':
            $auth->logout();

            // Redirect to empty request.
            header('Location: ?');
            break;
    }
    
} else {
    // Set up the Login form if we need to.
    if ($auth->login_form) {
        
        $_SESSION['key'] = $auth->randomString(20);
        $_SESSION['prev_query'] = $_SERVER['QUERY_STRING'];
        
        $view->assign('key', $_SESSION['key']);
        $view->register('js', 'sha1.js');
        $view->register('js', 'ajax_functions.js');
        $view->register('js', 'login.js');
        
    } else {
        // Set up the logout link
        $view->assign('loggedin', true);
        $view->assign('fullname', $_SESSION['FullName']);
    }
    include($dispatch->controller);
    $view->display();
}
?>
