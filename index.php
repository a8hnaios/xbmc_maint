<?php

?>
<html>
<body>

<h2>XBMC DB Management</h2>

<table>
<tr><td>
<form name=dbform id=dbform method=post action=set_tag.php>

Select a DB to manage sets and tags: 
</td>

<td>
<select name=DB>
<option>main_video93
<option>cassandra_video93
<option>nick_video93
</select>

<input type=submit value=Go>
</form>
</td></tr>

<tr><td>
<form name=dbform id=dbform method=post action=clean_up_movies.php>

Select a DB to manage movies: 
</td>

<td>
<select name=DB>
<option>main_video93
<option>cassandra_video93
<option>nick_video93
</select>

<input type=submit value=Go>
</form>
</td></tr>

<tr><td>

<form name=dbform id=dbform method=post action=clean_up_tv.php>

Select a DB to manage TV episodes: 
</td>

<td>
<select name=DB>
<option>main_video93
<option>cassandra_video93
<option>nick_video93
</select>

<input type=submit value=Go>
</form>
</td></tr>
</table>
</body>
</html>

