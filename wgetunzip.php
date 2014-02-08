<?php
/**
* @version 
* @package WgetUnzip - wget and unzip, A tiny wget and unzip PHP program to do direct download via web browser and unzip to your site
* @copyright (C) 2011 ongetc.com
* @info ongetc@ongetc.com http://ongetc.com
* @license GNU/GPL http://ongetc.com/gpl.html.
*/
?>
<html><body>
<style type="text/css">
 A {text-decoration:none;}
 A: hover {text-decoration:underline;}
 body {
  background-color: #E3F2E1;
 }
 .login {
   align: center;
   width: 25%;
   text-align: center;
 }
 .unzipForm {
   align: center;
   width: 50%;
   text-align: center;
 }
</style>
<?php 
define("_WGETUNZIP", TRUE);
session_start();
ob_implicit_flush(true);
$wgetunzip = new WgetUnzip();
echo $wgetunzip->doMain(); 
?>
</body></html>
<?php
// end of main

// WgetUnzip class
class WgetUnzip {
  var $pwFile;
  var $me;
  var $remoteFile;
	function wGetUnzip() {
    $this->pwFile = ".pw.php";
    $this->me = "WgetUnzip";
    $this->remoteFile = "http://coaddonscms.googlecode.com/files/wgetunzip.zip";
	}
	function doMain() {
		$buff = "";
		if ($_GET["action"] == "logout") $this->doLogout();
		$buff .= $this->doMenu();
		if (file_exists($this->pwFile)) {
		if ($_GET["action"] == "wget") $buff .= $this->doWget();
		elseif ($_GET["action"] == "unzip") $buff .= $this->unzipForm($_GET["zipfile"]);
		elseif (!empty($_GET["zipfile"])) $buff .= $this->doUnzip($_GET["zipfile"]);
		elseif (!empty($_POST["url"])) $buff .= $this->wGet($_POST["url"],$_POST["filename"]);
		else $buff .= $this->listZipFiles();
		}
		return $buff;
	}
  function doMenu() {
    ($this->isLogin()==TRUE) ? $logout = " - <a href='?action=logout'>[Logout]</a>" : $logout="";
    $wGetURL = " - <a href='?action=wget'>[Wget]</a>";
		return "<div><span><a href='?'>[GoBack]</a>$wGetURL.$logout</span></div>";
  }
  function doUnzip($unzip) {
    $buff = "<br />Nothing to do!";
    if (is_file($unzip) and ($_GET["action"] == "view" or $_GET["action"] == "dounzip")) {
      $zip = new Wunzip;
      if (($list = $zip->listContent($unzip)) == 0) die("Error : ".$zip->errorInfo($zip));
      if (!empty($list)) {
        $buff = $this->zipFileStatInfo($unzip, $list);
        if ($_GET["action"] == "view") $buff .= $this->showZipFile($list);
        elseif (!empty($_POST["dounzip"]) and !empty($_POST["filename"])) 
          $buff .= $zip->extractZip($_POST["filename"], $_POST["dest"]);
      } 
    }
    return $buff;
  }
  function unzipForm($unzip) {
    $dest=eregi_replace("[\\]","/",dirname(__FILE__)."/");
		return '<div align="center">You can change unzip output folder
<form class="unzipForm" method="POST" action="?action=dounzip&zipfile='.$unzip.'">
<fieldset>
<legend>Unzip File to folder</legend>	
<p />
<label>FileName:</label>&nbsp;<input type="text" name="filename" size="45" value="'.$unzip.'">
<p />
<label>Save to:</label>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="dest" size="45" value="'.$dest.'">
<p />
<input type="submit" value="Do Unzip" name="dounzip">
</fieldset>
</form>
</div>';
	}
  function doLogin() {
		$logedIn = FALSE;
    include $this->pwFile;
    $adm_user	=	$_POST['adm_user'];
    $adm_pass	=	$_POST['adm_pass'];
    if ($reg_user != $adm_user || $reg_pass != $adm_pass) {
      echo "Login error: incorrect username or password!<br>";
    } else {
      $time_started = md5(mktime());
      $secure_session_user = md5($adm_user.$adm_pass);
      $_SESSION['user'] = $adm_user;
      $_SESSION['session_key'] = $time_started.$secure_session_user.session_id();
      $_SESSION['current_session'] = $adm_user."=".$_SESSION['session_key'];
      $logedIn = TRUE;
    }
    return $logedIn;
  }
	function isLogin() {
		$logedIn = FALSE;
    if (!empty($_POST['login'])) {
      $logedIn = $this->doLogin();
    } elseif ($_SESSION['current_session'] ==	$_SESSION['user']."=".$_SESSION['session_key']) {
      $logedIn = TRUE;
    }
    if (!empty($_POST['reg'])) $this->doRegister();
		if (!file_exists($this->pwFile)) {
			echo $this->registerForm();
      die();
		} elseif ($logedIn<>TRUE) {
      echo $this->loginForm();
    	die();
		} 
    return $logedIn;
	}
	function wGetForm($url,$rfile="") {
		return '<div align="center">You can change output folder
<form class="unzipForm" method="POST" action="?action=download">
<fieldset>
<legend>wGet Form</legend>	
<p />		
<label>From URL:</label>&nbsp;<input type="text" name="url" size="55" value="'.$url.'">
<p />	
<label>Save to:</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="filename" size="55" value="'.$rfile.'">
<p />	
<input type="submit" value="Get file" name="getfile">
</form>
</fieldset>
</div>';
	}
  function wGet($strURL, $strFilename="") {
    if (!$strFilename) {
      $strFilename=dirname(__FILE__)."/".basename($strURL);
      $strFilename=eregi_replace("[\\]","/",$strFilename);
    } else {
      if (is_dir($strFilename)) {
        $strFilename .= basename($strURL);
      }
    }
    file_put_contents($strFilename,file_get_contents($strURL));
		return "<br />[$strURL] has been downloaded to [$strFilename]";
  }
	function listZipFiles() {
		$buff = "<div><div>";
		$buff .= "<p />List of zip files: (Note: click file name to view or <font color=red>[unzip]</font> next to each file to extract that file.)";
		$buff .= "<br />";
		$filez = $this->getDirList();
		reset($filez);
		if (sizeof($filez) > 0) {
			foreach ($filez as $filename) {
				if ((strtolower($this->fileExt($filename)) == "zip") and (is_file($filename))) {
					$buff .= "&nbsp;&nbsp;<a href='?action=view&zipfile=".$filename."' title='View archive contents'>".$filename."</a>";
					$buff .= " -- <a href='?action=unzip&zipfile=".$filename."' title='Extract files from archive'>[unzip]</a>";
          $buff .= "<br>";
				}
			}
		}
		$buff .= "</div></div>";
		return $buff;
	}
	function showZipFile($list) {
		for ($i = 0; $i < sizeof($list); $i++) {
      $foldername = dirname($list[$i][stored_filename]);
      $abuff[$foldername][$i]['filename'] = basename($list[$i][stored_filename]);
      $abuff[$foldername][$i]['size'] = $list[$i][size];
		}
		$buff = "";
		foreach ($abuff as $key=>$value) {
			$line = "<br />&nbsp;&nbsp;&nbsp;&nbsp;<b>Folder:</b> [".$key."] <b>Total files:</b> ".count($value);
			foreach ($value as $k=>$v) {
				$line .= "<br />".$v['filename']." [".$this->convertSize($v['size'])."]";
			}
			$buff .= $line;
		}
		return $buff."<p />Done!";
	}
  function doWget() {
    $strFilename=eregi_replace("[\\]","/",dirname(__FILE__)."/");
    return $this->wGetForm($this->remoteFile, $strFilename);
  }
	function zipFileStat($list) {
		$fold=$fil=0;
		for ($i = 0; $i < sizeof($list); $i++) {
			($list[$i][folder] == "1") ? $fold++ : $fil++;
			$tot_comp += $list[$i][compressed_size];
			$tot_uncomp += $list[$i][size];
		}
		return array($fold,$fil,$tot_comp,$tot_uncomp);
	}
	function zipFileStatInfo($unzip, $list) {
		$buff="";
		list($fold,$fil,$tot_comp,$tot_uncomp) = $this->zipFileStat($list);
		$buff .= "<br />File: [$unzip] has $fil files and $fold directories! ";
		$buff .= $this->convertSize($tot_comp) . " (Compressed) - ";
		$buff .= $this->convertSize($tot_uncomp) . " (Uncompressed)<br>\n";
		return $buff;
	}
	function fileExt($file) {
		$p = pathinfo($file);
		return $p['extension'];
	}
	function convertSize($size) {
		$times = 0;
		$comma = '.';
		while ($size > 1024) {
			$times++;
			$size = $size / 1024;
		}
		$size2 = floor($size);
		$rest = $size - $size2;
		$rest = $rest * 100;
		$decimal = floor($rest);
		$addsize = $decimal;
		if ($decimal < 10) {
			$addsize .= '0';
		};
		if ($times == 0) {
			$addsize = $size2;
		} else {
			$addsize = $size2 . $comma . substr($addsize, 0, 2);
		}
		switch ($times) {
			case 0 : $mega = ' bytes';
			break;
			case 1 : $mega = ' KB';
			break;
			case 2 : $mega = ' MB';
			break;
			case 3 : $mega = ' GB';
			break;
			case 4 : $mega = ' TB';
			break;
		}
		$addsize .= $mega;
		return $addsize;
	}
	function getDirList() {
		$glob = array();
		$c = 0;
		$dh = opendir(getcwd());
		if (!empty($dh)) {
			while (($file = readdir($dh)) !== false) {
				if ($file != '..' && $file != '.')
				$glob[$c++] = $file;
			}
			closedir($dh);
		}
		return $glob;
	}
  function doLogout() {
    $_SESSION['current_session'] = rand(100,9000000);
    $_SESSION['curr_sess_iden'] = rand(100,9000000);
    $_SESSION['user'] = "Logged out";
    $_SESSION['session_key'] = rand(100,9000000);
  }
	function loginForm() {
    return '<div align="center">Please use your login that you registered previously.<p />
<form class="login" method="POST" action="?action=login">
<fieldset>
<legend>Admin Login Form</legend>
<label>Admin Username:</label>&nbsp;<input type="text" name="adm_user" size="15"><br />
<label>Admin Password:</label>&nbsp;<input type="password" name="adm_pass" size="15"><br />
<input type="submit" value="Login" name="login">
</fieldset>
</form></div>';
	}
  function registerForm() {
		return '<div align="center">['.$this->me.'] requires ['.$this->pwFile.'] but does not exist!   
<p />Must register admin user and password for using to login later!<br />
<form class="login" method="POST" action="?action=reg">
<fieldset>
<legend>Admin Register Form</legend>
<p />
<label>Admin Username:</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="adm_user" size="15"><p />
<label>Admin Password:</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="password" name="adm_pass" size="15"><p />
<label>Confirm Password:</label>&nbsp;&nbsp;<input type="password" name="adm_pass_conf" size="15"><p />
<p />
<input type="submit" value="Register" name="reg">
</fieldset>
</form></div>';
	}
	function doRegister() {
		$adm_user = $_POST['adm_user'];
		$adm_pass = $_POST['adm_pass'];
		$adm_pass_conf = $_POST['adm_pass_conf'];
		if ($_POST['reg']!='') {
			if ($adm_user == '' || $adm_pass == '' || $adm_pass_conf == '') $err = 'At least one of the fields is empty!<br>';
			if ($adm_pass != $adm_pass_conf) $err .= 'Passwords do not match!';
			if ($err == '') { //store passwords in this file
				$fn = fopen($this->pwFile,'w');
				$buff = '<?php defined( "_WGETUNZIP" ) or die("Direct Access to this location is not allowed."); $reg_user = '."'".$adm_user."'".'; $reg_pass = '."'".$adm_pass."'"."; ?>\n";
				fputs($fn, $buff);
				fclose($fn);
			} else {
        die ($err);
      }
		}
	}
  function reDirect() {
    header("Location: .");
  }
}
// Wunzip class
class Wunzip {
  public function listContent($src) {
    if (($zip = zip_open(realpath($src)))) {
      $i =0;
      while (($zip_entry = zip_read($zip))) {
        $path = zip_entry_name($zip_entry);
        if (zip_entry_open($zip, $zip_entry, "r")) {
          $content[$i][filename] = $path;
          $content[$i][stored_filename] = $path;
          $content[$i][size] = zip_entry_filesize($zip_entry);
          $content[$i][compressed_size] = zip_entry_compressedsize($zip_entry);
          $content[$i][ratio] = zip_entry_filesize($zip_entry) ? round(100-zip_entry_compressedsize($zip_entry) / zip_entry_filesize($zip_entry)*100, 1) : false;
          
          (is_dir(zip_entry_name($zip_entry))==true)
          ? $content[$i][folder] = 1
          : $content[$i][folder] = '';
          zip_entry_close($zip_entry);
        } 
        $i++;  
      }
      zip_close($zip);
    } else {
      $content = 0;
    }
    return $content;
  }
  public function extractZip ($src, $dest) {
    $zip = new ZipArchive;
    $path = realpath($src);
    if ($zip->open($path)===true) {
      $zip->extractTo($dest);
      $zip->close();
      return "<br />[$src] has been extracted to [$dest]";
    }
    return false;
  }
  function errorInfo($errno) {
    $return = 'Zip File Function error: unknown';
    $zipFileFunctionsErrors = array(
      'ZIPARCHIVE::ER_MULTIDISK' => 'Multi-disk zip archives not supported.',
      'ZIPARCHIVE::ER_RENAME' => 'Renaming temporary file failed.',
      'ZIPARCHIVE::ER_CLOSE' => 'Closing zip archive failed',
      'ZIPARCHIVE::ER_SEEK' => 'Seek error',
      'ZIPARCHIVE::ER_READ' => 'Read error',
      'ZIPARCHIVE::ER_WRITE' => 'Write error',
      'ZIPARCHIVE::ER_CRC' => 'CRC error',
      'ZIPARCHIVE::ER_ZIPCLOSED' => 'Containing zip archive was closed',
      'ZIPARCHIVE::ER_NOENT' => 'No such file.',
      'ZIPARCHIVE::ER_EXISTS' => 'File already exists',
      'ZIPARCHIVE::ER_OPEN' => 'Can\'t open file',
      'ZIPARCHIVE::ER_TMPOPEN' => 'Failure to create temporary file.',
      'ZIPARCHIVE::ER_ZLIB' => 'Zlib error',
      'ZIPARCHIVE::ER_MEMORY' => 'Memory allocation failure',
      'ZIPARCHIVE::ER_CHANGED' => 'Entry has been changed',
      'ZIPARCHIVE::ER_COMPNOTSUPP' => 'Compression method not supported.',
      'ZIPARCHIVE::ER_EOF' => 'Premature EOF',
      'ZIPARCHIVE::ER_INVAL' => 'Invalid argument',
      'ZIPARCHIVE::ER_NOZIP' => 'Not a zip archive',
      'ZIPARCHIVE::ER_INTERNAL' => 'Internal error',
      'ZIPARCHIVE::ER_INCONS' => 'Zip archive inconsistent',
      'ZIPARCHIVE::ER_REMOVE' => 'Can\'t remove file',
      'ZIPARCHIVE::ER_DELETED' => 'Entry has been deleted',
    );
    $errmsg = 'unknown';
    foreach ($zipFileFunctionsErrors as $constName => $errorMessage) {
      if (defined($constName) and constant($constName) === $errno) {
        $return = 'Zip File Function error: '.$errorMessage;
      }
    }
    return $return;
  }    
}
?>