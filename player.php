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
	This is the main plyer file. It simply wraps one of your playlists for viewing.
*/
$playlist = isset($_GET['playlist']) ? $_GET['playlist'] : 'playlists/playlist1.xml'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Media Player</title>
</head>

<body>
<div style="width:313px; height:441px; background-image:url(tpl/SS_media_bg.jpg); float:left; margin-right:10px;">
<div style="float:left; padding-left:80px; color:#FFF; font-family:'Trebuchet MS', Arial, Helvetica, sans-serif; font-size:18px; font-weight:bold; padding-top:3px;">Video Viewer</div>
<script type='text/javascript' src='mediaplayer/swfobject.js'></script>

<div style="padding-top:35px; padding-left:10px;">

 <div name="mediaspace" id="mediaspace">Video Player</div>

  <script type='text/javascript'>
  var s1 = new SWFObject('mediaplayer/player.swf','ply','294','400','9','#');
  s1.addParam('allowfullscreen','true');
  s1.addParam('allowscriptaccess','always');
  s1.addParam('wmode','opaque');
  s1.addParam('flashvars','file=<?php echo $playlist; ?>&playlist=bottom&repeat=list?rand=<?php echo rand(300, 800); ?>');
  s1.write('mediaspace');
</script>
</div>
</div>
</body>
</html>
