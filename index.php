<?
$httproto = getenv('PICT_NO_SSL') ? 'http' : 'https';

$URL = dirname($_SERVER['SCRIPT_NAME']).'/';
if ($URL == '//') $URL = '/';
$fullURL = $httproto.'://'.$_SERVER['HTTP_HOST'].$URL;

$showErrors = false;

$max_players = 88; // VARCHAR(255) for player order implies maximum of 88

$HTMLheaderCode='<!doctype html><html><head>
<meta charset=utf-8><title>PICT</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">';

$HTMLheaderCodeNoScale='<!doctype html><html><head>
<meta charset=utf-8><title>PICT</title>
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">';

//Potentially auto generate...
$wordLists = array("wordList.txt"=>"Pictionary", "nounsList.txt"=>"Tricky Nouns", "cah_whitecards.txt"=>"Cards Against Humanity");

$countdownList = array(
  "30 Seconds",
  "1 Minute",
  "2 Minutes",
  "Unlimited"
);
$countdownSec = array(
  30,
  60,
  120,
  -1
);
$gameModeList = array(
  "Normal",
  "Etch-a-sketch"
);

$bannedAgents = array(
  "WhatsApp",
  "facebookexternalhit"
);

$jsPollTime = getenv('JS_POLL_TIME') ?: 3000;

$DB_UTF8MB4_SUPPORT=false; // Whether or not to store strings as html-escaped in the db

if ($DB_UTF8MB4_SUPPORT){
  function entitiesOut($str){
    return htmlentities($str);
  }
  function entitiesIn($str){
    return $str;
  }
} else {
  function entitiesOut($str){
    return $str;
  }
  function entitiesIn($str){
    return mb_encode_numericentity(htmlentities($str),  array(0x10000, 0xFFFFFF, 0, 0xFFFFFFFF),  mb_internal_encoding(), true);
  }
}

$q = explode('?',explode($URL,$_SERVER['REQUEST_URI'])[1])[0];

require('_db.php');

/*
pict/join
pict/host
pict/join

if no login cookie
    show menu host game / join game
    host game :  generate game id, forward to enter-name page
    join game :  enter id, go to enter name page

url: pict/game/id123 --needed? already logged in, right?
visiting an unknown id - warn that you're starting a new game?


from here on, no other page urls, just poll urls
everything done by game state

-waiting for players, ready checkbox
game starts: setup, initial cues chosen

round 0: waiting for players
            if name field is blank, show name entry form
            else show player list, ready checkbox
round 1: js timer before start, longer for first picture
round 2: guess the cue for this picture
round 3: draw this cue
round 4: guess the cue for this picture
...
round 2n

ending: show story for your final guess, link to see other stories


maybe show stories on pict/archive/gameID


pict/test - play with interface



*/

$s = $_COOKIE['pict'];
$player = false;
$game = false;

if ($s && isSessionKey($s)) {
  $r=$db->query("SELECT * FROM pictPlayers WHERE SessionCookie='$s'");
  if ($r && $r->num_rows==1) {
    $player = $r->fetch_assoc();
    $r=$db->query("SELECT * FROM pict WHERE GameID='{$player['GameID']}'");
    if ($r && $r->num_rows==1) {
      $game = $r->fetch_assoc();
    }
  }
} else $s=false;



if (substr($q,0,7)=="archive") {
  require("_archive.php");
  die();
}


if (str_replace($bannedAgents, '', $_SERVER['HTTP_USER_AGENT']) !== $_SERVER['HTTP_USER_AGENT']) {
  $q="";
}


if (!$game) {

if ($_GET['poll']) die('{"reload":1}');



// Do ID check first so we can fall through to join screen if needed

if (preg_match('/^[0-9]+$/',$q) && $q>1 && $q<0xFFFFFFFF) { // Request to Join Game
  $r=$db->query("SELECT * FROM pict WHERE GameID='$q'");
  if ($r && $r->num_rows==1) {

    $game = $r->fetch_assoc();
    if ($game['Round']==0) {

      //This number will be inaccurate since disconnected players don't decrease it
      if ($game['NumPlayers'] > $max_players) die("Cannot join game: too many players! <a href=$fullURL>Home</a>");

      do {
        $s=generateSessionKey();
        $r=$db->query("SELECT * FROM pictPlayers WHERE SessionCookie='$s'");
      } while ($r && $r->num_rows>0);
      if (!$r) errorHandler(__LINE__);

      $playerNum = $game['NumPlayers']+1; // Recalculate later anyway, in case people leave during setup
      $r=$db->query("INSERT INTO pictPlayers (`SessionCookie`, `GameID`, `PlayerNum`, `Ready`, `Name`) VALUES ('$s', '$q', '$playerNum', '0', '');");
      if (!$r) errorHandler(__LINE__);

//Update NumPlayers and playorder in game row

      bake($s);

      header("Location: {$fullURL}game");
      die();

      // redirect to main url - /pict/game ?
    } else {
        //die('They started without you! That game is already in progress or finished. <a href='.$fullURL.'>Home</a>');
        $joinWarning = "They started without you! That game is already in progress or finished.";
        $q="join";
    }
  } else {
    //Invalid game
    $joinWarning = "Unknown game ID";
    $q="join";

/*
echo $HTMLheaderCode;
?>
</head><body>
Unknown game ID. <p>
<a href=<?=$URL ?>join>Join game</a><p>
<a href=<?=$URL ?>>Home</a><p>
</body></html><?
*/


  }

}





switch ($q) {

case "game": // perhaps from history
//  echo "No valid game data, cookie expired perhaps. Probably redirect back to home here";
//break;
case "quit":
  header("Location: {$fullURL}");
  die();
break;

case "": echo $HTMLheaderCode;
?>
<style>
body{text-align:center;background:#fc8; animation: dazzle 8s ease-in infinite;}
div{
  background:#fff;
  padding:20px;
  border-radius:15px;
  max-width:300px;
  margin:30px auto;
}
a:hover{color:red}
div a{background:#39b044;color:#eee;border-radius:10px;padding:10px;display:inline-block;font-family: sans-serif}
div a:hover{background:#eee;color:#39b044;}

h1 {letter-spacing:2px; animation: fadeIn  3s; font-family: sans-serif;}

@keyframes fadeIn{
  from  {text-shadow:0px 0px 5px #000; color:transparent; transform:scale(1.5)}
  90% {text-shadow:0px 0px 1px #000; color:transparent; transform:scale(1)}
  to {text-shadow:0px 0px 1px #000; color:black;transform:scale(1)}
}

@keyframes dazzle{
    0% {background:#fc8}
   33% {background:#8fc}
   66% {background:#cf8}
  100% {background:#fc8}
}
</style>
</head><body>

<div>
<h1>PICT</h1>
<a href=host>Host game</a><p>
<a href=join>Join game</a><p>
</div>
<a href=https://mitxela.com/>mitxela.com</a>

</body></html>
<?
break;

case "host":

  // create fresh game row, id
  $tries = 1;
  do {
    $GameID = mt_rand(100,998 + $tries);
    $tries*=10;
    if ($tries>0xFFFFFFFF-998) $tries = 0xFFFFFFFF-998;
    $r=$db->query("SELECT * FROM pict WHERE GameID='$GameID'");
  } while ($r && $r->num_rows>0);
  if (!$r) die($db->error . __LINE__);

  $r=$db->query("INSERT INTO pict (`GameID`, `NumPlayers`, `Round`, `PlayOrder`) VALUES ('$GameID', '1', '0', '1');");
  if (!$r) errorHandler(__LINE__);

  // Create new player
  do {
    $s=generateSessionKey();
    $r=$db->query("SELECT * FROM pictPlayers WHERE SessionCookie='$s'");
  } while ($r && $r->num_rows>0);
  if (!$r) errorHandler(__LINE__);

  $r=$db->query("INSERT INTO pictPlayers (`SessionCookie`, `GameID`, `PlayerNum`, `Ready`, `Name`) VALUES ('$s', '$GameID', '1', '0', '');");
  if (!$r) errorHandler(__LINE__);

  bake($s);

  header("Location: {$fullURL}game");
  die();

break;

case "join":

echo $HTMLheaderCodeNoScale;
?>
<style>
body{text-align:center;background:#8cf;}
form {
 background:#fff;
 padding:20px;
  border-radius:15px;
  max-width:300px;
  margin:30px auto;
}
input{
  background:#e8e8e8;
  box-sizing: border-box;
  border-radius:10px;
  padding:5px;
  margin:8px;
  width:200px;
  border:0;
  text-align:center;
}
input[type=submit]{
  background:#8d8;
  cursor:pointer;
}
input[type=text]:focus{box-shadow:0 0 5px orange;background:#fff;}
</style>


</head><body>

<form onsubmit='joinGame();return false;'>
<? if ($joinWarning) echo "<p style='color:red'>$joinWarning</p>";?>
Enter the ID of the game you want to join:<p>
<input type=text autofocus>
<input type=submit value=Go>
</form>
<script>
function joinGame(){
  var id=document.querySelector('input[type=text]').value;
  if (parseInt(id)==id && id>99)
    window.location='<?=$URL ?>'+id;
  else alert('Invalid game ID');
}
</script>
</body></html>
<?
break;


case "test-def":

  $prompt="Test mode.";
  $straightToPoll=false;
  $countdownSec=array();
  $game=array('Countdown'=>0);
  $countdownSec[$game['Countdown']]=-1;
  $startCountdown=5;
  require('_draw_def.php');

break;
case "test-etch":

  $prompt="Test mode.";
  $straightToPoll=false;
  $countdownSec=array();
  $game=array('Countdown'=>0);
  $countdownSec[$game['Countdown']]=-1;
  $startCountdown=5;
  require('_draw_etch.php');

break;


default: e404();
}









/////////////////////////////////////////
} else { // $game set
/////////////////////////////////////////



if ($q=="quit"){
  //edit polltime to explicitly remove them from the game? --nah

  bake("");

  // if they were trying to join another game, redirect
  if (isset($_SERVER["HTTP_REFERER"])) {
    $ref = explode($fullURL, $_SERVER["HTTP_REFERER"]);
    if ($ref[1] && preg_match('/^[0-9]+$/',$ref[1])) {
      $ref=$ref[1];
      //$r=$db->query("SELECT * FROM pict WHERE GameID='$ref' && Round='0'");
      //if ($r && $r->num_rows>0) {
        header("Location: {$fullURL}{$ref}");
        die();
      //}
    }
  }
  header("Location: {$fullURL}");
  die();
}

if ($q=="new"){
// if NextGame == 0,
  if ($game['NextGame']==0) {

    // Same as case "host":
    // create fresh game row, id
    $tries = 1;
    do {
      $GameID = mt_rand(100,998 + $tries);
      $tries*=10;
      if ($tries>0xFFFFFFFF-998) $tries = 0xFFFFFFFF-998;
      $r=$db->query("SELECT * FROM pict WHERE GameID='$GameID'");
    } while ($r && $r->num_rows>0);
    if (!$r) errorHandler(__LINE__);

    $r=$db->query("INSERT INTO pict (`GameID`, `NumPlayers`, `Round`, `PlayOrder`) VALUES ('$GameID', '1', '0', '1');");
    if (!$r) errorHandler(__LINE__);

    // set 'NextGame' for all the others
    $r=$db->query("UPDATE `pict` SET `NextGame`='$GameID' WHERE `GameID` = '{$game['GameID']}';");
    if (!$r) errorHandler(__LINE__);

   //Set our new GameID and ready = 0
    $r=$db->query("UPDATE `pictPlayers` SET `GameID` = '$GameID',`Ready`='0' WHERE `SessionCookie` = '$s';");
    if (!$r) errorHandler(__LINE__);

  } else {

    // Check that the new game has not started yet.
    $r=$db->query("SELECT * FROM `pict` WHERE `GameID`='{$game['NextGame']}' && `Round`='0'");
    if (!$r) errorHandler(__LINE__);

    if ($r->num_rows!=0) {
      $r=$db->query("UPDATE `pictPlayers` SET `GameID` = '{$game['NextGame']}',`Ready`='0' WHERE `SessionCookie` = '$s';");
      if (!$r) errorHandler(__LINE__);
    } else {
      // nothing better to do, might as well kick them back to the homepage
      bake("");
    }

  }

  header("Location: {$fullURL}game");
  die();
}

if ($q=="end") {
  if ($game['Round']>0 && $game['Round'] <= 1+$game['NumPlayers']) {
    $db->query("UPDATE `pict` SET `Round`='".(2+$game['NumPlayers'])."' WHERE `GameID`='{$game['GameID']}';")
      or errorHandler(__LINE__);
  }
  header("Location: {$fullURL}game");
  die();
}

if ($q!="game") { //TODO: redirect a join game link


//if url is accessing a valid game ID, and that game id is accepting players, and our current game is over, join the new game keeping our name

echo $HTMLheaderCode;
?><style>
body{text-align:center;background:#8cf;}
div{ background:#fff; padding:20px;border-radius:15px;max-width:300px;margin:30px auto;}
</style>
</head><body>
<div>
You are currently partaking in game [<?=$game['GameID']?>]. <p>
<a href=<?=$URL ?>game>Resume game</a><p>
<a href=<?=$URL ?>quit>Exit</a><p>
</div>
</body></html><?
die();

}






if ($player['Name']=="") {

if (isset($_POST['username'])) {

  if (!preg_match('/[!-~]/',$_POST['username'])) $userError="Please use at least one printable ascii character!";
  else {
    $username= escape(entitiesIn($_POST['username']));

    //check name not in use
    $r=$db->query("SELECT * FROM pictPlayers WHERE `GameID`='{$game['GameID']}' AND `Name`='$username'");
    if (!$r) errorHandler(__LINE__);
    if ($r->num_rows ==0) {
      $r=$db->query("UPDATE `pictPlayers` SET `Name` = '$username' WHERE `SessionCookie` = '$s';");
      if (!$r) errorHandler(__LINE__);
      $player['Name'] = entitiesIn($_POST['username']);
    } else $userError = "That name is already in use!";
  }
  // Fall through to choose name screen (dies) or on to round==0
}

if (!isset($_POST['username']) || $userError ) {

echo $HTMLheaderCodeNoScale;
?><style>
body{text-align:center;background:#8cf;}
form {
 background:#fff;
 padding:20px;
  border-radius:15px;
  max-width:300px;
  margin:30px auto;
}
input{
  background:#e8e8e8;
  box-sizing: border-box;
  border-radius:10px;
  padding:5px;
  margin:8px;
  width:200px;
  border:0;
  text-align:center;
}
input[type=submit]{
  background:#8d8;
  cursor:pointer;
}
input[type=text]:focus{box-shadow:0 0 5px orange;background:#fff;}
h3{font-family:sans-serif;}
</style>
</head><body>
<form method=post>
<h3>Joining game <?=$game['GameID']?></h3>
Enter your name: <input name=username type=text autofocus><input type=submit value=Go>
</form><p>
<?=$userError ?>
</body></html><?
  die();
}

} //end if name=""



if ($game['Round'] ==0) {


// if $_GET[poll], respond with player list, or command to reload, else...
// update ready state
// game?ready=0&poll=1539873405


if ($_GET['poll']) {

  //update ready line in players table
  if (!isset($_GET['ready']) || !($_GET['ready']==='0' || $_GET['ready']==='1')) die();

  /*// Prune ourselves if needed
  if (time() - strtotime($player['pollTime']) > 10) {
    bake("");
    $r = $db->query("DELETE FROM `pictPlayers` WHERE `SessionCookie` = '$s'");
    die('{"reload":1}');
  }*/

  // only update if needed (might have just entered name)
  if (time() - strtotime($player['pollTime']) > 1){
    $r = $db->query("UPDATE `pictPlayers` SET `Ready` = '{$_GET['ready']}', `pollTime`=CURRENT_TIMESTAMP WHERE `SessionCookie` = '$s';");
    if (!$r || $db->affected_rows==0) { //should always be 1 affected since we're updating poll time
      // possibly resumed connection, player pruned
      die('{"reload":1}');
    }
  }
  //Prune other players
  $r = $db->query("DELETE FROM `pictPlayers` WHERE `GameID`='{$game['GameID']}' AND UNIX_TIMESTAMP()-UNIX_TIMESTAMP(pollTime) > 30");
  if (!$r) errorHandler(__LINE__);


  if (isset($_GET['wordlist']) && preg_match('/^[0-9]+$/',$_GET['wordlist']) &&$_GET['wordlist']>=0 && $_GET['wordlist']<count($wordLists)) {
    $game['WordList'] = $_GET['wordlist'];
    $r=$db->query("UPDATE `pict` SET `WordList`='{$game['WordList']}' WHERE `GameID`='{$game['GameID']}'");
    if (!$r) errorHandler(__LINE__);
  }
  if (isset($_GET['countdown']) && preg_match('/^[0-9]+$/',$_GET['countdown']) &&$_GET['countdown']>=0 && $_GET['countdown']<count($countdownList)) {
    $game['Countdown'] = $_GET['countdown'];
    $r=$db->query("UPDATE `pict` SET `Countdown`='{$game['Countdown']}' WHERE `GameID`='{$game['GameID']}'");
    if (!$r) errorHandler(__LINE__);
  }
  if (isset($_GET['gamemode']) && preg_match('/^[0-9]+$/',$_GET['gamemode']) &&$_GET['gamemode']>=0 && $_GET['gamemode']<count($gameModeList)) {
    $game['GameMode'] = $_GET['gamemode'];
    $r=$db->query("UPDATE `pict` SET `GameMode`='{$game['GameMode']}' WHERE `GameID`='{$game['GameID']}'");
    if (!$r) errorHandler(__LINE__);
  }

  // fetch all players
  // need sessioncookie as it's the primary key
  $r = $db->query("SELECT `SessionCookie`,`Name`,`Ready` FROM `pictPlayers` WHERE `GameID`='{$game['GameID']}'");
  if (!$r) errorHandler(__LINE__);

  // if everyone ready, move to next phase

  $allP=array();
  $ready = 1;
  while ($row = $r->fetch_assoc()) {
    $row['Name'] = entitiesOut($row['Name']);
    if ($row['Ready']=='0') $ready=0;
    $allP[] = $row;
  }
  if (count($allP)<2) $ready=0; //Need at least 2 players

  // output player list
  if (!$ready) {
    foreach ($allP as $k => &$v){ unset($v['SessionCookie']); }
    die(json_encode(array("playerList"=>$allP,"countdown"=>$game['Countdown'],"gamemode"=>$game['GameMode'],"wordlist"=>$game['WordList'])));
  } else {
    //re-sort player numbers, number of players, play order
    $numPlayers = count($allP);
    $playOrder=array();
    $queryV=array();
    for ($i=1;$i<=$numPlayers;$i++) {
      $playOrder[]=$i;
      $queryV[]="('{$allP[$i-1]['SessionCookie']}',$i,'')";
    }

    $r=$db->query("INSERT INTO pictPlayers (SessionCookie,PlayerNum, Name) VALUES ".implode(',',$queryV)."ON DUPLICATE KEY UPDATE PlayerNum=VALUES(PlayerNum);");
    if (!$r) errorHandler(__LINE__);

    shuffle($playOrder);

    $r=$db->query("UPDATE `pict` SET `NumPlayers` = '$numPlayers', `PlayOrder`='".implode(',',$playOrder)."', `Round`=1 WHERE `GameID` = {$game['GameID']};");
    if (!$r) errorHandler(__LINE__);
    // set round=1, this should cause all players to reload


    // choose initial prompts and populate as round 0 description
    $r=$db->query("SELECT `Description` FROM `pictDesc` WHERE `Round`=0");
    if (!$r) errorHandler(__LINE__);
    $avoid = array();
    while ($row=$r->fetch_row()) $avoid[]=$row[0];

    $wList = chooseWords($numPlayers,$avoid);
    $wQuery=array();

    for ($i=1;$i<=count($wList);$i++) {$wQuery[]="('{$game['GameID']}','0','$i','".escape(entitiesIn($wList[$i-1]))."')";}

    $r=$db->query("INSERT INTO `pictDesc` (`GameID`,`Round`,`Artist`,`Description`) VALUES ".implode(',',$wQuery));
    if (!$r) errorHandler(__LINE__);


    die('{"reload":1}');
  }

  die();
}



echo $HTMLheaderCode;
?>
<style>
body{text-align:center;}
table,td,th{border-collapse:collapse;padding:3px;margin:auto;}
tr:nth-child(even){background:#f8f8f8}
th,td{text-align:left}
td:nth-child(2){text-align:center}
td:nth-child(1){width:100%}
table{max-width:500px}
h3{font-family:sans-serif}
fieldset{max-width:500px;margin:10px auto;box-sizing: border-box;border-radius:10px;}
select{width:50%;float:right;}
div{background:#fec;max-width:500px;margin:10px auto; padding:10px; border-radius:10px;box-sizing: border-box;}
#qrcode img{margin:auto}
</style>
</head><body>

Waiting for players.<p>
<h3>Game ID: <?=$game['GameID'] ?></h3>

<p id=qrcode></p>
<script src=QRCode.js></script>
<script>
new QRCode(document.getElementById("qrcode"), {text:"<?=$fullURL.$game['GameID'] ?>", correctLevel:QRCode.CorrectLevel.L});
</script>

<div>
Send this link to everyone you want to join: <a href=<?=$fullURL.$game['GameID'] ?>><?= $fullURL.$game['GameID'] ?></a>
</div><p>


<table id=playerList></table>


<fieldset>
<legend>Options</legend>
Countdown:
<select id=countdown onchange='request(this);'><?
  $i=0; foreach ($countdownList as $v) echo "<option value=".($i++).">$v</option>";
?></select>

<p>Game mode:
<select id=gamemode onchange='request(this);'><?
  $i=0; foreach ($gameModeList as $v) echo "<option value=".($i++).">$v</option>";
?></select>

<p>Word List:
<select id=wordlist onchange='request(this);'><?
  $i=0; foreach ($wordLists as $v) echo "<option value=".($i++).">$v</option>";
?></select>

</fieldset>
<p>
<label>Ready: <input type=checkbox></label>
<script>

function ajax(url, callback){
  var r = new XMLHttpRequest();
  r.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200)
      callback(JSON.parse(this.responseText));
  };
  r.open("GET", url, true);
  r.send();
}

function poll(){

  var ready = document.querySelector('input[type=checkbox]').checked ? 1:0;
  var t = Math.round(new Date().getTime()/1000);

  var op="";
  for (var i in options) op+="&"+i+"="+options[i];

  ajax('<?=$fullURL ?>game?ready='+ready+op+'&poll='+t, function(r){

    if (r['reload']) window.location=window.location;

    if (r.hasOwnProperty('countdown')) document.getElementById('countdown').selectedIndex=r['countdown'];
    if (r.hasOwnProperty('gamemode')) document.getElementById('gamemode').selectedIndex=r['gamemode'];
    if (r.hasOwnProperty('wordlist')) document.getElementById('wordlist').selectedIndex=r['wordlist'];

    if (r['playerList']) {

      var pl=r['playerList'], tab="<tr><th>Name</th><th>Ready?</th></tr>";
      for (var i=0;i<pl.length;i++) tab+="<tr><td>"+pl[i].Name+"</td><td>"+(pl[i].Ready==1?'&#x2714;':'')+"</td></tr>";

      document.getElementById('playerList').innerHTML = tab

    }

  });
  options={};
}
options={};
function request(o){options[o.id]=o.value;}


poll();
window.setInterval(poll, <?=$jsPollTime ?>);

</script>

</body></html><?











} else { // round != 0;

// reload page if still on round 0
if (isset($_GET['ready'])) die('{"reload":1}');





//    if round== last -> pads are back in original players' hands
if ($game['Round'] > 1+$game['NumPlayers']) {
    // if get poll (or wait?), die reload
    if($_GET['poll'] || $_GET['wait']) die('{"reload":1}');

    // No waiting on story page, 'play again' link leads to round zero

    //show story
    $r=$db->query("SELECT * FROM `pictDesc` WHERE `GameID`='{$game['GameID']}' ORDER BY `Round` ASC");
    if (!$r) errorHandler(__LINE__);

    $pad = (isset($_GET['show']) && preg_match('/^[0-9]+$/',$_GET['show']) && $_GET['show']>0 && $_GET['show']<=$game['NumPlayers'])?$_GET['show']
          : 1;

    $rounds=array();
    $round=0;
    $track = prevPlayer($pad, $game['PlayOrder']);
    //$allNames = array();

    while ($row = $r->fetch_assoc()) {
      if($row['Round']==$round && $row['Artist']==$track){
        $rounds[]=array(entitiesOut($row['ArtistName']), entitiesOut($row['Description']));
        $round++;
        $track = nextPlayer($track, $game['PlayOrder']);
      }
      //$allNames[$row['Artist']]=$row['ArtistName'];
    }

    $hue = round(360*$pad/$game['NumPlayers']);

    echo $HTMLheaderCode;?>
<style>
body{text-align:center;margin:0;background:	hsl(<?=$hue?>, 100%, 86%)}
img{
<?
if ($game['GameMode']!='1') echo
"  background:url(tex_squares.jpg);
  box-shadow:5px 5px 10px #888, inset 0 0 50px #ccc;";
?>
  max-width:98%;
  margin:1%;
}
div{padding:10px;margin:10px 0px;background:hsl(<?=$hue?>, 84%, 76%)}
.b{background:#eee;text-decoration:none;color:	hsl(<?=$hue?>, 42%, 47%);font-family:sans-serif;font-weight:bold;border-radius:10px;padding:10px 30px;margin:10px;display:inline-block;}
.b:hover{background:#fff;color:hsl(<?=$hue?>, 100%, 50%)}
b{display:block;font-size:x-large}
</style></head><body><h1>Results</h1>

<div>Initial prompt: <b><?=$rounds[0][1]?></b></div><?

    for ($i=1;$i<count($rounds);$i++) {
      if ($i%2) { //odd
        echo "{$rounds[$i][0]} sketched: <br><img src={$URL}img/{$rounds[$i][1]}>";
      } else { //even
        echo "<div>{$rounds[$i][0]} interpreted this as <b>{$rounds[$i][1]}</b></div>";
      }
    }

//    echo "<div>See other results: ";
//    foreach ($allNames as $k=>$v) if($k && $k!=$pad){
//      if ($k==$player['PlayerNum']) echo " [<a href='game'>".htmlentities($v)."</a>] ";
//      else echo " [<a href='?show=$k#{$game['GameID']}'>".htmlentities($v)."</a>] ";
//    }
    echo "<div>Rate this result: (not implemented yet)</div>";

    if ($pad==$game['NumPlayers'])
      echo "<a class=b href=new>Play Again</a>";
    else
      echo "<a class=b href='?show=".($pad+1)."'>Next Result &gt;&gt;</a>";

    echo "<br><br></body></html>";

    die();
}


if (1*$game['Round'] % 2) {//Odd - enter drawing phase

if ($_GET['wait']=='description') die('{"reload":1}');

if ($_GET['upload']) {

  $path = dirname($_SERVER['SCRIPT_FILENAME']).'/img/';
  $fname = "G{$game['GameID']}_{$game['Round']}_{$player['PlayerNum']}.png";


  $img = file_get_contents("php://input");
  file_put_contents( $path.$fname,  $img);

  if (exif_imagetype($path.$fname)!=IMAGETYPE_PNG) {
    unlink($path.$fname);
    die('{"uploaded":-1}');
  }

  $r=$db->query("INSERT IGNORE INTO `pictDesc` (`GameID`,`Round`,`Artist`,`ArtistName`,`Description`) VALUES ('{$game['GameID']}', '{$game['Round']}', '{$player['PlayerNum']}', '"
                .escape($player['Name'])."', '{$fname}')");

  if (!$r) errorHandler(__LINE__);


  echo '{"uploaded":'.strlen($img).'}';

  die();

}

if ($_GET['wait']=='upload') {

  //retrieve list of players who've not yet uploaded this round

  $r=$db->query("SELECT `ArtistName` FROM `pictDesc` WHERE `GameID`='{$game['GameID']}' AND `Round`='{$game['Round']}'");
  if (!$r) errorHandler(__LINE__);

  $pUploaded = array();
  while ($row = $r->fetch_row()) {
    $pUploaded[]=$row[0];
  }

  $r=$db->query("SELECT `Name` FROM `pictPlayers` WHERE `GameID`='{$game['GameID']}'");
  if (!$r) errorHandler(__LINE__);

  $waiting = array();
  while ($row = $r->fetch_row()) {
    if (!in_array($row[0],$pUploaded)) $waiting[]=entitiesOut($row[0]);
  }

  if (count($waiting)>0) {
    die(json_encode(array("playerList"=>$waiting)));
  } else { //Move on to next round

    $game['Round']++;

    $r=$db->query("UPDATE `pict` SET `Round`='{$game['Round']}' WHERE `GameID` = {$game['GameID']};");
    if (!$r) errorHandler(__LINE__);

    die('{"reload":1}');

  }

}
//end of ajax handling





// load prompt, from round-1
  $prev = prevPlayer($player['PlayerNum'], $game['PlayOrder']);
  $r=$db->query("SELECT `Description` FROM `pictDesc` WHERE `GameID`='{$game['GameID']}' AND `Round`='".($game['Round']-1)."' AND `Artist`='$prev'");
  if (!$r) errorHandler(__LINE__);


  $prompt = $r->fetch_assoc()["Description"];





// Check if already uploaded image, go straight to wait routine (in case page was refreshed after upload)
  $r=$db->query("SELECT * FROM `pictDesc` WHERE `GameID`='{$game['GameID']}' AND `Round`='{$game['Round']}' AND `Artist`='{$player['PlayerNum']}'");
  if ($r && $r->num_rows>0) $straightToPoll=true;

  $startCountdown = 5;

  if ($game['GameMode']==1) {
    require('_draw_etch.php');
  } else {
    require('_draw_def.php');
  }

} else { //Even - enter guessing phase

  //For players still on upload waiting screen
  if ($_GET['wait']=='upload') die('{"reload":1}');


  if ($_GET['description']) {

    $name = escape($player['Name']);
    $desc = escape(entitiesIn($_GET['description']));

    $r=$db->query("INSERT IGNORE INTO `pictDesc` (`GameID`,`Round`,`Artist`,`ArtistName`,`Description`) "
                 ."VALUES ('{$game['GameID']}', '{$game['Round']}', '{$player['PlayerNum']}', '$name', '$desc')");

    if (!$r) errorHandler(__LINE__);

    die('{"description":'.strlen($_GET['description']).'}');

  }

  if ($_GET['wait']=='description') {

    //Identical to upload wait screen
    //retrieve list of players who've not yet uploaded this round

    $r=$db->query("SELECT `ArtistName` FROM `pictDesc` WHERE `GameID`='{$game['GameID']}' AND `Round`='{$game['Round']}'");
    if (!$r) errorHandler(__LINE__);

    $pUploaded = array();
    while ($row = $r->fetch_row()) {
      $pUploaded[]=$row[0];
    }

    $r=$db->query("SELECT `Name` FROM `pictPlayers` WHERE `GameID`='{$game['GameID']}'");
    if (!$r) errorHandler(__LINE__);

    $waiting = array();
    while ($row = $r->fetch_row()) {
      if (!in_array($row[0],$pUploaded)) $waiting[]=entitiesOut($row[0]);
    }

    if (count($waiting)>0) {
      die(json_encode(array("playerList"=>$waiting)));
    } else { //Move on to next round

      $game['Round']++;
      // check if game over ? no, that comes after the reload

      $r=$db->query("UPDATE `pict` SET `Round`='{$game['Round']}' WHERE `GameID` = {$game['GameID']};");
      if (!$r) errorHandler(__LINE__);

      die('{"reload":1}');

    }

  }




  //display image
  $prev = prevPlayer($player['PlayerNum'], $game['PlayOrder']);
  $r=$db->query("SELECT `Description` FROM `pictDesc` WHERE `GameID`='{$game['GameID']}' AND `Round`='".($game['Round']-1)."' AND `Artist`='$prev'");
  if (!$r) errorHandler(__LINE__);

  $img = $URL.'img/'.$r->fetch_row()[0];

echo $HTMLheaderCode;
?><style>body{text-align:center;}</style>
</head><body>

<div id=q>

<form onsubmit='send();return false;'>
Describe what you see:
<input type=text>
<input type=submit value=Go>
</form>

</div>

<p><img style='<?
if ($game['GameMode']!='1') echo "background:url(tex_squares.jpg);box-shadow:5px 5px 10px #888, inset 0 0 50px #ccc;";
?>max-width:100%;max-height:100vh;' src=<?=$img ?>>

<script>
function ajax(url, callback){
  var r = new XMLHttpRequest();
  r.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200)
      callback(JSON.parse(this.responseText));
  };
  r.open("GET", url, true);
  r.send();
}

q=document.getElementById('q');

function send(){
  var d = encodeURIComponent(document.querySelector('input[type=text]').value.substr(0,255));
  var t = Math.round(new Date().getTime()/1000);

  if (d.length==0) alert('Please enter a description');
  else ajax('?description='+d+'&poll='+t, function(r){
    q.innerHTML = 'Sent!';
    poll();
    window.setInterval(poll, 3000);
  })
}
function poll(){
  var t = Math.round(new Date().getTime()/1000);
  ajax('?wait=description&poll='+t, function(r){

    if (r['reload']) window.location=window.location;

    if (r['playerList']) q.innerHTML='Waiting for players... ' + r['playerList'].join(', ');

  })
}



</script>

</body></html>
<?



} // End if round even

} // End if round!=0

} // End if $game set







function chooseWords($n, $avoid=array()){
  global $wordLists, $game;

  $list = file(array_keys($wordLists)[$game['WordList']]);
  $out = array();

  for ($i=0;$i<$n;$i++) {
    $e = count($list);
    do {
      $word = $list[mt_rand(0, count($list)-1)];
    } while (--$e && (in_array($word,$out) || in_array($word,$avoid)));
    $out[]=$word;
  }
  return $out;
}

function nextPlayer($me,$order){
  if (!is_array($order)) $order=explode(',',$order);
  $n=count($order);
  $place = array_search($me,$order);
  $place = ($place+1) % $n;
  return $order[$place];
}
function prevPlayer($me,$order){
  if (!is_array($order)) $order=explode(',',$order);
  $n=count($order);
  $place = array_search($me,$order);
  $place = ($place+$n-1) % $n;
  return $order[$place];
}

function escape($str, $len=255){
  global $db, $DB_UTF8MB4_SUPPORT;

  if (!$DB_UTF8MB4_SUPPORT && strlen($str)>$len) {
    do {
      $s = mb_strcut($str,0,$len);
    } while (preg_match('/&[^;]*$/',$s) && $len--);
  }

  return $db->escape_string(mb_strcut($str,0,$len));
}

function generateSessionKey() {
  $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $pieces = "";
  for ($i = 0; $i < 32; ++$i) {
    $pieces.= $keyspace[mt_rand(0, 61)];
  }
  return $pieces;
}
function isSessionKey($k) {
  return preg_match('/^[A-z0-9]{32}$/',$k);
}

function e404(){
  die();
}
function bake($s){
  setcookie("pict", $s, 0, "/", '.'.$_SERVER['HTTP_HOST'], !getenv('PICT_NO_SSL'), TRUE);
}
function errorHandler($line){
  global $db, $showErrors;
  if ($showErrors) {
    echo "[ $line ] " . $db->error ."<br>";
  }
  die("Query Failed");
}
?>
