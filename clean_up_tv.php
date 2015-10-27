<?php

$sapi_type = php_sapi_name();

#####################
#
# Clean up TV episodes and library
#
#####################

#####################
# Constants
#####################
define ("DEBUG", 1);
define ("INFO",  0);
define ("TV_PATH", "/export/tv_shows/TV/*");
define ("DELETE_FILES", "Delete");
define ("CLEAN_UP_DB",  "Clean Up DB");

$dbs = array ("main_video93", "cassandra_video93", "nick_video93");

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

if (isset($_POST['Submit'])) {
   $submit = $_POST['Submit'];
}

if (isset($_POST['tvshowfilter'])) {
   $tvshowfilter = $_POST['tvshowfilter'];
   if ($tvshowfilter == "") {
      unset ($tvshowfilter);
   }
}

if ($submit == DELETE_FILES || $submit == CLEAN_UP_DB) {
   if ($submit == DELETE_FILES) {
      $idfiles = $_POST['del_all'];
   }
   if ($submit == CLEAN_UP_DB) {
      if (count ($_POST['del_all']) > 0) {
         $idfiles = $_POST['del_all'];
      } else {
         $idfiles = $_POST['clean_all'];
      }
      $idpaths = $_POST['clean_dir'];
   }

   $q_del_files = "select strpath, strfilename from files f, path p ";
   $q_del_files.= "where f.idpath=p.idpath and (";

   $q_del_db = "delete f.*, p.* from files f, path p ";
   $q_del_db.= "where f.idpath=p.idpath and (";

   for ($x = 0; $x < count($idfiles)-1; $x++) {
      $q_del_files.= "f.idfile=".$idfiles[$x]." or \n";
      $q_del_db.= "f.idfile=".$idfiles[$x]." or \n";
   }

   $q_del_files.= "f.idfile=".$idfiles[$x].")";
   $q_del_db.= "f.idfile=".$idfiles[$x].")";

   $q_del_dirs = "delete from path where ";
   for ($x = 0; $x < count($idpaths)-1; $x++) {
      $q_del_dirs.= "idpath=".$idpaths[$x]." or \n";
   }
   $q_del_dirs.= "idpath=".$idpaths[$x];
}

}

#####################
# Include required common constants and includes
# Include required DB functions
#####################
include ("/data/web/inc/db_funct.php.inc");

#####################
# Get the files from the DBs
#####################
$link=mysql_connect ('prometheus-gig', 'mysql', 'MySQL');

if (!$link) {
    echo "Unable to connect to DB: " . mysql_error();
    exit;
}

#
# If the form was submitted we do stuff
#
if (isset($submit)) {
   mysql_select_db($_POST['DB']);

   if (DEBUG) { print "<pre>"; }

   if ($submit == DELETE_FILES) {
      $res_del_file = mysql_query($q_del_files);

      if (DEBUG) { print $del_files."\n"; }

      $count=0;
      while ($row = mysql_fetch_assoc($res_del_file)) {
         $del_files[$count] = substr($row['strpath'], stripos($row['strpath'], "/export/tv"));
         $del_files[$count++].= $row['strfilename'];
      }

      if (DEBUG) { print_r($del_files); }

      for ($x = 0; $x < count($del_files); $x++) {

         if (DEBUG) { print "Deleting ".$del_files[$x]."\n"; }
         unlink ($del_files[$x]);

         if (count(glob(dirname($del_files[$x])."/*")) == 0) {

            if (DEBUG) { print "DIR: ".dirname($del_files[$x])."\n"; }
            rmdir (dirname($del_files[$x]));

            if (count(glob(dirname(dirname($del_files[$x]))."/*")) == 0) {
               if (DEBUG) { print "PDIR: ".dirname(dirname($del_files[$x]))."\n"; }
               rmdir(dirname(dirname($del_files[$x])));
            }
         }
      }
   }

   if ($submit == DELETE_FILES || $submit == CLEAN_UP_DB) {
      if (count($idfiles) > 0) {
         if (DEBUG) { print $q_del_db."\n"; }
         $res_del_db = mysql_query($q_del_db);
print "<pre>";
print "DEL FILE\n";
print $q_del_db;
print "</pre>";
      }

      if (count($idpaths) > 0) {
         if (DEBUG) { print $q_del_dirs."\n"; }
         $res_del_dirs = mysql_query($q_del_dirs);
print "<pre>";
print "DEL DIR\n";
print $q_del_dirs;
print "</pre>";
      }
   }

   if (DEBUG) { print "</pre>"; }
}


#####################
# Read all the info from all the DBs and figure out
# what's to be deleted or cleaned up
#####################
for ($db_cnt=0; $db_cnt < count($dbs); $db_cnt++) {

   $db_name = $dbs[$db_cnt];
   mysql_select_db($db_name);

   # Select all episodes in the DB
   $q_in = "select strpath as path, strfilename as file ";
   $q_in.= "from episode e, files f, path p ";
   $q_in.= "where e.idfile=f.idfile and f.idpath=p.idpath and ";
   if (isset($tvshowfilter)) {
      $q_in.= "strpath like '%/TV/%".$tvshowfilter."%' order by path, file";
   } else {
      $q_in.= "strpath like '%/TV/%' order by path, file";
   }

   # Select episodes that have a TV Show in the DB (i.e. orphans)
   $q_not_in = "select p.idpath, idfile, strPath path, strfilename file ";
   $q_not_in.= "from path p, files f ";
   if (isset($tvshowfilter)) {
      $q_not_in.= "where strpath like '%/TV/%".$tvshowfilter."%' ";
   } else {
      $q_not_in.= "where strpath like '%/TV/%' ";
   }
   $q_not_in.= "and p.idpath=f.idpath ";
   $q_not_in.= "and f.idfile not in (select idfile from episode) order by path, file";

   # Select left-over directories that can be cleaned up from the DB
   $q_dirs = "select idpath, strpath as path from path where idpath ";
   $q_dirs.= "not in (select idpath from files) ";
   $q_dirs.= "and strpath like '%/TV/%' order by strpath";

   $res_in = mysql_query($q_in);
   $res_not_in = mysql_query($q_not_in);
   $res_dirs = mysql_query($q_dirs);

   if (!$res_in) {
      echo "Could not successfully run query ($q_in) from DB: " . mysql_error();
      exit;
   }
   if (!$res_not_in) {
      echo "Could not successfully run query ($q_not_in) from DB: " . mysql_error();
      exit;
   }

   # Get all the episodes in the DB
   while ($row = mysql_fetch_assoc($res_in)) {
      $path = substr(strstr($row['path'], "10.20.10.210"), strlen("10.20.10.210"));
      $file = $row['file'];

      $files_in_db[$db_name][] = $path.$file;
   }

   @sort($files_in_db[$db_name]);

   # Get all the orphan episodes in the DB
   $count=0;
   while ($row = mysql_fetch_assoc($res_not_in)) {
      $path = substr(strstr($row['path'], "10.20.10.210"), strlen("10.20.10.210"));
      $file = $row['file'];

      $files_not_in_db[$db_name][$count]['idfile'] = $row['idfile'];
      $files_not_in_db[$db_name][$count]['idpath'] = $row['idpath'];
      $files_not_in_db[$db_name][$count]['episode'] = $path.$file;
      $files_not_in_db[$db_name][$count]['delete'] = 'N';
      $files_not_in_db[$db_name][$count]['other_dbs'] = "";
      $files_not_in_db[$db_name][$count++]['clean_db'] = 'N';
   }

   # Get all the orphan directories in the DB
   $count = 0;
   while ($row = mysql_fetch_assoc($res_dirs)) {
      $path = substr($row['path'], stripos($row['path'], "/export/tv"));
      $dirs[$db_name][$count]['path'] = $path;
      $dirs[$db_name][$count]['idpath'] = $row['idpath'];
      $dirs[$db_name][$count++]['clean_db'] = 'N';
   }

}

#####################
# Close DB connections
#####################
mysql_close($link);
}

#####################
# Get the files from the disk
#####################
$count=0;
$path = array("/export/tv_shows/TV/*");

while(count($path) != 0) {
   $v = array_shift($path);

   foreach(glob($v) as $item) {
      if (is_dir($item)) {
         $path[] = $item . '/*';
      } else {
         $files_on_disk[] = $item;
      }
   }
}

#####################
# Find out which files should be removed and DB entries needs a clean up
# and if any are "confused"
#####################

for ($db_cnt = 0; $db_cnt < count($dbs); $db_cnt++) {

   $db_name=$dbs[$db_cnt];

   for ($x = 0; $x < count($files_not_in_db[$db_name]); $x++) {

      $episode = $files_not_in_db[$db_name][$x]['episode'];

      for ($new_db = 0; $new_db < count($dbs); $new_db++) {
         $new_db_name = $dbs[$new_db];
         if ($db_name == $new_db_name) { continue; }
         if (@in_array($episode, $files_in_db[$new_db_name])) {
            $files_not_in_db[$db_name][$x]['other_dbs'].= $new_db_name." ";
         }
      }

      # If a filename exists in another DB then if should be clean up
      # If a filename does not exist in another DB then it should be delete from the disk
      if ($files_not_in_db[$db_name][$x]['other_dbs'] == "") {
         $files_not_in_db[$db_name][$x]['delete'] = 'Y';
         if (!in_array($episode, $files_on_disk)) {
            $files_not_in_db[$db_name][$x]['delete'] = 'N';
            $files_not_in_db[$db_name][$x]['clean_db'] = 'Y';
         }
      } else {
         $files_not_in_db[$db_name][$x]['clean_db'] = 'Y';
      }
      
   }

   # If a directory is empty and it's in the DB then it can be removed from the path table
#print"<pre>";
#print_r($dirs[$db_name]);
#print"</pre>";
#exit;
   for ($x = 0; $x < count($dirs[$db_name]); $x++) {
      if (count(glob($dirs[$db_name][$x]['path']."/*")) == 0) {
         $dirs[$db_name][$x]['clean_db'] = 'Y';
      }
   }
}

?>
<html>
<HEAD>
  <TITLE>XBMC TV Episode Clean Up</TITLE>

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

<SCRIPT LANGUAGE=JavaScript TYPE="text/javascript">
function clear_all() {
  checkboxes = document.getElementsByName('del_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checkboxes = document.getElementsByName('del_some');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checkboxes = document.getElementsByName('clean_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checkboxes = document.getElementsByName('clean_some');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checker = document.getElementsByName('all_del');
  for(var i=0, n=checker.length;i<n;i++) {
    checker[i].checked = "";
  }
  checker = document.getElementsByName('all_clean');
  for(var i=0, n=checker.length;i<n;i++) {
    checker[i].checked = "";
  }
}
function toggle(source) {
  checkboxes = document.getElementsByName('del_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = source.checked;
  }
  checkboxes = document.getElementsByName('del_some');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checkboxes = document.getElementsByName('clean_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checker = document.getElementsByName('all_clean');
  for(var i=0, n=checker.length;i<n;i++) {
    checker[i].checked = "";
  }
}
function toggle2(source) {
  checkboxes = document.getElementsByName('clean_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = source.checked;
  }
  checkboxes = document.getElementsByName('del_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checker = document.getElementsByName('all_del');
  for(var i=0, n=checker.length;i<n;i++) {
    checker[i].checked = "";
  }
}

function toggler(source, name) {
  checkboxes = document.getElementsByName(name);
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = source.checked;
  }
}

function toggle_class(source) {
  checkboxes = document.getElementsByClassName(source.className);
  for(var i=0, n=checkboxes.length;i<n;i++) {
       checkboxes[i].checked = source.checked;
  }
  checkboxes = document.getElementsByName('clean_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checker = document.getElementsByName('all_clean');
  for(var i=0, n=checker.length;i<n;i++) {
    checker[i].checked = "";
  }
}
function toggle_class2(source) {
  checkboxes = document.getElementsByName('clean_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    if (checkboxes[i].className == source.className) {
       checkboxes[i].checked = source.checked;
    }
  }
  checkboxes = document.getElementsByName('del_all[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = "";
  }
  checker = document.getElementsByName('all_del');
  for(var i=0, n=checker.length;i<n;i++) {
    checker[i].checked = "";
  }
}

</script>

</HEAD>

<BODY CLASS=PAGE_BODY>

<FORM ID=xbmctvepisode NAME=xbmctvepisode METHOD=POST action="<?php echo $_SERVER['PHP_SELF']; ?>">

<h2>
XBMC TV Episode Management
<?if (isset($db)) { print "for DB ".$db; }?>
</h2>

<table><tr>
<td>
Select a DB to manage TV episodes: 
</td>
<td>
<select name=DB ID=DB OnChange ="clear_all(); document.xbmctvepisode.submit()">
<OPTION value='None' SELECTED>

<?
for ($db_cnt = 0; $db_cnt < count($dbs); $db_cnt++) {
	print "<option value='".$dbs[$db_cnt]."'";
	if ($db == $dbs[$db_cnt]) print " selected";
	print ">".$dbs[$db_cnt]."\n";
}
?>
</select>
</td>
</tr>
</table>

<?
if (isset($db)) {
?>

<table bordercolor=red border=0>
<tr>
<td align=left><table border=0 bordercolor=blue width=48%>
<tr>
<td width=30%>&nbsp;</td>
<td><b> TV Show </td>
<td><b> watched </td>
</tr>

<tr>
<td>&nbsp;</td>
<td>
   <input type=text name=tvshowfilter id=tvshowfilter
   <? if (isset($tvshowfilter)) { print "value=".$tvshowfilter; } ?> >
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

<!-- Delete from Disk column -->
<tr>
<td valign=top>
<table CLASS=LIST_FRAME border=0 bordercolor=green width=100%>

<TR>
<TD colspan=3><b>Delete from Disk</td>
</TR>

<TR>
<TD Class=sort_nonehdr align=center><font size=1>
   <b>Select All</font><br><input type=checkbox name='all_del' onClick="toggle(this)" >
</TD>
<TD CLASS=SORT_NONEHDR> TV Show/TV Episode </TD>
<TD CLASS=SORT_NONEHDR align=center> W </TD>
</TR>

<?

$db_name = $db;

$row = 0;
$old_tv_show = "";
for ($x = 0; $x < count($files_not_in_db[$db_name]); $x++) {
   if ($files_not_in_db[$db_name][$x]['delete'] == 'Y') {
      $idfile = $files_not_in_db[$db_name][$x]['idfile'];
      $episode = substr($files_not_in_db[$db_name][$x]['episode'], 20);
      $details = explode ("/", $episode);
      $tv_show = $details[0];
      $real_episode = substr($episode, strlen($tv_show)+1);

      if ($old_tv_show != $tv_show) {
         print "\n ";
         print "<TR CLASS=EVEN_ROW_SMALL>";
         print "<td bgcolor=#FAFF7E align=center> <input type=checkbox class='".$tv_show."_del' name='del_some' onClick=\"toggle_class(this)\" </td>";
         print "<td width=85% bgcolor=#FAFF7E><b>$tv_show</b></td>";
         print "</TR> \n";
         $row = 0;
      }

      if ($row++ % 2 == 0) {
         print "<TR CLASS=EVEN_ROW_SMALL>";
      } else {
         print "<TR CLASS=ODD_ROW_SMALL>";
      }
      print "<td align=center>";
      print "<input type=checkbox class='".$tv_show."_del' name='del_all[]' value='$idfile'>";
      print "</td> ";
      print "<td width=85%>";

      print $real_episode;
      print "</td>";
      print "<td align=center>".$watched."</td>";
      print "</tr>\n";

      $old_tv_show = $tv_show;

   }
}

?>

<tr><td>&nbsp;</td></tr>
<TR>
<TD align=center>
<input type=submit name=Submit Value="Delete" >
</TD>
</TR>
<tr><td>&nbsp;</td></tr>
<TR>
<TD align=center>
<input type=submit name=Submit Value="Clean Up DB" >
</TD>
</TR>

</table>
</td>

<!-- Clean Up DB column part 1 -->
<td valign=top>
<table CLASS=LIST_FRAME border=0 bordercolor=green width=100%>

<TR>
<TD colspan=3><b>Clean Up DB</td>
</TR>

<TR>
<TD Class=sort_nonehdr align=center><font size=1>
   <b>Select All</font><br><input type=checkbox name='all_clean'  onClick="toggle2(this)" >
</TD>
<TD CLASS=SORT_NONEHDR> TV Show/TV Episode </TD>
<TD CLASS=SORT_NONEHDR> Other DB </TD>
<TD CLASS=SORT_NONEHDR align=center> W </TD>
</TR>

<?
$row = 0;
$old_tv_show = "";
for ($x = 0; $x < count($files_not_in_db[$db_name]); $x++) {
   if ($files_not_in_db[$db_name][$x]['clean_db'] == 'Y') {
      $idfile = $files_not_in_db[$db_name][$x]['idfile'];
      $episode = substr($files_not_in_db[$db_name][$x]['episode'], 20);
      $details = explode ("/", $episode);
      $tv_show = $details[0];
      $real_episode = substr($episode, strlen($tv_show)+1);
      $other_db = trim($files_not_in_db[$db_name][$x]['other_dbs']);

      if ($old_tv_show != $tv_show) {
         print "\n <TR CLASS=EVEN_ROW_SMALL>";
         print "<td bgcolor=#FAFF7E align=center> <input type=checkbox class='".$tv_show."_clean' name='clean_some' onClick=\"toggle_class2(this)\" </td>";
         print "<td width=85% bgcolor=#FAFF7E><b>$tv_show</b></td>";
         print "</TR> \n";
         $row = 0;
      }

      if ($row++ % 2 == 0) {
         print "<TR CLASS=EVEN_ROW_SMALL>";
      } else {
         print "<TR CLASS=ODD_ROW_SMALL>";
      }
      print "<td align=center>";
      print "<input type=checkbox class='".$tv_show."_clean' name='clean_all[]' value='$idfile'>";
      print "</td> ";
      print "<td width=85%>";

      print $real_episode;
      print "</td>";
      print "<td align=left>".$other_db."</td>";
      print "<td align=center>".$watched."</td>";
      print "</tr>\n";

      $old_tv_show = $tv_show;

   }
}
?>

<TR>
<TD colspan=3>&nbsp;</td>
</TR>

<!-- Clean Up DB column part 2 -->
<TR>
<TD colspan=3><b>Clean Up Empty Dirs from DB</td>
</TR>

<TR>
<TD Class=sort_nonehdr align=center><font size=1>
   <b>Select All</font><br><input type=checkbox name='all_dir'  onClick="toggler(this, 'clean_dir[]')" >
</TD>
<TD CLASS=SORT_NONEHDR colspan=3> Dir </TD>
</TR>

<?
$row = 0;
for ($x = 0; $x < count($dirs[$db_name]); $x++) {
   if ($dirs[$db_name][$x]['clean_db'] == 'Y') {
      $idpath = $dirs[$db_name][$x]['idpath'];
      $dir = $dirs[$db_name][$x]['path'];

      if ($row++ % 2 == 0) {
         print "<TR CLASS=EVEN_ROW_SMALL>";
      } else {
         print "<TR CLASS=ODD_ROW_SMALL>";
      }
      print "<td align=center>";
      print "<input type=checkbox name='clean_dir[]' value='$idpath'>";
      print "</td> ";
      print "<td width=85% colspan=3>";

      print $dir;
      print "</td>";
      print "</tr>\n";
   }
}
?>

<tr><td>&nbsp;</td></tr>
<TR>
<TD align=center>
<input type=submit name=Submit Value="Clean Up DB" >
</TD>
</TR>

</table>
</td>

</tr>
</table>

<?
}

exit;

?>