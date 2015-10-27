<?php

#####################
#
# XBMC Set and Tag Maintenace
#
#####################

#####################
# Constants
#####################
define ("DEBUG", 0);
define ("INFO",  0);

if (DEBUG || INFO) {
   print "<pre>";
   print_r($_POST);
   print "</pre>";
}

#####################
# Parse thru the _POST array
#####################
if (isset($_POST['DB'])) {
   if ($_POST['DB'] != "None") {
      $db = $_POST['DB'];

if ($_POST['Set'] != "") {
   $s_idset = $_POST['Set'];
} else {
   $s_idset = -1;
}

if (isset($_POST['Tag'])) {
   $s_idtag = $_POST['Tag'];
} else {
   $s_idtag = -1;
}

if (isset($_POST['new_set'])) {
   $new_set = $_POST['new_set'];
}

if (isset($_POST['new_tag'])) {
   $new_tag = $_POST['new_tag'];
}

if (isset($_POST['showsets'])) {
   $showsets = TRUE;
} else {
   $showsets = FALSE;
}

if (isset($_POST['showtags'])) {
   $showtags = TRUE;
} else {
   $showtags = FALSE;
}

if (isset($_POST['moviefilter'])) {
   $moviefilter = $_POST['moviefilter'];
   if ($moviefilter == "") {
      unset ($moviefilter);
   }
}

if (isset($_POST['yearfilter'])) {
   $yearfilter = $_POST['yearfilter'];
   if ($yearfilter == "") {
      unset ($yearfilter);
   }
}

if (isset($_POST['watchedfilter'])) {
   $watchedfilter = $_POST['watchedfilter'];
   if ($watchedfilter == "") {
      unset ($watchedfilter);
   }
}

if (isset($_POST['sel_movies_add'])) {
   $movies_to_add = array_keys($_POST['sel_movies_add']);
}

if (isset($_POST['set_movies_rem'])) {
   $movies_notin_set = array_keys($_POST['set_movies_rem']);
}

if (isset($_POST['Submit'])) {
   $submit = $_POST['Submit'];
}

#####################
# Include required common constants and includes
# Include required DB functions
#####################
include ("/data/web/inc/db_funct.php.inc");

#####################
# Establish DB connections
#####################
$link=mysql_db ('prometheus-gig', 'mysql', 'MySQL', $db);

#####################
# Apply any changes to the DB
#####################
if (isset($submit)) {
   if ($submit == "Remove from Set" || $submit == "Add to Set") {
      $old_s_idset = $s_idset;
      if ($submit == "Remove from Set") {
         $old_s_idset = $s_idset;
         $s_idset = "NULL";
         $movie_arr = $movies_notin_set;
      }
      if ($submit == "Add to Set") {
         $movie_arr = $movies_to_add;
      }
      for ($x = 0; $x < count($movie_arr); $x++) {
         $upd = "update movie set idset=$s_idset where idmovie=$movie_arr[$x]";
         $res_set = mysql_query($upd);
      }
      $s_idset = $old_s_idset;
   }

   if ($submit == "Remove from Tag" || $submit == "Add to Tag") {
      $old_s_idtag = $s_idtag;
      if ($submit == "Remove from Tag") {
         $old_s_idtag = $s_idtag;
         $s_idtag = "NULL";
         $movie_arr = $movies_notin_tag;
      }
      if ($submit == "Add to Tag") {
         $movie_arr = $movies_to_add;
      }
      for ($x = 0; $x < count($movie_arr); $x++) {
         $upd = "insert into taglinks values($s_idtag, ".$movie_arr[$x].", 'movie')";
         $res_set = mysql_query($upd);
      }
      $s_idtag = $old_s_idtag;
   }

   if ($submit == "Add Set" && isset($new_set)) {
      $ins = "insert into sets(strSet) values('$new_set')";
      $res_set = mysql_query($ins);
   }

   if ($submit == "Remove Set") {
      $del = "delete from sets where idSet=$s_idset";
      $res_set = mysql_query($del);
   }

   if ($submit == "Add Tag" && isset($new_set)) {
      $ins = "insert into tag(strTag) values('$new_tag')";
      $res_tag = mysql_query($ins);
   }

   if ($submit == "Remove Tag") {
      $del = "delete from taglinks where idTag=$s_idtag";
      $res_tag = mysql_query($del);
   }
}

#####################
# Query DB and get results
#####################
$query_movie = "select idmovie, idset, c00 as title, c07 as year, playcount as watched ";
$query_movie.= "from movie m, files f where m.idfile=f.idfile ";
if (isset($moviefilter)) {
$query_movie.= "and c00 like '%".$moviefilter."%' ";
}
if (isset($yearfilter)) {
$query_movie.= "and c07 like '%".$yearfilter."%' ";
}
if (isset($watchedfilter)) {
   if ($watchedfilter == 'Y') {
$query_movie.= "and playcount=1 ";
   }
   if ($watchedfilter == 'N') {
$query_movie.= "and playcount is NULL ";
   }
}
$query_movie.= "order by c00, c07";

if (DEBUG) { print $query_movie."\n"; }

$query_set = "select * from sets order by strSet";

$query_tag = "select * from tag order by strTag";

$query_taglink = "select idTag, idMedia, c00 as title, c07 as year ";
$query_taglink.= "from taglinks t, movie m where t.idMedia=m.idMovie ";
$query_taglink.= "and idTag=$s_idtag order by idTag";

$res_movie = mysql_query($query_movie);
$res_set = mysql_query($query_set);
$res_tag = mysql_query($query_tag);
$res_taglink = mysql_query($query_taglink);

$movies = mysql_fetch_all ($res_movie);
$sets = mysql_fetch_all($res_set);
$tags = mysql_fetch_all($res_tag);
if ($res_taglink) {
   $taglinks = mysql_fetch_all($res_taglink);
}

#####################
# Close DB connections
#####################
mysql_close($link);

}
}
?>
<html>
<HEAD>
  <TITLE>XBMC Sets</TITLE>

  <LINK REL="stylesheet" HREF="../styles/buttons.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/fields.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/lists.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/links.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/menus.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/messages.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/tabs.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/titles.css" TYPE="TEXT/CSS">
  <LINK REL="stylesheet" HREF="../styles/txtsmall.css" TYPE="TEXT/CSS">

  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

</HEAD>

<BODY CLASS=PAGE_BODY>

<FORM ID=xbmcsetsform NAME=xbmcsetsform METHOD=POST action="<?php echo $_SERVER['PHP_SELF']; ?>">

<h2>
XBMC DB set and tag management
<?if (isset($db)) { print "for DB ".$db; }?>
</h2>

<table><tr>
<td>
Select a DB to manage sets and tags: 
</td>
<td>
<select name=DB ID=DB OnChange ="document.xbmcsetsform.submit()">
<OPTION value='None' SELECTED>
<option value='main_video90'
<?if ($db == 'main_video90') print "selected"?>
>main_video90
<option value='cassandra_video90'
<?if ($db == 'cassandra_video90') print "selected"?>
>cassandra_video90
<option value='nick_video90'
<?if ($db == 'nick_video90') print "selected"?>
>nick_video90
<option value='foreign_video90'
<?if ($db == 'foreign_video90') print "selected"?>
>foreign_video90
<option value='xmas_video90'
<?if ($db == 'xmas_video90') print "selected"?>
>xmas_video90
<option value='test_video90'
<?if ($db == 'test_video90') print "selected"?>
>test_video90
</select>
</td>

<td>
<input type=checkbox name=showsets id=showsets OnChange ="document.xbmcsetsform.submit()"
<? if ($showsets) { print "checked"; } ?>
>Show Movies in Sets

<br>

<input type=checkbox name=showtags id=showtags OnChange ="document.xbmcsetsform.submit()"
<? if ($showtags) { print "checked"; } ?>
>Show Movies with Tags
</td>
</tr></table>

<?
if (isset($db)) {
?>


<table bordercolor=red border=0>
<tr>
<td align=right><table border=0 bordercolor=blue width=88%>
<tr>
<td><b> Movie Title </td>
<td><b> Year </td>
<td><b> watched </td>
</tr>

<tr>
<td>
   <input type=text name=moviefilter id=moviefilter
   <? if (isset($moviefilter)) { print "value=".$moviefilter; } ?> >
</td>
<td>
   <input type=text name=yearfilter id=yearfilter
   <? if (isset($yearfilter)) { print "value=".$yearfilter; } ?> >
</td>
<td>
   <select name=watchedfilter>
   <OPTION VALUE='' SELECTED>
   <OPTION VALUE='Y' <?if ($watchedfilter == 'Y') print "selected"?> >Y
   <OPTION VALUE='N' <?if ($watchedfilter == 'N') print "selected"?> >N
   </select>
</td>
<td>
   <input type=submit name=Submit value="Filter">
</td>
</tr>
</td><tr></table>
</td></tr>

<!-- Movie column -->
<tr>
<td valign=top>
<table CLASS=LIST_FRAME border=0 bordercolor=green width=100%>

<TR>
<TD Class=sort_nonehdr align=center><font size=1>
   <b>Select All</font><br><input type=checkbox name=allmovies>
</TD>
<TD CLASS=SORT_NONEHDR> Movies - <?php print count($movies) ?> </TD>
<TD CLASS=SORT_NONEHDR align=center> W </TD>
</TR>
<?
$row = 0;
for ($x = 0; $x < count($movies); $x++) {
   $idmovie = $movies[$x]['idmovie'];
   $title = $movies[$x]['title'];
   $year = $movies[$x]['year'];
   $watched = $movies[$x]['watched'];
   if ($watched >= 1) {
      $watched = "Y";
   } else {
      $watched = "N";
   }
   $m_idset = $movies[$x]['idset'];
   
   if ($m_idset != $s_idset && (!isset($m_idset) || $showsets)) {
#   if ($m_idset != $s_idset && $showsets) {
      if ($row++ % 2 == 0) {
         print "<TR CLASS=EVEN_ROW_SMALL>";
      } else {
         print "<TR CLASS=ODD_ROW_SMALL>";
      }
      print "<td align=center>";
      print "<input type=checkbox name='sel_movies_add[$idmovie]'>";
      print "</td> ";
      print "<td width=85%>";
      if (isset($m_idset)) {
         print "<font color=green ";
         print "style='font-weight:bold;";
#         print "text-decoration: underline";
         print "'>";
      }
      print $title." - ".$year;
      if (isset($m_idset)) {
         print "</font>";
      }
      print "</td>";
      print "<td align=center>".$watched."</td>";
      print "</tr>\n";
   }
}
?>
</table>
</td>

<!-- Set/Tag management column -->
<td valign=top>
<table CLASS=LIST_FRAME border=0>
<TR><TD CLASS=SORT_NONEHDR> Set </TD></TR>
<tr><td>
   <SELECT NAME=Set ID=Set OnChange ="document.xbmcsetsform.submit()">
   <OPTION VALUE='' SELECTED>
<?

for ($x=0; $x < count($sets); $x++) {
   print "   <OPTION VALUE=".$sets[$x]['idSet'];
   if ($submit == "Add Set" && $sets[$x]['strSet'] == $new_set) {
      print " selected";
   } elseif ($s_idset == $sets[$x]['idSet']) {
      print " selected";
   }
   print ">";
   print $sets[$x]['strSet']."\n";
}
?>
</SELECT>
</td></tr>
<tr><td>&nbsp;</td></tr>
<TR>
<TD align=center>
<input type=submit name=Submit Value="Add to Set" >
&nbsp;&nbsp;&nbsp;
<input type=submit name=Submit Value="Remove from Set">
</TD>
</TR>
<tr><td><hr></td></tr>
<TR>
<TD align=center>
<input type=submit name=Submit Value="Remove Set" >
</TD>
</TR>
<tr><td><hr></td></tr>
<TR>
<TD align=center>
<input type=text size=40 name=new_set><br>
<input type=submit name=Submit Value="Add Set" >
</TD>
</TR>

<tr><td>&nbsp;</td></tr>

<TR><TD CLASS=SORT_NONEHDR> Tag </TD></TR>
<tr><td>
   <SELECT NAME=Tag ID=Tag OnChange ="document.xbmcsetsform.submit()">
   <OPTION VALUE='' SELECTED>
<?
for ($x=0; $x < count($tags); $x++) {
   print "   <OPTION VALUE=".$tags[$x]['idTag'];
   if ($s_idtag == $tags[$x]['idTag']) print " selected";
   print ">";
   print $tags[$x]['strTag']."\n";
}
?>
</SELECT>
</td></tr>

<tr><td>&nbsp;</td></tr>
<TR>
<TD align=center>
<input type=submit name=Submit Value="Add to Tag" >
&nbsp;&nbsp;&nbsp;
<input type=submit name=Submit Value="Remove from Tag">
</TD>
</TR>
<tr><td><hr></td></tr>
<TR>
<TD align=center>
<input type=submit name=Submit Value="Remove Tag" >
</TD>
</TR>
<tr><td><hr></td></tr>
<TR>
<TD align=center>
<input type=text size=40 name=new_tag><br>
<input type=submit name=Submit Value="Add Tag" >
</TD>
</TR>

</table>
</td>

<!-- Set/Tag column -->
<td valign=top>
<table CLASS=LIST_FRAME border=0 bordercolor=green width=100%>
<TR>
<TD Class=sort_nonehdr align=center><font size=1>
   <b>Select All</font><br><input type=checkbox name=allmovies>
</TD>
<TD CLASS=SORT_NONEHDR> Movies in Set</TD>
<TD CLASS=SORT_NONEHDR> W</TD>
</TR>
<?
$row = 0;
for ($x = 0; $x < count($movies); $x++) {
   $idmovie = $movies[$x]['idmovie'];
   $title = $movies[$x]['title'];
   $year = $movies[$x]['year'];
   $watched = $movies[$x]['watched'];
   if ($watched == 1) {
      $watched = "Y";
   } else {
      $watched = "N";
   }
   $m_idset = $movies[$x]['idset'];
   
   if ($m_idset == $s_idset) {
      if ($row++ % 2 == 0) {
         print "<TR CLASS=EVEN_ROW_SMALL>";
      } else {
         print "<TR CLASS=ODD_ROW_SMALL>";
      }
      print "<td align=center>";
      print "<input type=checkbox name='set_movies_rem[$idmovie]'>";
      print "</td> ";
      print "<td>".$title." - ".$year."</td>";
      print "<td>".$watched."</td>";
      print "</tr>\n";
   }
}
?>

<tr><td>&nbsp;</td></tr>

<TR>
<TD Class=sort_nonehdr align=center><font size=1>
<b>Select All</font><br><input type=checkbox name=allmovies></TD>
<TD CLASS=SORT_NONEHDR> Movies with Tag</TD>
<TD CLASS=SORT_NONEHDR> W</TD>
</TR>
<?
$row = 0;
for ($x = 0; $x < count($taglinks); $x++) {
   $idmovie = $taglinks[$x]['idMedia'];
   $title = $taglinks[$x]['title'];
   $year = $taglinks[$x]['year'];
   $watched = $movies[$x]['watched'];
   if ($watched == 1) {
      $watched = "Y";
   } else {
      $watched = "N";
   }
   
#   if ($m_idset == $s_idset) {
      if ($row++ % 2 == 0) {
         print "<TR CLASS=EVEN_ROW_SMALL>";
      } else {
         print "<TR CLASS=ODD_ROW_SMALL>";
      }
      print "<td align=center>";
      print "<input type=checkbox name='tag_movies_rem[$idmovie]'>";
      print "</td> ";
      print "<td>".$title." - ".$year."</td>";
      print "<td>".$watched."</td>";
      print "</tr>\n";
#   }
}
?>
</table>

</td>
</tr>
</table>

<?
}
?>

</form>
</body>
</HTML>
