<?php
/**
    Media Manager For JW Player
	Copyright 2009, 2010 Matthew Grdinic [ nicSoft ] - http://www.formboss.net/
	
	This file is part of Media Manager For JW Player.

    Media Manager For JW Player is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Media Manager For JW Player is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Media Manager For JW Player.  If not, see <http://www.gnu.org/licenses/>.
	
	About This File
	This is the main program. It reads your playlists and creates a graphical
	representation of them for you to edit.
	
*/

//echo dirname($_SERVER["SCRIPT_NAME"]) . '/'; die();
//echo phpinfo();

// vars
define('LOG_LEVEL', 'STANDARD'); // VERBOSE | STANDARD | NONE
define('LOG_TIME_FORMAT', 'M j G:i:s');

// users [name=password]
$users_array = array('test=test');

// session
session_start();

require 'simplepie/simplepie.inc';
require 'MediaLogger.php';

// create new logger instance
$logger = new MediaLogger();
echo $logger->get_errors();

/**
 * LOGIN AREA
 */
if(isset($_POST['login'])){
	// check credentials against user array
	$user = isset($_POST['user']) ? $_POST['user'] : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';
	$entry = $user . '=' . $password;
	$pass = 0;
	foreach($users_array as $value){
		if($value == $entry){
			$pass = 1;
			$_SESSION['logged_in'] = true;
			$_SESSION['user'] = $user;
			$logger->add_standard("\n\n");
			$logger->add_standard("----------------- NEW SESSION -----------------");
			$logger->add_standard("User: {$user}");
		}
	}
	if($pass == 0){
		$login_message = 'Please try again';	
	}
}

if(isset($_POST['logout'])){
	$logger->add_standard("User: {$_SESSION['user']}");
	$logger->add_standard("----------------- END SESSION -----------------");
	// unset vars
	unset($_SESSION['logged_in']);
	unset($_SESSION['user']);
}

// create default rss variable
$rss = '';

// global vars
$update_message = '';
	
$playlist = isset($_POST['playlist']) ? $_POST['playlist'] : 'playlists/playlist1.xml';

//echo $playlist ;

// read in our xml file, only if not set
if(file_exists($playlist) && !isset($_POST['position'])){
	// read xml
	$rss = new SimplePie($playlist);
	$logger->add_verbose("Read Playlist: {$playlist}");
	
	//print_r($data);
}


/**
 * Add new playlist item
 */
if(isset($_POST['add_item'])){
	$xml = file_get_contents($playlist);
	
	// grab the content for the corresponding <item> xml tag
	$location_array = array();
	$count = substr_count($xml, '<item>');
	$offset = 0;
	for($i = 0; $i < $count; $i++){
		// get item position start and end
		$location_array[$i]['start'] = strpos($xml, '<item>', $offset);
		$location_array[$i]['stop'] = strpos($xml, '</item>', $offset) + 7;
		$offset = $location_array[$i]['stop'];
	}
	
	// add proper <item> element at the end of the list
	$elt = <<<EOT
\n\n\t\t<item>
\t\t\t<title></title>
\t\t\t<link></link>
\t\t\t<description></description>
\t\t\t<media:credit role="author"></media:credit>
\t\t\t<media:content url="" type="" duration="" />
\t\t\t<media:thumbnail url="" />
\t\t</item>
EOT;

	// add proper <item> element at the end of the list
	$elt_new = <<<EOT
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
	<channel>
		<title>Playlist</title>
		<link>{$_SERVER['SERVER_NAME']}</link>
		<item>
			<title></title>
			<link></link>
			<description></description>
			<media:credit role="author"></media:credit>
			<media:content url="" type="" duration="" />
			<media:thumbnail url="" />
		</item>
	</channel>
</rss>
EOT;
	
	// cannot add more than 1 blank item at a time
	if(substr_count($xml, $elt) == 1){
		$update_message .= "You cannot add more than 1 blank item at a time.";
		$logger->add_verbose("Tried to add more than 1 new element");
	} else {
	
		// add a new playlist item - update this to add new items to that start of the field list, not end
		if($count != 0){ // we have existing items
			//$replace = substr($xml, $location_array[0]['start'], $location_array[0]['stop'] - $location_array[0]['start']);
			$xml = preg_replace('/<item>/', $elt . "\n" . '<item>', $xml, 1);
		} else {
			$xml = $elt_new;
		}
		
		// update message
		$update_message .= "Position {$count} has been added";
		
		// log
		$logger->add_standard("[Add]\t\t{$count} \t {$playlist}");
		
		//echo '<pre>' . $xml . '</pre>';
		file_put_contents($playlist, $xml);
		
		// reload rss
		$rss = new SimplePie($playlist);
	}
		
}

/**
 * Update / Delete item
 */
if(isset($_POST['position'])){
	
	$error = 'pass';
	
	if(isset($_POST['delete_item']) && $_POST['delete_item'] == '1'){
		// get the position
		$position = $_POST['position'];
		$xml = file_get_contents($playlist);
		
		// grab the content for the corresponding <item> xml tag
		$location_array = array();
		$count = substr_count($xml, '<item>');
		$offset = 0;
		for($i = 0; $i < $count; $i++){
			// get item position start and end
			$location_array[$i]['start'] = strpos($xml, '<item>', $offset);
			$location_array[$i]['stop'] = strpos($xml, '</item>', $offset) + 7;
			$offset = $location_array[$i]['stop'];
		}
		
		
		// remove the old item
		$replace = substr($xml, $location_array[$position]['start'], $location_array[$position]['stop'] - $location_array[$position]['start']);
		$xml = str_replace($replace, '', $xml);
		
		if(isset($_POST['delete_media'])){
			$media_url = isset($_POST['media_url']) ? $_POST['media_url'] : ''; $media_file = explode('/', $media_url);
			$thumb_url = isset($_POST['thumb_url']) ? $_POST['thumb_url'] : ''; $thumb_file = explode('/', $thumb_url);
			$removed_media = array_pop($media_file);
			$removed_thumb = array_pop($thumb_file);
			if(@unlink('./source/' . $removed_media)){
				$update_message .= "Position {$position} media file(s) have been removed.<br/>";
			}
			if(@unlink('./source/' . $removed_thumb)){
				$update_message .= "Position {$position} thumbnail file has been removed.<br/>";
			}
			//die();
			
			// log
			$logger->add_standard("[X Media]\t{$position} \t {$playlist} | {$removed_media} | {$removed_thumb}");
			
		}
		
		// update message
		$update_message .= "Position {$position} has been removed.<br/>";
		
		// log
		$logger->add_standard("[Remove]\t\t{$position} \t {$playlist}");
		
		// create new xml file
		file_put_contents($playlist, $xml);
		
		// reload rss
		$rss = new SimplePie($playlist);
		
		
	} else {
	
		// check input
		$title = isset($_POST['title']) ? $_POST['title'] : '';
		$link = isset($_POST['link']) ? $_POST['link'] : '';
		$media_credit = isset($_POST['media_credit']) ? $_POST['media_credit'] : '';
		$media_url = isset($_POST['media_url']) ? $_POST['media_url'] : '';
		$thumb_url = isset($_POST['thumb_url']) ? $_POST['thumb_url'] : '';
		$description = isset($_POST['description']) ? $_POST['description'] : '';
		$media_type = isset($_POST['media_type']) ? $_POST['media_type'] : '';
		$media_duration = isset($_POST['media_duration']) ? $_POST['media_duration'] : '';
		
		if($title != '' && $link != '' && $media_credit != '' && $description != '' && $media_type != '' && $media_duration != ''){
	
			// get the position
			$position = $_POST['position'];
			$xml = file_get_contents($playlist);
			
			// grab the content for the corresponding <item> xml tag
			$location_array = array();
			$count = substr_count($xml, '<item>');
			$offset = 0;
			for($i = 0; $i < $count; $i++){
				// get item position start and end
				$location_array[$i]['start'] = strpos($xml, '<item>', $offset);
				$location_array[$i]['stop'] = strpos($xml, '</item>', $offset) + 7;
				$offset = $location_array[$i]['stop'];
			}
			
			// handle file uploads
			if(isset($_FILES['upload_media']) && $_FILES['upload_media']['size'] != 0){
				$uid = $_FILES['upload_media']['tmp_name'];
				$destination = dirname($_SERVER["SCRIPT_NAME"]);
				$filename = $_FILES['upload_media']['name'];
				copy($uid, './source/' . $filename);
				// set new media link
				$port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
				$media_url = 'http://' . $_SERVER['SERVER_NAME'] . $port . dirname($_SERVER["SCRIPT_NAME"]) . '/source/' . $filename;
				$update_message .= 'Media File Added.<br/>';
				
				// log
				$logger->add_standard("[Add Media]\t{$position} \t {$playlist} | {$filename}");
			}
			
			// handle thumbnail uploads
			if(isset($_FILES['upload_thumb']) && $_FILES['upload_thumb']['size'] != 0){
				$uid = $_FILES['upload_thumb']['tmp_name'];
				$destination = dirname($_SERVER["SCRIPT_NAME"]);
				$filename = $_FILES['upload_thumb']['name'];
				copy($uid, './source/' . $filename);
				// set new media link
				$port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
				$thumb_url = 'http://' . $_SERVER['SERVER_NAME'] . $port . dirname($_SERVER["SCRIPT_NAME"]) . '/source/' . $filename;
				$update_message .= 'Media Thumb Added.<br/>';
				
				// log
				$logger->add_standard("[Add Thumb]\t{$position} \t {$playlist} | {$filename}");
			}
		
		// replace proper <item> element for the given position
		$elt = <<<EOT
<item>
\t\t\t<title>{$title}</title>
\t\t\t<link>{$link}</link>
\t\t\t<description>{$description}</description>
\t\t\t<media:credit role="author">{$media_credit}</media:credit>
\t\t\t<media:content url="{$media_url}" type="{$media_type}" duration="{$media_duration}" />
\t\t\t<media:thumbnail url="{$thumb_url}" />
\t\t</item>
EOT;
	
			// replace the old record
			$replace = substr($xml, $location_array[$position]['start'], $location_array[$position]['stop'] - $location_array[$position]['start']);
			$xml = str_replace($replace, $elt, $xml);
			
			// update message
			$update_message .= "Position {$position} has been updated";
			
			// log
			$logger->add_standard("[Update]\t\t{$position} \t {$playlist}");
			
			//echo '<pre>' . $xml . '</pre>';
			file_put_contents($playlist, $xml);
			
			// reload rss
			$rss = new SimplePie($playlist);
			
		} else {
			$error = 'Cannot have blank fields. Please fill out the form for each media item completely.';
			$rss = new SimplePie($playlist);
		}
		
	} // if delete
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Manage Media</title>
<style type="text/css">
body { font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#666; font-weight:bold; }
.body-required { font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#B0242B; font-weight:bold; }
.input-normal { border:1px #999 solid; width:300px; font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#333; }
.input-upload { border:1px #999 solid; width:180px; font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#333; }
.input-10 { border:1px #999 solid; width:100px; font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#333; }
.input-5 { border:1px #999 solid; width:50px; font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#333; }
.textarea-normal { border:1px #999 solid; width:300px; font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#333; }
.button {  border:1px #999 solid; width:100px; background-color:#366; font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#fff; }
.button-logout {  border:1px #999 solid; width:100px; background-color:#B0242B; font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#fff; }
.headline { font-weight:bold; color:#366; font-size:13px; }
img { border:1px #999 solid; }
</style>

<script type='text/javascript'>
function update_button(value, action, key){
	// change button color
	switch(action){
		case 'delete' :
			if(value){
				document.getElementById('button_'+key).style.backgroundColor = '#B0242B';
				document.getElementById('button_'+key).value = 'Delete Item';
			} else {
				document.getElementById('button_'+key).style.backgroundColor = '#366';
				document.getElementById('button_'+key).value = 'Update Position ' + key;
			}
			break;
	}
}

function preview(playlist){
	window.open("player.php?playlist="+playlist, "Media", "status=1,toolbar=1,height=550,width=400");	
}
</script>
</head>

<body>

<?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == 'true') { ?>

<table width="1112" height="20" border="0" cellspacing="5" bgcolor="#efefef" style="border-bottom:1px #aaa dashed; border-top:1px #aaa dashed;">
<tr>
  <td width="50">&nbsp;</td>
	<td width="336">
        Select a Playlist for: <?php echo $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']; ?>
          <form id="select_playlist" name="select_playlist" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <label>  
              <select name="playlist" class="input-10" id="playlist" onchange="document.forms['select_playlist'].submit();">
                <option>-- Select --</option>
                <option value="playlists/playlist1.xml" <?php if($playlist == 'playlists/playlist1.xml') { echo 'selected="selected"'; } ?>>Playlist 1</option>
                <option value="playlists/playlist2.xml" <?php if($playlist == 'playlists/playlist2.xml') { echo 'selected="selected"'; } ?>>Playlist 2</option>
                <option value="playlists/playlist3.xml" <?php if($playlist == 'playlists/playlist3.xml') { echo 'selected="selected"'; } ?>>Playlist 3</option>
                <option value="playlists/playlist4.xml" <?php if($playlist == 'playlists/playlist4.xml') { echo 'selected="selected"'; } ?>>Playlist 4</option>
              </select>
            </label>
            
            <input name="preview_playlist" type="button" class="button" id="preview_playlist" 
            	value="Preview Playlist" style="cursor:pointer;" 
                onclick="preview('<?php echo $playlist;?>');" />
            &nbsp;
                <input name="add_item" type="submit" class="button" id="add_item"  value="Add Playlist Item" style="cursor:pointer;" />
            
            </form>
            
            <p>iFrame Media Link:&nbsp;
            
            
<input type="text" class="input-10" style="width:480px; height:18px;" value="&lt;iframe src=&quot;<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER["SCRIPT_NAME"]) . '/player.php?playlist=' . $playlist;?>&quot; frameborder=&quot;0&quot; scrolling=&quot;no&quot; width=&quot;460&quot; height=&quot;500&quot;&gt;&lt;/iframe&gt;" onClick="this.select()" /></p>


      </td>
	<td width="536"> 
		<?php if(isset($error) && $error != 'pass') { echo $error; } ?>
        <?php if(isset($update_message) && $update_message != '') { echo $update_message; } ?>
    </td>
	<td width="157">
    	<form id="logout_form" name="logout_form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    		<input name="logout" type="submit" class="button-logout" id="logout"  value="Logout" style="cursor:pointer;" />
        </form>
    </td>
     </tr>
</table>
  
  <?php
  if(is_object($rss)){
	  foreach($rss->get_items() as $key=>$item){ 
	  $enclosure = $item->get_enclosure();
	  $credit = $enclosure->get_credit();
	  // print_r($enclosure);
  ?>
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" name="update_playlist" id="update_playlist_<?php echo $key; ?>">
  	
  	<input name="MAX_FILE_SIZE" type="hidden" value="31457280" /><!-- 30 mb -->
    <input name="position" type="hidden" value="<?php echo $key; ?>" />
    <input name="playlist" type="hidden" value="<?php echo $playlist; ?>" />

<div style="border-bottom:1px #ddd solid; margin-top:10px; margin-bottom:20px;">
  
  <table width="1112" height="210" border="0" cellspacing="5">
    <tr>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left"><span class="headline"><?php echo $item->get_title(); ?></span></td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td style="text-align: right">&nbsp;</td>
      <td class="body-required" style="text-align: left">Red Text = Required Field</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td width="131" class="body-required" style="text-align: right">Item Title:</td>
      <td width="331" style="text-align: left"><label>
        <input name="title" type="text" class="input-normal" id="title" value="<?php echo $item->get_title(); ?>" />
        </label></td>
      <td width="122" class="body-required" style="text-align: left">Description: </td>
      <td width="156" style="text-align: right">&nbsp;</td>
      <td width="44" style="text-align: left">&nbsp;</td>
      <td width="281" style="text-align: left">Thumbnail Preview</td>
    </tr>
    <tr>
      <td style="text-align: right">Item Link:</td>
      <td style="text-align: left"><input name="link" type="text" class="input-normal" id="link" value="<?php echo $item->get_link(); ?>" /></td>
      <td colspan="3" rowspan="3" valign="top" style="text-align: left">
      	<textarea name="description" rows="5" class="textarea-normal" id="description"><?php echo $item->get_description(); ?></textarea>
      </td>
      <td rowspan="7" valign="top" style="text-align: left"><img src="<?php echo $enclosure->get_thumbnail(); ?>" alt="" name="" width="254" height="133" /></td>
    </tr>
    <tr>
      <td class="body-required" style="text-align: right">Media Credit:</td>
      <td style="text-align: left"><input name="media_credit" type="text" class="input-normal" id="media_credit" value="<?php echo $credit->get_name(); ?>" /></td>
      </tr>
    <tr>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      </tr>
    <tr>
      <td class="body-required" style="text-align: right">Media URL:</td>
      <td style="text-align: left"><input name="media_url" type="text" class="input-normal" id="media_url" value="<?php echo $enclosure->get_link(); ?>" /></td>
      <td style="text-align: left"><span style="text-align: right">Media Type:</span></td>
      <td style="text-align: left"><span class="body-required">Media Duration:</span></td>
      <td style="text-align: left">&nbsp;</td>
      </tr>
    <tr>
      <td style="text-align: right">Upload New Media</td>
      <td style="text-align: left"><label>
        <input name="upload_media" type="file" class="input-upload" id="upload_media" />
      </label></td>
      <td style="text-align: left"><select name="media_type" class="input-10" id="media_type">
        <option>-- Select --</option>
        <option value="video/x-flv" selected="selected" <?php if($enclosure->get_type() == 'video/x-flv') { echo 'selected="selected"'; } ?> >Video</option>
        <option value="image/jpg" <?php if($enclosure->get_type() == 'image/jpg') { echo 'selected="selected"'; } ?> >Image</option>
      </select></td>
      <td style="text-align: left"><span style="text-align: left">
        <input name="media_duration" type="text" class="input-5" id="media_duration" value="<?php echo $enclosure->get_duration(); ?>" />
      (x:xx)</span></td>
      <td style="text-align: left">&nbsp;</td>
      </tr>
    <tr>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">(For images + Video)</td>
      <td style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td class="body-required" style="text-align: right">Thumbnail URL: </td>
      <td style="text-align: left"><input name="thumb_url" type="text" class="input-normal" id="thumb_url" value="<?php echo $enclosure->get_thumbnail(); ?>" /></td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      </tr>
    <tr>
      <td style="text-align: right">Upload New Thumb</td>
      <td style="text-align: left"><input name="upload_thumb" type="file" class="input-upload" id="upload_thumb" /></td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td valign="top" style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td style="text-align: right">Delete Item</td>
      <td style="text-align: left"><input type="checkbox" name="delete_item" id="delete_item" value="1" onclick="update_button(this.checked, 'delete', <?php echo $key; ?>);" /></td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td valign="top" style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td style="text-align: right"><span style="text-align: left">Delete Media</span></td>
      <td style="text-align: left">
        <input type="checkbox" name="delete_media" id="delete_media" value="1" /></td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td valign="top" style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td valign="top" style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left"><input name="button_<?php echo $key; ?>" type="submit" class="button" id="button_<?php echo $key; ?>" value="Update Position" style="cursor:pointer;" /></td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td valign="top" style="text-align: left">&nbsp;</td>
    </tr>
    <tr>
      <td style="text-align: right">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td style="text-align: left">&nbsp;</td>
      <td valign="top" style="text-align: left">&nbsp;</td>
    </tr>
  </table>
</div>
  </form>
  <?php 
  } 
  } // if rss object was created ( we have a playlist to show
} else { // is loggged in ?>

<div style="width:1000px; height:100px;">
    <div style="width:400px; margin-left:auto; margin-right:auto;">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="login">
        <input name="login" type="hidden" value="login" />
          <table width="396" border="0" cellspacing="5" style="border:1px #efefef solid;">
            <tr>
              <td style="text-align: right">&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td width="140" style="text-align: right">&nbsp;</td>
              <td width="235">Welcome to Media Manager</td>
            </tr>
            <tr>
              <td style="text-align: right">User Name</td>
              <td><label>
                <input name="user" type="text" class="input-10" id="user" />
              </label></td>
            </tr>
            <tr>
              <td style="text-align: right">Password</td>
              <td><label>
                <input name="password" type="password" class="input-10" id="password" />
              </label></td>
            </tr>
            <tr>
              <td style="text-align: right">&nbsp;</td>
              <td><label>
                <input name="button" type="submit" class="button" id="button" value="Login" />
              </label></td>
            </tr>
            <tr>
              <td style="text-align: right">&nbsp;</td>
              <td><?php if(isset($login_message)) { echo $login_message; } ?></td>
            </tr>
          </table>
      </form>
    </div>
</div>

<?php 

} // login form

?>

</body>
</html>