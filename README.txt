Thank you for downloading Media Manager For JW Player!
---------------------------------------------------------

The purpose of this program is to provide a graphical front end to managing your
JWPlayer playlists for PHP based web servers.

USEAGE

1. DOWNLOAD JWPLAYER

First, download a copy of JWPlayer from:
http://www.longtailvideo.com/players/jw-flv-player/

Extract the contents of the folder to the mediaplayer folder. It is important 
to note that the JWPlayer download most likely came in a folder called mediaplayer.
Thus, you can overwrite this folder or place the contents of the mediaplayer.zip
into the existing mediaplayer folder.

You do not want to place the entire mediaplayer folder into the existing mediaplayer
folder as in: mediaplayer/mediaplayer. Only place the contents into mediaplayer.


2. SET THE USER ACCOUNT

Open index.php in a text editor and look for:

// users [name=password]
$users_array = array('test=test');

Which will be near the top. 

The part inside the array is the username / password combo.
By default it is set to test / test.

To change this you edit the array pair as in: 
User: matt
Password: formboss

Which would be:
// users [name=password]
$users_array = array(Ômatt=formboss');

Load the index.php in your web browser. 


3. SET FILE PERMISSIONS

a. Set manage_media.log to be writeable by your web server
b. Set the source/ directory to be writeable by the web server as well 
c. Set the playlists/ directory and all files within to be writeable by the web server.


4. ADD PLAYLIST ITEMS

With the playlist editor launched you will have a blank screen except for a few
buttons at the top. To add a playlist item click the 'Add Playlist Item' button. 

A playlist item will appear. Now fill in all fields in RED and press 'Update Position'.
