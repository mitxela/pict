<?php
if (!isset($URL)) {
  die();
}

$q=explode('/', $q);
if (count($q) == 2) {
  if ( !preg_match('/^[0-9]+$/',$q[1]) ) e404();

  $r=$db->query("SELECT * FROM `pict` WHERE `GameID`='{$q[1]}'");
  if (!$r) die('Query Failed ['. __LINE__ .']');
  if ($r->num_rows==0) e404();

// Also only show if same criterea for listing on index?

  $game=$r->fetch_assoc();


  $r=$db->query("SELECT * FROM `pictDesc` WHERE `GameID`='{$game['GameID']}' ORDER BY `Round` ASC");
  if (!$r) die('Query Failed ['. __LINE__ .']');

  //var_dump($r->fetch_all());

    $pad = (isset($_GET['show']) && preg_match('/^[0-9]+$/',$_GET['show']) && $_GET['show']>0 && $_GET['show']<=$game['NumPlayers'])?$_GET['show'] : 1;

    $rounds=array();
    $round=0;
    $track = prevPlayer($pad, $game['PlayOrder']);
    $allNames = array();

    while ($row = $r->fetch_assoc()) {
      if($row['Round']==$round && $row['Artist']==$track){
        $rounds[]=array(entitiesOut($row['ArtistName']), entitiesOut($row['Description']));
        $round++;
        $track = nextPlayer($track, $game['PlayOrder']);
      }
      $allNames[$row['Artist']]=$row['ArtistName'];
    }

    $hue = round(360*$pad/$game['NumPlayers']);


echo $HTMLheaderCode;?>
<style>
body{text-align:center;margin:0;background:	hsl(<?=$hue?>, 100%, 86%)}
img{
<?php
if ($game['GameMode']!='1') echo
"  background:url(../tex_squares.jpg);
  box-shadow:5px 5px 10px #888, inset 0 0 50px #ccc;";
?>
  max-width:98%;
  margin:1%;
}
div{padding:10px;margin:10px 0px;background:hsl(<?=$hue?>, 84%, 76%)}
.b{background:#eee;text-decoration:none;color:	hsl(<?=$hue?>, 42%, 47%);font-family:sans-serif;font-weight:bold;border-radius:10px;padding:10px 30px;margin:10px;display:inline-block;}
.b:hover{background:#fff;color:hsl(<?=$hue?>, 100%, 50%)}
b{display:block;font-size:x-large}
</style></head><body><h1><?=$game['GameID']." / ".entitiesOut($allNames[$pad]) ?></h1>

<div>Initial prompt: <b><?=$rounds[0][1]?></b></div><?php

    for ($i=1;$i<count($rounds);$i++) {
      if ($i%2) { //odd
        echo "{$rounds[$i][0]} sketched: <br><img src={$URL}img/{$rounds[$i][1]}>";
      } else { //even
        echo "<div>{$rounds[$i][0]} interpreted this as <b>{$rounds[$i][1]}</b></div>";
      }
    }

    echo "<div>See other results: ";
    foreach ($allNames as $k=>$v) if($k && $k!=$pad){
      echo " [<a href='?show=$k'>".entitiesOut($v)."</a>] ";
    }
    echo "</div><a class=b href={$URL}archive>Back to Archive</a><br><br></body></html>";




} else if (count($q)==1) { // Archive index


/*
 list all games where round > 1+NumPlayers

 GameID NumPlayers
 List names of all players in each game?

*/

$allNames=array();
// Select names from pictDesc because pictPlayers is session data
// Select round1 to limit total iterations
// subselect gameIDs that will be listed below (completed games, more than X players?)
$r=$db->query("SELECT `GameID`,`Artist`,`ArtistName` FROM `pictDesc` WHERE `Round`=1 AND `GameID` IN (
  SELECT `GameID` FROM `pict` WHERE `Round`>(1+`NumPlayers`) AND `NumPlayers`>2
)");
if (!$r) die('Query Failed ['. __LINE__ .']');
while ($row=$r->fetch_assoc()) {
  if (!isset($allNames[$row['GameID']])) $allNames[$row['GameID']]=array();
  $allNames[$row['GameID']][$row['Artist']]=$row['ArtistName'];
}

$sortby='`NumPlayers` DESC';
if ($_GET['sort'] == 'date') $sortby='`startTime` DESC';

$r=$db->query("SELECT * FROM `pict` WHERE `Round`>(1+`NumPlayers`) AND `NumPlayers`>2 ORDER BY $sortby");
if (!$r) die('Query Failed ['. __LINE__ .']');

echo $HTMLheaderCode;
?>
<style>

</style>
</head><body>
<pre>
<?php


while ($row=$r->fetch_assoc()) {
  echo "\n {$row['GameID']}\t{$row['startTime']} {$row['NumPlayers']}\t";
//  var_dump($allNames[$row['GameID']]);
  foreach ($allNames[$row['GameID']] as $k=>$v) echo " <a href=\"{$URL}archive/{$row['GameID']}?show=$k\">$v</a>";
}

?>
</pre>

</body>
</html>
<?php

} else e404();






