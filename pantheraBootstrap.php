<?php
/*
 * Panthera web-bootstrap
 *
 * @package Panthera\bootstrap
 * @author Damian Kęska
 * @copyright Copyleft by Panthera Framework Team
 */

session_start();
ini_set('default_socket_timeout', 3600);
set_time_limit(3600);

$sessionName = md5($_SERVER['SCRIPT_FILENAME']);
 
// if you fork this project you can change this url to your own that points to a zipped project files
$zipName = 'panthera-master.zip';
$zipURL = 'http://pantheraframework.org/tarballs/' .$zipName;
$password = '';

/**
 * Displays CSS styles
 *
 * @package Panthera\bootstrap
 * @return null
 */
 
function displayCSS()
{
?>
<style>
            html * { padding:0; margin:0; }
            body * { padding:10px 20px; }
            body * * { padding:0; }
            body { font:small sans-serif; background:#eee; }
            body>div { border-bottom:1px solid #ddd; }
            h1 { font-weight:normal; margin-bottom:.4em; }
            h1 span { font-size:60%; color:#666; font-weight:normal; }
            table { border:none; border-collapse: collapse; width:100%; }
            td, th { vertical-align:top; padding:2px 3px; }
            th { width:12em; text-align:right; color:#666; padding-right:.5em; }
            #info { background:#f6f6f6; }
            #info ol { margin: 0.5em 4em; }
            #info ol li { font-family: monospace; }
            #summary { background: #ffffcc; padding-top: 25px; padding-left: 50px; padding-bottom: 50px; }
            #summary h1 { font-size: 24px; font-weight: 700;}
            #summaryDetails { list-style-type: none; padding-left: 25px; font-size: 16px; padding-top: 5px; }
            
            .inner { padding-left: 25px; padding-bottom: 0px; }
            
            #explanation { background:#eee; border-bottom: 0px none; }
            div { padding-left: 50px; padding-bottom: 25px; }
            .lighter { background: #f6f6f6; }
            .footer { padding-bottom: 100px; }
            </style>
<?php
}

/**
 * Displays layout with $title and $text
 *
 * @param string $title Title
 * @param string $text Page content
 * @package Panthera\bootstrap
 * @author Damian Kęska
 * @return null
 */

function displayLayout($title, $text)
{
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title;?></title>
        <meta charset="utf-8">
        <META NAME="ROBOTS" CONTENT="NOINDEX">
        <?php displayCSS(); ?>
    </head>

    <body>
        <div id="summary">
            <h1><?php echo $title; global $sessionName; global $password; if($password and $_SESSION[$sessionName] == $password) {?> <a href="?logout">(Logout)</a><?php }?></h1>
            
            <ol id="summaryDetails">
                <?php echo $text;?>
            </ol>
        </div>
    </body>
</html>
<?php
}

/**
 * Copy files recursively
 *
 * @param string $source
 * @param string $dest
 * @see http://stackoverflow.com/questions/5707806/recursive-copy-of-directory
 * @return bool
 */

function copyr($source, $dest)
{
   // Simple copy for a file
   if (is_file($source)) {
      return copy($source, $dest);
   }

   // Make destination directory
   if (!is_dir($dest)) {
      mkdir($dest);
      $company = ($_POST['company']);
   }

   // Loop through the folder
   $dir = dir($source);
   while (false !== $entry = $dir->read()) {
      // Skip pointers
      if ($entry == '.' || $entry == '..') {
         continue;
      }

      // Deep copy directories
      if ($dest !== "$source/$entry") {
         copyr("$source/$entry", "$dest/$entry");
      }
   }

   // Clean up
   $dir->close();
   return true;
}

// authorization
if ($password)
{
    if (isset($_POST['passwd']))
    {
        if ($_POST['passwd'] == $password)
        {
            $_SESSION[$sessionName] = $password;
        }
    }
    
    if (isset($_GET['logout']))
        unset($_SESSION[$sessionName]);
    
    if (!isset($_SESSION[$sessionName]) or $_SESSION[$sessionName] != $password)
    {
        displayLayout('Authentication required', '<li><form action="?" method="POST">Password: <input type="password" name="passwd"> <input type="submit" value="Login"></form></li>');
        exit;
    }
}

if (!is_file('./' .$zipName))
{
    $log = "<li>Downloading \"".$zipURL."\"...</li>\n";

    $ctx = stream_context_create(array('http'=>
        array(
            'timeout' => 3600, // 1 hour
        )
    ));

    $fp = fopen('./' .$zipName, 'w');
    fwrite($fp, file_get_contents($zipURL, false, $ctx));
    fclose($fp);
    
    if (!is_file('./' .$zipName))
    {
        displayLayout('Download error', '<li>Cannot save file, please check write permissions!</li>');
        exit;
    }
    
    $zip = new ZipArchive;
    
    if ($zip -> open('./' .$zipName) !== True)
    {
        displayLayout('Error extracting archive', '<li>Invalid ZIP archive, please try again or download archive from URL "' .$zipURL. '" manually and place it in this directory</li>');
        unlink('./' .$zipName);
        exit;
    }
    
    $log .= "<li>Extracting archive...</li>\n";
    $zip -> extractTo('./.pantheralib');
    $zip -> close();
    
    // restrict access to pantheralib on Apache webserver
    $fp = fopen('./.pantheralib/.htaccess', 'w');
    fwrite($fp, "deny from all\n");
    fclose($fp);
    
    displayLayout('Unpacked Panthera libraries', $log. '<li>Panthera libraries are now in ./.pantheralib directory</li>');
    print('<meta http-equiv="refresh" content="1">');
}

if (is_dir('./.pantheralib'))
{
    if (isset($_POST['projectname']))
    {
        $dir = addslashes($_POST['projectname']);
    
        if (!preg_match('/^([A-Za-z_\-\ \.\,0-9]+)$/', $dir))
        {
            displayLayout('Invalid name', '<li>Invalid project directory name, please use only A-Z, a-z, 0-9, dot, _ and -</li><li><a href="?">Back</a></li>');
            exit; 
        }
        
        if (is_dir($dir))
        {
            displayLayout('Invalid name', '<li>Directory "' .realpath($dir). '" already exists</li><li><a href="?">Back</a></li>');
            exit; 
        }
        
        copyr('./.pantheralib/example-app', './' .$dir);
        
        foreach (scandir('./' .$dir. '/') as $oldController)
        {
            if (strtolower(pathinfo($oldController, PATHINFO_EXTENSION)) !== 'php')
            {
                continue;
            }
            
            unlink('./' .$dir. '/' .$oldController);
        }
        
        foreach (scandir('./.pantheralib/lib/frontpages') as $controller)
        {
            if (strtolower(pathinfo($controller, PATHINFO_EXTENSION)) !== 'php')
            {
                continue;
            }
            
            symlink(realpath('./.pantheralib/lib/frontpages/' .$controller), './' .$dir. '/' .$controller);
        }
        
        $config = array(
            'lib' => realpath('./.pantheralib/lib'),
        );
        
        $fp = fopen('./' .$dir. '/content/app.php', 'w');
        fwrite($fp, "<?php\n## PANTHERA BOOTSTRAP ##\n\$config = ".var_export($config, true));
        fclose($fp);
        
        session_destroy();
        header('Location: ./' .$dir. '/install.php');
    }

    displayLayout('Create a new Panthera application', '<form action="?" method="POST"><li><b>Application directory name:</b> <input type="text" name="projectname"> <input type="submit" value="Create"></li></form>');
}
