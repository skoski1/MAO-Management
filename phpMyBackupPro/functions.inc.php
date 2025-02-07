<?php
/*
 +--------------------------------------------------------------------------+
 | phpMyBackupPro                                                           |
 +--------------------------------------------------------------------------+
 | Copyright (c) 2004-2015 by Dirk Randhahn                                 |
 | http://www.phpMyBackupPro.net                                            |
 | version information can be found in definitions.php.                     |
 |                                                                          |
 | This program is free software; you can redistribute it and/or            |
 | modify it under the terms of the GNU General Public License              |
 | as published by the Free Software Foundation; either version 2           |
 | of the License, or (at your option) any later version.                   |
 |                                                                          |
 | This program is distributed in the hope that it will be useful,          |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
 | GNU General Public License for more details.                             |
 |                                                                          |
 | You should have received a copy of the GNU General Public License        |
 | along with this program; if not, write to the Free Software              |
 | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,USA.|
 +--------------------------------------------------------------------------+
*/
include_once('mysql2i.class.php');

// default debug function
function PMBP_debug($object)
{
	echo "<pre>";
	print_r($object);
	echo "</pre>";
}

// prints the basis html header in the $lang language with $scriptname scriptname
function PMBP_print_header($scriptname)
{
	global $CONF;
	global $_POST;
	global $PMBP_SYS_VAR;
	global $PMBP_MU_CONF;

	// register callback handler
	ob_start("PMBP_shutdown_handler");

	if (!isset($CONF['stylesheet'])) $CONF['stylesheet'] = "standard";
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01
Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html" . ARABIC_HTML . ">
<head>
<title>phpMyBackupPro " . PMBP_VERSION;
	// print mu mode info in browser title
	if ($_SESSION['multi_user_mode']) echo " (Multi User Mode)";
	if (isset($_SESSION['sql_user']) && isset($_SESSION['sql_passwd']))
		if ($_SESSION['sql_user'] && $_SESSION['sql_user'] == $PMBP_MU_CONF['sql_user_admin'] && $_SESSION['sql_passwd'] == $PMBP_MU_CONF['sql_passwd_admin'])
			echo " (Multi User Mode Administration)";

	if (!file_exists(PMBP_STYLESHEET_DIR . $CONF['stylesheet'] . ".css"))
		if (!file_exists(PMBP_STYLESHEET_DIR . ($CONF['stylesheet'] = "standard") . "css"))
			echo "STYLESHEET IS MISSING!";
	echo "</title>
<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\">
<meta name=\"robots\" content=\"noindex\">
<meta name=\"robots\" content=\"nofollow\">
<link href=\"images/favicon.png\" type=\"image/png\" rel=\"icon\">
<link rel=\"stylesheet\" href=\"" . PMBP_STYLESHEET_DIR . $CONF['stylesheet'] . ".css\" type=\"text/css\">
";
	readfile(PMBP_JAVASCRIPTS);
	// define menue
	$menu = array("index.php" => F_START);

	// disable config tab
	if (!$PMBP_SYS_VAR['C_disable_config']) {
		$menu = array_merge($menu, array("config.php" => F_CONFIG));
	}

	$menu = array_merge($menu, array("import.php" => F_IMPORT, "backup.php" => F_BACKUP, "scheduled.php" => F_SCHEDULE, "db_info.php" => F_DB_INFO));

	// disable sql queries in mu mode if allow_sql_queries is false or if the system-variable C_disable_sqlquery is set
	if ((!$PMBP_SYS_VAR['C_disable_sqlquery'] && $_SESSION['multi_user_mode'] && $PMBP_MU_CONF['allow_sql_queries']) || (!$PMBP_SYS_VAR['C_disable_sqlquery'] && !$_SESSION['multi_user_mode'])) {
		$menu = array_merge($menu, array("sql_query.php" => F_SQL_QUERY));
	}


	$accesskeys = array("index.php" => "m", "config.php" => "c", "import.php" => "i", "backup.php" => "b", "scheduled.php" => "s", "db_info.php" => "d", "sql_query.php" => "q", "logout" => "l", "help" => "h");
	$simple_width = 140;
	$width = count($menu) * $simple_width;

	echo "</head>

<body><center>
<table width=\"" . $width . "\">
 <colgroup>
  <col span=\"" . count($menu) . "\" width=\"" . $simple_width . "\">
 </colgroup>
 <tr>
  <th colspan=\"" . count($menu) . "\" class=\"active\" id=\"menu\">\n";
	// print titel
	echo "<div id=\"logo\">\n";
	echo PMBP_image_tag("logo.png", "phpMyBackupPro", "phpMyBackupPro Homepage", PMBP_WEBSITE);
	echo "&nbsp;&nbsp;" . PMBP_VERSION . "\n";

	// support links
	echo "<div style=\"position:relative;left:380px;top:-40px\">";
	echo "\n<a href=\"http://sourceforge.net/donate/index.php?group_id=76148\"><img src=\"http://images.sourceforge.net/images/project-support.jpg\" width=\"88\" height=\"32\" border=\"0\" alt=\"Support phpMyBackupPro\" /> </a>";
	echo "\n<a href=\"http://flattr.com/thing/464503/phpMyBackupPro-the-MySQL-backup-tool\" target=\"_blank\" >\n<img src=\"http://api.flattr.com/button/flattr-badge-large.png\" alt=\"Flattr this\" title=\"Flattr this\" border=\"0\" /></a>";
	echo "</div>";

	echo "</div>\n<div id=\"help\">\n";
	// generate popup link for proper help file
	if (!file_exists("./" . PMBP_LANGUAGE_DIR . $CONF['lang'] . "_help.php")) echo PMBP_pop_up("./" . PMBP_LANGUAGE_DIR . "english_help.php?script=" . $scriptname, PMBP_image_tag("help.gif", "", "help") . F_HELP, "help", "help");
	else echo PMBP_pop_up("./" . PMBP_LANGUAGE_DIR . $CONF['lang'] . "_help.php?script=" . $scriptname, PMBP_image_tag("help.gif", "", "help") . F_HELP, "help", "help");

	echo "\n</div>\n<div id=\"logout\">\n";


	// print logout link if function is not disabled
	if (!($CONF['no_login'] == "1" && $CONF['login'] == "0")) {
		echo "<a href=\"login.php?logout=TRUE\" accesskey=\"l\" title=\"[access key = l]\">";
		echo PMBP_image_tag("login.gif", "", "[access key = l]");
		echo F_LOGOUT . "</a>\n";
	}
	echo "\n</div>\n";

	echo "  </th>\n";

// print selection for several sql servers
	if (count($CONF['sql_passwd_s']) && basename($_SERVER['SCRIPT_NAME']) !== "config.php" && !isset($_POST['period'])) {
		echo " </tr>
 <tr>
  <th colspan=\"" . count($menu) . "\">
  <form action=\"" . basename($_SERVER['SCRIPT_NAME']) . "\" method=\"POST\">
  <span class=\"bold_left\">Select working SQL server:</span>
  <select name=\"mysql_host\" onchange=\"submit()\">\n";
		if ($CONF['sql_host'] == $_SESSION['sql_host_org'] && $CONF['sql_user'] == $_SESSION['sql_user_org']) echo "<option value=\"-1\" selected>" . $_SESSION['sql_host_org'] . " (" . $_SESSION['sql_user_org'] . ")</option>\n";
		else echo "<option value=\"-1\">" . $_SESSION['sql_host_org'] . " (" . $_SESSION['sql_user_org'] . ")</option>\n";
		for ($i = 0; $i < count($CONF['sql_passwd_s']); $i++) {

			if (isset($CONF['sql_host_s'])) {
				if ($CONF['sql_host'] == $CONF['sql_host_s'][$i] && $CONF['sql_user'] == $CONF['sql_user_s'][$i]) echo "<option value=\"" . $i . "\" selected>" . $CONF['sql_host_s'][$i] . " (" . $CONF['sql_user_s'][$i] . ")</option>\n";
				else echo "<option value=\"" . $i . "\">" . $CONF['sql_host_s'][$i] . " (" . $CONF['sql_user_s'][$i] . ")</option>\n";
			} else {
				echo "<option value=\"" . $i . "\">" . $CONF['sql_host_s'][$i] . " (" . $CONF['sql_user_s'][$i] . ")</option>\n";
			}
		}
		echo "  </select></form>
  </th>\n";
	}

	echo " </tr>
 <!-- MENU -->
 <tr>\n";

	// generate menu
	foreach ($menu as $filename => $title) {

		// print active link
		if ($filename == $scriptname && $filename != "login.php?logout=TRUE" && $filename != "HELP") {
			echo "  <th class=\"active\">\n   <a href=\"" . $filename . "\" accesskey=\"" . $accesskeys[$filename] . "\" title=\"[access key = " . $accesskeys[$filename] . "]\">" . PMBP_image_tag(substr($filename, 0, strpos($filename, ".")) . ".gif", "", "[accesskey = " . $accesskeys[$filename] . "]") . $title . "</a>\n  </th>\n";

			// print lasting menu
		} elseif ($filename != "login.php?logout=TRUE" && $filename != "HELP") {
			echo "  <th>\n   <a href=\"" . $filename . "\" accesskey=\"" . $accesskeys[$filename] . "\" title=\"[access key = " . $accesskeys[$filename] . "]\">" . PMBP_image_tag(substr($filename, 0, strpos($filename, ".")) . ".gif", "", "[accesskey = " . $accesskeys[$filename] . "]") . $title . "</a>\n  </th>\n";
		}

	}

	echo " </tr>
</table>
<table width=\"" . $width . "\">
 <colgroup>
  <col width=\"20\">
  <col width=\"*\">
  <col width=\"20\">
 </colgroup>
 <tr>
  <td>
    &nbsp;
  </td>
  <td class=\"main\">
<!-- HEADER END -->
";
}


// print basis html footer
function PMBP_print_footer()
{
	global $PMBP_SYS_VAR;
	global $PMBP_MU_CONF;

	// print but do not call the callback handler of ob_start()
	ob_flush();
	ob_end_clean();

	// adjust width of table to the number of menu items
	if (($_SESSION['multi_user_mode'] && $PMBP_MU_CONF['allow_sql_queries']) || !$_SESSION['multi_user_mode']) {
		$tabe_width = 980;
	} else {
		$tabe_width = 840;
	}

	echo "\n<!-- FOOTER -->
  </td>
  <td>
    &nbsp;
  </td>
 </tr>
</table>
<table width=\"" . $tabe_width . "\">
 <tr>
  <th class=\"active\">\n";
	printf(F_FOOTER, "<a href=\"" . PMBP_WEBSITE . "\">", "</a>");
	echo "\n</th>\n </tr>\n";

	// check for updates
	if ($PMBP_SYS_VAR['F_updates']) {

		// do this only once per session
		if (!$_SESSION['multi_user_mode'] && !isset($_SESSION['PMBP_VERSION'])) {
			$_SESSION['PMBP_VERSION'] = FALSE;
			// check web connection
			$ping_res = PMBP_is_connected();
			if ($ping_res) {
				$mode = $_SESSION['multi_user_mode'] ? 'm' : 's' + count($CONF['sql_host_s']) ? 'm' : 's';
				@set_time_limit("5");
				if (isset($PMBP_SYS_VAR['security_key'])) {
					$last_vers = @file("http://www.phpMyBackupPro.net/vers.php?v=" . PMBP_VERSION . "&m=" . $mode . "&c=" . count($CONF['sql_host_s']) . "&k=" . md5($PMBP_SYS_VAR['security_key']));
				} else {
					$last_vers = @file("http://www.phpMyBackupPro.net/vers.php?v=" . PMBP_VERSION . "&m=" . $mode . "&c=" . count($CONF['sql_host_s']));
				}
				@set_time_limit("30");
				if ($last_vers) {
					if ($last_vers[0] != PMBP_VERSION) $_SESSION['PMBP_VERSION'] = TRUE;
				}
			}
		}

		// new version found, print hint
		if ($_SESSION['PMBP_VERSION']) {
			echo "\n <tr>
      <td class=\"red large12\">
        ";
			printf(F_NOW_AVAILABLE, "<a href=\"" . PMBP_WEBSITE . "\">", "</a>");
			echo " !!!<br><br>
      </td>
     </tr>\n";
		}
	}

	// set to 0 if you don't want to see the PHP version hint any more
	if (1) {
		// check PHP version
		$tmp = phpversion();
		$phpvers = $tmp[0] . $tmp[1] . $tmp[2];
		if ($phpvers < 4.3) echo "<tr><td>PHP " . $tmp . " detected. It is not recommended to use phpMyBackupPro with PHP < PHP 4.3. You can disable this message if you want in functions.inc.php line " . __LINE__ . ".</td></tr>";
	}

	// set F_ffadd on the configuration page to 0 if you don't want to see the Firefox add any more
	if ($PMBP_SYS_VAR['F_ffadd']) {
		// removed with v2.2
	}

	echo "
</table>
</center>
</body>
</html>
";
}

// checks if the server is connected to the web
function PMBP_is_connected()
{
	$timeout = 5; //timeout in seconds
	$connected = @fsockopen("phpmybackup.sourceforge.net", 80, $dontCare1, $dontCare2, $timeout);
	if ($connected) {
		fclose($connected);
		return true;
	} else {
		return false;
	}
}

//
function PMBP_mysql_connect()
{
	global $CONF;
	$res = @mysql_connect($CONF['sql_host'], $CONF['sql_user'], $CONF['sql_passwd']);
	if ($res) {
		PMBP_set_character_set();
	}
	return $res;
}

function PMBP_set_character_set()
{
	$characterSet = PMBP_get_character_set();
	$characterSet = "utf8";
	@mysql_set_charset($characterSet);
//	@mysql_query("set names " . $characterSet);
	return $characterSet;
}

// prints html export form used on several pages
function PMBP_print_export_form($dirs1 = FALSE, $scheduled = FALSE)
{
	global $CONF;
	global $PMBP_SYS_VAR;

	echo "\n<table width=\"940\">\n";
	echo "<tr>\n<td>\n";
	echo F_SELECT_DB . ":\n";
	echo "</td>\n<td>&nbsp;</td>\n<td>";
	echo F_COMMENTS . ":";
	echo "</td>\n</tr><tr>\n<td>\n";
	echo "<select name=\"db[]\" multiple=\"multiple\" size=\"10\">\n";
	if (!$con = PMBP_mysql_connect()) ;

	// find the availabe compression methods and set which are disabled and which is selected
	if (!@function_exists("gzopen") || !@function_exists("gzcompress")) $disable_gzip = " disabled"; else $disable_gzip = "";

	$last_dbs = explode("|", $PMBP_SYS_VAR['F_dbs']);
	if (count($db_list = PMBP_get_db_list()) > 0) {
		foreach ($db_list as $db) {
			if (in_array($db, $last_dbs)) {
				echo "<option value=\"" . $db . "\" selected>" . $db . "</option>\n";
			} else {
				echo "<option value=\"" . $db . "\">" . $db . "</option>\n";
			}
		}
	} else {
		echo "<option></option>\n";
	}
	echo "</select>\n<br>";
	echo PMBP_set_select("backup", "db[]", "[" . F_SELECT_ALL . "]");
	echo "\n</td>\n<td>&nbsp;</td>\n<td>\n";
	echo "<textarea name=\"comments\" rows=\"9\" cols=\"80\">" . $PMBP_SYS_VAR['F_comment'] . "</textarea>\n<br>";
	if ($PMBP_SYS_VAR['F_tables']) $checked = "checked"; else $checked = "";
	echo "<input type=\"checkbox\" name=\"tables\" " . $checked . ">" . F_EX_TABLES . " | ";
	if ($PMBP_SYS_VAR['F_data']) $checked = "checked"; else $checked = "";
	echo "<input type=\"checkbox\" name=\"data\" " . $checked . ">" . F_EX_DATA . " | ";
	if ($PMBP_SYS_VAR['F_drop']) $checked = "checked"; else $checked = "";
	echo "<input type=\"checkbox\" name=\"drop\" " . $checked . ">" . F_EX_DROP . " | ";

	$comp_off = $comp_gzip = $comp_zip = "";
	if ($PMBP_SYS_VAR['F_compression'] == "gzip" && !$disable_gzip) $comp_gzip = " selected";
	elseif ($PMBP_SYS_VAR['F_compression'] == "zip") $comp_zip = " selected";
	else $comp_off = " selected";

	echo F_EX_COMP . "
<select name=\"zip\">
<option" . $comp_off . " value=\"\">" . F_EX_OFF . "</option>
<option " . $comp_gzip . " " . $disable_gzip . " value=\"gzip\">" . F_EX_GZIP . "</option>
<option" . $comp_zip . " value=\"zip\">" . F_EX_ZIP . "</option>
</select>\n</td>\n</tr>\n</table>\n<p></p>\n";

	// show directory backup form
	if ($CONF['dir_backup']) {
		if (!is_array($dirs1) && $PMBP_SYS_VAR['dir_lists'] >= 1) $dirs1 = PMBP_get_dirs("../");

		$last_dirs = explode("|", $PMBP_SYS_VAR['F_ftp_dirs']);

		echo "\n\n<table width=\"940\">\n";
		echo "<tr>\n<td>\n";
		if ($scheduled) {
			echo EX_DIRS . ":<br>(<a href=\"scheduled.php?update_dir_list=TRUE\">" . PMBP_EXS_UPDATE_DIRS . "</a>)<br>\n";
		} else {
			echo EX_DIRS . ":<br>(<a href=\"backup.php?update_dir_list=TRUE\">" . PMBP_EXS_UPDATE_DIRS . "</a>)<br>\n";
		}
		echo "</td>\n<td>&nbsp;</td>\n<td>\n";
		echo EX_DIRS_MAN . ":<br>\n";
		echo "</td>\n</tr><tr>\n<td>";
		echo "<select name='dirs[]' multiple=\"multiple\" size=\"9\">";
		foreach ($dirs1 as $value) {
			if (in_array("../" . $value, $last_dirs)) {
				echo "<option value=\"" . "../" . $value . "\" selected>" . "../" . $value . "</option>\n";
			} else {
				echo "<option value=\"" . "../" . $value . "\">" . "../" . $value . "</option>\n";
			}
		}
		echo "</select>\n";
		echo "\n</td>\n<td>&nbsp;</td>\n<td>\n";
		echo "<textarea rows=\"7\" cols=\"63\" name=\"man_dirs\">" . $PMBP_SYS_VAR['F_ftp_dirs_2'] . "</textarea><br>\n";
		if ($PMBP_SYS_VAR['F_packed']) $checked = "checked"; else $checked = "";
		echo "<input type=\"checkbox\" name=\"packed\" " . $checked . ">" . EX_PACKED . "\n";
		echo "</td>\n</tr>\n</table>\n<p></p>\n";
	}
}


// checks if settings on the export form where made and saves them
function PMBP_save_export_settings()
{
	global $PMBP_SYS_VAR;

	// check if any settings have changed
	if ($PMBP_SYS_VAR['F_data'] != $_POST['data'] or $PMBP_SYS_VAR['F_tables'] != $_POST['tables'] or
		$PMBP_SYS_VAR['F_compression'] != $_POST['zip'] or $PMBP_SYS_VAR['F_drop'] != $_POST['drop'] or $PMBP_SYS_VAR['F_packed'] != $_POST['packed']) {
		$PMBP_SYS_VAR['F_data'] = $_POST['data'];
		$PMBP_SYS_VAR['F_tables'] = $_POST['tables'];
		$PMBP_SYS_VAR['F_compression'] = $_POST['zip'];
		$PMBP_SYS_VAR['F_drop'] = $_POST['drop'];
		$PMBP_SYS_VAR['F_packed'] = $_POST['packed'];
	}

	if (isset($_POST['db'])) {
		if (is_array($_POST['db'])) {
			if ($PMBP_SYS_VAR['F_dbs'] != implode("|", $_POST['db'])) {
				$PMBP_SYS_VAR['F_dbs'] = implode("|", $_POST['db']);
			}
		} else {
			$PMBP_SYS_VAR['F_dbs'] = "";
		}
	} else {
		$PMBP_SYS_VAR['F_dbs'] = "";
	}

	if ($PMBP_SYS_VAR['F_comment'] != $_POST['comments']) {
		$PMBP_SYS_VAR['F_comment'] = $_POST['comments'];
	}

	if (isset($_POST['dirs'])) {
		if ($PMBP_SYS_VAR['F_ftp_dirs'] != implode("|", $_POST['dirs'])) {
			$PMBP_SYS_VAR['F_ftp_dirs'] = implode("|", $_POST['dirs']);
		}
	} else {
		$PMBP_SYS_VAR['F_ftp_dirs'] = "";
	}

	if ($PMBP_SYS_VAR['F_ftp_dirs_2'] != $_POST['man_dirs']) {
		$PMBP_SYS_VAR['F_ftp_dirs_2'] = $_POST['man_dirs'];
	}

	// update global_conf.php
	PMBP_save_global_conf();
}


// generates image tag
function PMBP_image_tag($image, $alt = "", $title = "", $link = "")
{
	if (strpos($image, "/") === false) {
		$image = PMBP_IMAGE_DIR . $image;
		$size = getimagesize($image);
	} else {
		$size = getimagesize(PMBP_IMAGE_DIR . basename($image));
	}
	if ($link)
		return "<a href=\"" . $link . "\"><img src=\"" . $image . "\" alt=\"" . $alt . "\" title=\"" . $title . "\" " . $size[3] . "></a>";
	else
		return "<img src=\"" . $image . "\" alt=\"" . $alt . "\" title=\"" . $title . "\" " . $size[3] . ">";
}


// generates javascript 'select all in input select' link
function PMBP_set_select($form, $select, $link)
{
	return "<a href=\"\" onclick=\"setSelect('" . $form . "','" . $select . "'); return false;\">" . $link . "</a>";
}


// generates javascript PMBP_pop_up link
function PMBP_pop_up($path, $link, $type, $title_attr = "")
{
	return "<a href='javascript:popUp(\"" . $path . "\",\"" . $type . "\",false,\"\")' title=\"" . $title_attr . "\">" . $link . "</a>";
}


// generates event hanlders to change the border color in a td.list list
function PMBP_change_color($color1, $color2)
{
	return "onmouseout=\"changeColor(this, '" . $color1 . "');\" onmouseover=\"changeColor(this, '" . $color2 . "');\"";
}


// generates javascript confirm dialog
// if $popupType is "view" or something else, a pop up like in PMBP_pop_up will be opened after the confirmation
function PMBP_confirm($text, $path, $link, $popupType = false)
{
	global $CONF;
	switch ($CONF['confirm']) {
		case 0:
			if ($popupType) return "<a href='javascript:popUp(\"" . $path . "\",\"" . $popupType . "\",true,\"" . $text . "\")'>" . $link . "</a>";
			else return "<a href='javascript:confirmClick(\"" . $text . "\",\"" . $path . "\")'>" . $link . "</a>";
		case 1:
			if ($popupType) {
				if (strstr($path, "all") || strstr($path, "ALL")) return "<a href='javascript:popUp(\"" . $path . "\",\"" . $popupType . "\",true,\"" . $text . "\")'>" . $link . "</a>";
				else return "<a href='javascript:popUp(\"" . $path . "\",\"" . $popupType . "\",false,\"\")'>" . $link . "</a>";
			} else {
				if (strstr($path, "all") || strstr($path, "ALL")) return "<a href='javascript:confirmClick(\"" . $text . "\",\"" . $path . "\")'>" . $link . "</a>";
				else return "<a href=\"" . $path . "\">" . $link . "</a>";
			}
		case 2:
			if ($popupType) {
				if (strstr($path, "ALL")) return "<a href='javascript:popUp(\"" . $path . "\",\"" . $popupType . "\",true,\"" . $text . "\")'>" . $link . "</a>";
				else return "<a href='javascript:popUp(\"" . $path . "\",\"" . $popupType . "\",false,\"\")'>" . $link . "</a>";
			} else {
				if (strstr($path, "ALL")) return "<a href='javascript:confirmClick(\"" . $text . "\",\"" . $path . "\")'>" . $link . "</a>";
				else return "<a href=\"" . $path . "\">" . $link . "</a>";
			}
		case 3:
			if ($popupType) {
				return "<a href='javascript:popUp(\"" . $path . "\",\"" . $popupType . "\",false,\"\")'>" . $link . "</a>";
			} else {
				return "<a href=\"" . $path . "\">" . $link . "</a>";
			}
	}
}


// function to execute the sql queries provided by the file handler $file
// $file can be a gzopen() or open() handler, $con is the database connection
// $linespersession says how many lines should be executed; if false, all lines will be executed
function PMBP_exec_sql($file, $con, $linespersession = false, $noFile = false)
{
	$query = "";
	$queries = 0;
	$error = "";
	if (isset($_GET["totalqueries"])) $totalqueries = $_GET["totalqueries"]; else $totalqueries = 0;
	if (isset($_GET["start"])) $linenumber = $_GET["start"]; else $linenumber = $_GET['start'] = 0;
	if (!$linespersession) $_GET['start'] = 1;
	$inparents = false;
	$querylines = 0;

	// $tableQueries and $insertQueries only count this session
	$tableQueries = 0;
	$insertQueries = 0;

	// stop if a query is longer than 300 lines long
	$max_query_lines = 300;

	// lines starting with these strings are comments and will be ignored
	$comment[0] = "#";
	$comment[1] = "-- ";

	while (($linenumber < $_GET["start"] + $linespersession || $query != "") && ($dumpline = gzgets($file, 65536))) {
		// increment $_GET['start'] when $linespersession was not set
		// so all lines of $file will be exeuted at once
		if (!$linespersession) $_GET['start']++;

		// handle DOS and Mac encoded linebreaks
		$dumpline = preg_replace("/\r\n$/", "\n", $dumpline);
		$dumpline = preg_replace("/\r$/", "\n", $dumpline);

		// skip comments and blank lines only if NOT in parents
		if (!$inparents) {
			$skipline = false;
			foreach ($comment as $comment_value) {
				if (!$inparents && (trim($dumpline) == "" || strpos($dumpline, $comment_value) === 0)) {
					$skipline = true;
					break;
				}
			}
			if ($skipline) {
				$linenumber++;
				continue;
			}
		}

		// remove double back-slashes from the dumpline prior to count the quotes ('\\' can only be within strings)
		$dumpline_deslashed = str_replace("\\\\", "", $dumpline);

		// count ' and \' in the dumpline to avoid query break within a text field ending by ;
		// please don't use double quotes ('"')to surround strings, it wont work
		$parents = substr_count($dumpline_deslashed, "'") - substr_count($dumpline_deslashed, "\\'");
		if ($parents % 2 != 0) $inparents = !$inparents;

		// add the line to query
		$query .= $dumpline;

		// don't count the line if in parents (text fields may include unlimited linebreaks)
		if (!$inparents) $querylines++;

		// stop if query contains more lines as defined by $max_query_lines
		if ($querylines > $max_query_lines) {
			$error = sprintf(BI_WRONG_FILE . "\n", $linenumber, $max_query_lines);
			break;
		}

		// execute query if end of query detected (; as last character) AND NOT in parents
		if (preg_match("/;$/", trim($dumpline)) && !$inparents) {
			if (!mysql_query(trim($query), $con)) {
				$error = SQ_ERROR . " " . ($linenumber + 1) . "<br>" . nl2br(htmlentities(trim($query))) . "\n<br>" . htmlentities(mysql_error());
				break;
			}

			if (strtolower(substr(trim($query), 0, 6)) == "insert") $tableQueries++;
			elseif (strtolower(substr(trim($query), 0, 12)) == "create table") $insertQueries++;
			$totalqueries++;
			$queries++;
			$query = "";
			$querylines = 0;
		}
		$linenumber++;
	}
	return array("queries" => $queries, "totalqueries" => $totalqueries, "linenumber" => $linenumber, "error" => $error, "tableQueries" => $tableQueries, "insertQueries" => $insertQueries);
}

function PMBP_get_character_set()
{
	$res = mysql_query("SHOW VARIABLES LIKE 'character_set_database'");
	$obj = mysql_fetch_array($res);
	if ($obj['Value']) {
		return $obj['Value'];
	} else {
		return "utf8";
	}
}


// Liefert ein Array mit den Tabellen in ['tables'] und den views in ['views'] zurück
function PMBP_list_tables_and_view()
{
	// get auto_increment values and names of all tables
	$result = array('tables' => array(), 'views' => array());
	$res = mysql_query("show table status");
	while ($row = mysql_fetch_array($res)) {
		if (sizeof($row['Engine']) == 0) {
			$result['views'][] = $row;
		} else {
			$result['tables'][] = $row;
		}
	}
	return $result;
}

// generates a dump of $db database
// $tables and $data set whether tables or data to backup. $comment sets the commment text
// $drop and $zip tell if to include the drop table statement or dry to pack
function PMBP_dump($db, $tables, $data, $drop, $zip, $comment)
{
	global $CONF;
	global $PMBP_SYS_VAR;
	$error = FALSE;

	// set max string size before writing to file
	if (@ini_get("memory_limit")) {
		$max_size = 500000 * ini_get("memory_limit");
	} else {
		ini_set("memory_limit", $PMBP_SYS_VAR['memory_limit'] / 1024000);
		$max_size = 500000 * ($PMBP_SYS_VAR['memory_limit'] / 1024000);
	}

	// set backupfile name
	$time = time();
	if ($zip == "gzip") $backupfile = $db . "." . $time . ".sql.gz";
	else $backupfile = $db . "." . $time . ".sql";
	$backupfile = PMBP_EXPORT_DIR . $backupfile;

	if ($con = PMBP_mysql_connect()) {

		// don't backup tables in $PMBP_SYS_VAR['except_tables']
		$except_tables = explode(",", $PMBP_SYS_VAR['except_tables']);

		//create comment
		$out = "# MySQL dump of database '" . $db . "' on host '" . $CONF['sql_host'] . "'\n";
		$out .= "# backup date and time: " . strftime($CONF['date'], $time) . "\n";
		$out .= "# built by phpMyBackupPro " . PMBP_VERSION . "\n";
		$out .= "# " . PMBP_WEBSITE . "\n\n";

		// write users comment
		if ($comment) {
			$out .= "# comment:\n";
			$comment = preg_replace("'\n'", "\n# ", "# " . $comment);
			foreach (explode("\n", $comment) as $line) $out .= $line . "\n";
			$out .= "\n";
		}


		// set and log character set
		$characterSet = PMBP_set_character_set();
		$out .= "### used character set: " . $characterSet . " ###\n";
		$out .= "set names " . $characterSet . ";\n\n";

		// print "use database" if more than one database is available
		if (count(PMBP_get_db_list()) > 1) {
			$out .= "CREATE DATABASE IF NOT EXISTS `" . $db . "`;\n\n";
			$out .= "USE `" . $db . "`;\n";
		}

		// select db
		@mysql_select_db($db);

		// get tables and views
		$tables_and_views = PMBP_list_tables_and_view();
		$all_tables = $tables_and_views['tables'];
		$all_views = $tables_and_views['views'];

		// get table structures
		$all_tables_new = array();
		foreach ($all_tables as $key => $table) {
			if (!in_array($table['Name'], $except_tables)) {
				$res1 = mysql_query("SHOW CREATE TABLE `" . $table['Name'] . "`");
				$tmp = mysql_fetch_array($res1);
				$table_sql[$table['Name']] = $tmp["Create Table"];
				$all_tables_new[$key] = $table;
			}
		}
		$all_tables = $all_tables_new;
		unset($all_tables_new);

		// find foreign keys
		$fks = array();
		if (isset($table_sql)) {
			foreach ($table_sql as $tablenme => $table) {
				$tmp_table = $table;
				// save all tables, needed for creating this table in $fks
				while (($ref_pos = strpos($tmp_table, " REFERENCES ")) > 0) {
					$tmp_table = substr($tmp_table, $ref_pos + 12);
					$ref_pos = strpos($tmp_table, "(");
					// cut of '`' at the begining and end of fk-name
					$fks[$tablenme][] = trim(str_replace('`', '', substr($tmp_table, 0, $ref_pos)));
				}
			}
		}

		// order $all_tables and check for ring constraints
		$all_tables_copy = $all_tables;
		$all_tables = PMBP_order_sql_tables($all_tables, $fks);
		$ring_constraints = false;

		// ring constraints found
		if ($all_tables === false) {
			$ring_constraints = true;
			$all_tables = $all_tables_copy;

			$out .= "\n# ring constraints workaround\n";
			$out .= "SET FOREIGN_KEY_CHECKS=0;\n";
			$out .= "SET AUTOCOMMIT=0;\n";
			$out .= "START TRANSACTION;\n";
		}
		unset($all_tables_copy);

		// as long as no error occurred
		if (!$error) {

			if ($drop) {
				$out .= "\n### drop all tables first ###\n";
				foreach (array_reverse($all_tables) as $row) {
					$out .= "\nDROP TABLE IF EXISTS `" . $row['Name'] . "`;";
				}
				$out .= "\n";
			}
			foreach ($all_tables as $row) {
				$tablename = $row['Name'];
				$auto_incr[$tablename] = $row['Auto_increment'];

				$out .= "\n\n";
				// export tables
				if ($tables) {
					$out .= "### structure of table `" . $tablename . "` ###\n\n";
					$out .= $table_sql[$tablename];

					// add auto_increment value
					if ($auto_incr[$tablename]) {
						$out .= " AUTO_INCREMENT=" . $auto_incr[$tablename];
					}
					$out .= ";";
				}
				$out .= "\n\n\n";

				// export data
				if ($data && !$error) {
					$out .= "### data of table `" . $tablename . "` ###\n\n";

					// check if field types are NULL or NOT NULL
					$res3 = mysql_query("show columns from `" . $tablename . "`");

					$res2 = mysql_query("select * from `" . $tablename . "`");
					if ($res2) {
						$number_of_rows = mysql_num_rows($res2);
						if ($number_of_rows > 0) {
							for ($j = 0; $j < $number_of_rows; $j++) {
								$out .= "insert into `" . $tablename . "` values (";
								$row2 = mysql_fetch_row($res2);
								// run through each field
								for ($k = 0; $k < $nf = mysql_num_fields($res2); $k++) {
									// identify null values and save them as null instead of ''
									if (is_null($row2[$k])) $out .= "null"; else $out .= "'" . mysql_real_escape_string($row2[$k]) . "'";
									if ($k < ($nf - 1)) $out .= ", ";
								}
								$out .= ");\n";

								// if saving is successful, then empty $out, else set error flag
								if (strlen($out) > $max_size) {
									if ($out = PMBP_save_to_file($backupfile, $zip, $out, "a")) $out = ""; else $error = TRUE;
								}
							}
						} else {
							$out .= "-- table is empty\n"; // no data
						}
					} else {
						echo "MySQL error: " . mysql_error();
						@unlink(PMBP_EXPORT_DIR . $backupfile);
						return FALSE;
					}

					// an error occurred! Try to delete file and return error status
				} elseif ($error) {
					@unlink(PMBP_EXPORT_DIR . $backupfile);
					return FALSE;
				}

				// if saving is successful, then empty $out, else set error flag
				if (strlen($out) > $max_size) {
					if ($out = PMBP_save_to_file($backupfile, $zip, $out, "a")) $out = ""; else $error = TRUE;
				}
			}


			// views
			// get all view definitions
			$all_views_definitions = array();
			foreach ($all_views as $row) {
				$viewname = $row['Name'];
				if (!in_array($viewname, $except_tables)) {
					$res4 = mysql_query("show create view `" . $viewname . "`");
					if ($res4) {
						$row4 = mysql_fetch_array($res4);
						$all_views_definitions[$viewname] = $row4['Create View'];
					} else {
						echo "MySQL error: " . mysql_error();
						@unlink(PMBP_EXPORT_DIR . $backupfile);
						return FALSE;
					}
				}
			}

			if (sizeof($all_views_definitions) > 0) $out .= "\n\n### views ###\n\n";

			// order $all_views and check for ring constraints
			$all_views_definitions_copy = $all_views_definitions;
			$all_views_definitions = PMBP_order_sql_views($all_views_definitions);
			$ring_constraints = false;

			// ring constraints found
			if ($all_views_definitions === false) {
				$all_views_definitions = $all_views_definitions_copy;

				// if not already written because of tabel ring constraints
				if (!$ring_constraints) {
					$out .= "\n# ring constraints workaround\n";
					$out .= "SET FOREIGN_KEY_CHECKS=0;\n";
					$out .= "SET AUTOCOMMIT=0;\n";
					$out .= "START TRANSACTION;\n";
				}
				$ring_constraints = true;
			}
			unset($all_views_definitions_copy);


			// view drop statements (in reverse order)
			if ($drop && count($all_views_definitions) > 0) {
				$out .= "### drop all views first ###\n\n";
				foreach (array_reverse($all_views_definitions) as $view_name => $view_definition) {
					$out .= "DROP VIEW IF EXISTS `" . $view_name . "`;\n";
				}
				$out .= "\n\n";
			}

			// view definitions
			foreach ($all_views_definitions as $view_name => $view_definition) {
				$out .= "### create statement of view `" . $view_name . "` ###\n\n";
				$out .= $view_definition;
				$out .= ";\n\n";
			}


			// if saving is successful, then empty $out, else set error flag
			if (strlen($out) > $max_size) {
				if ($out = PMBP_save_to_file($backupfile, $zip, $out, "a")) $out = ""; else $error = TRUE;
			}

			// an error occurred! Try to delete file and return error status
		} else {
			@unlink($backupfile);
			return FALSE;
		}

		// if db contained ring constraints
		if ($ring_constraints) {
			$out .= "\n\n# ring constraints workaround\n";
			$out .= "SET FOREIGN_KEY_CHECKS=1;\n";
			$out .= "COMMIT;\n";
		}

		// save to file
		if ($backupfile = PMBP_save_to_file($backupfile, $zip, $out, "a")) {
			if ($zip != "zip") return basename($backupfile);
		} else {
			@unlink($backupfile);
			return FALSE;
		}

		// create zip file in file system
		include_once("pclzip.lib.php");
		$pclzip = new PclZip($backupfile . ".zip");
		$pclzip->create($backupfile, PCLZIP_OPT_REMOVE_PATH, PMBP_EXPORT_DIR);

		// remove temporary plain text backup file used for zip compression
		@unlink(substr($backupfile, 0, strlen($backupfile)));

		if ($pclzip->error_code == 0) {
			return basename($backupfile) . ".zip";
		} else {
			// print pclzip error message
			echo PMBP_addOutput("pclzip: " . $pclzip->error_string, "red");

			// remove temporary plain text backup file
			@unlink(substr($backupfile, 0, strlen($backupfile) - 4));
			@unlink($backupfile);
			return FALSE;
		}

	} else {
		return "DB_ERROR";
	}
}


// orders the tables in $tables according to the constraints in $fks
// $fks musst be filled like this: $fks[tablename][0]=needed_table1; $fks[tablename][1]=needed_table2; ...
function PMBP_order_sql_tables($tables, $fks)
{
	// do not order if no contraints exist
	if (!count($fks)) return $tables;

	// order
	$new_tables = array();
	$existing = array();
	$modified = TRUE;
	while (count($tables) && $modified == TRUE) {
		$modified = FALSE;
		foreach ($tables as $key => $row) {
			// delete from $tables and add to $new_tables
			// does table has fk?
			if (isset($fks[$row['Name']])) {
				// find required tables
				foreach ($fks[$row['Name']] as $needed) {
					// go to next table if not all needed tables exist in $existing
					// in order to find all tables first, which do have no constraints or constraints which can already be resolved
					if (!in_array($needed, $existing)) {
						continue 2;
					}
				}
			}

			// if all required tables are already in the new-tables-list:
			// delete from $tables and add to $new_tables
			$existing[] = $row['Name'];
			$new_tables[] = $row;
			prev($tables);
			unset($tables[$key]);
			$modified = TRUE;
		}
	}

	if (count($tables)) {
		// probably there are 'circles' in the constraints, because of that no proper backups can be created
		// This will be fixed sometime later through using 'alter table' commands to add the constraints after generating the tables.
		// Until now I just add the lasting tables to $new_tables, return them and print a warning
		foreach ($tables as $row) $new_tables[] = $row;
//        echo PMBP_addOutput("THIS DATABASE SEEMS TO CONTAIN 'RING CONSTRAINTS'. pMBP DOES NOT SUPPORT THEM. PROBABLY THE FOLLOWING BACKUP IS BROKEN!","red_left");
		return false;
	}
	return $new_tables;
}


// orders the views in $views according to the constraints in $fks
function PMBP_order_sql_views($views)
{
	// order
	$new_views = array();
	$existing = array();
	$modified = TRUE;
	while (count($views) && $modified == TRUE) {
		$modified = FALSE;
		foreach ($views as $name => $definition) {
			// delete from $views and add to $new_views
			// does view refer to another view
			foreach (array_keys($views) as $dependendName) {
				if ($name != $dependendName && stripos($definition, '`' . $dependendName . '`') !== false) {
					continue 2;
				}
			}

			// if all required views are already in the new-views-list:
			// delete from $views and add to $new_views
			$new_views[$name] = $definition;
			prev($views);
			unset($views[$name]);
			$modified = TRUE;
		}
	}

	if (count($views)) {
		// probably there are 'circles' in the constraints, because of that no proper backups can be created
		// This will be fixed sometime later through using 'alter table' commands to add the constraints after generating the tables.
		// Until now I just add the lasting tables to $new_tables, return them and print a warning
		foreach ($views as $name => $definition) $new_views[$name] = $definition;
//        echo PMBP_addOutput("THIS DATABASE SEEMS TO CONTAIN 'RING CONSTRAINTS'. pMBP DOES NOT SUPPORT THEM. PROBABLY THE FOLLOWING BACKUP IS BROKEN!","red_left");
		return false;
	}
	return $new_views;
}


// saves the string in $fileData to the file $backupfile as gz file or not ($zip)
// returns backup file name if name has changed (zip), else TRUE. If saving failed, return value is FALSE
function PMBP_save_to_file($backupfile, $zip, &$fileData, $mode)
{
	// save to a gzip file
	if ($zip == "gzip") {
		if ($zp = @gzopen($backupfile, $mode . "wb9")) {
			@gzwrite($zp, $fileData);
			@gzclose($zp);
			return $backupfile;
		} else {
			return FALSE;
		}

		// save to a plain text file (uncompressed)
	} else {
		if ($zp = @fopen($backupfile, $mode)) {
			@fwrite($zp, $fileData);
			@fclose($zp);
			return $backupfile;
		} else {
			return FALSE;
		}
	}
}


// updates the content in global_conf.php
function PMBP_save_global_conf($global_conf_path = "")
{
	global $CONF;
	global $PMBP_SYS_VAR;

	// to ensure that all configuration settings are saved
	@ignore_user_abort(TRUE);

	// create content for global.conf
	$file = "<?php\n\n// This file is automatically generated and modified by phpMyBackupPro " . PMBP_VERSION . "\n\n";
	if (is_array($CONF)) {
		foreach ($CONF as $item => $conf) {
			// don't save multi server settings to gloabl_conf.php
			if ($item == "sql_host_s" || $item == "sql_user_s" || $item == "sql_passwd_s" || $item == "sql_db_s") continue;

			// don't store sql data in mu mode
			if ($_SESSION['multi_user_mode'] && ($item == "sql_passwd" || $item == "sql_host" || $item == "sql_user" || $item == "sql_db")) continue;

			// update $_SESSION['sql_host_org'] etc. if new sql data were entered on the config page
			if (basename($_SERVER['SCRIPT_NAME']) == "config.php") {
				$_SESSION['sql_host_org'] = htmlspecialchars($CONF['sql_host']);
				$_SESSION['sql_user_org'] = htmlspecialchars($CONF['sql_user']);
				$_SESSION['sql_passwd_org'] = htmlspecialchars($CONF['sql_passwd']);
				$_SESSION['sql_db_org'] = htmlspecialchars($CONF['sql_db']);
			}

			// save current $CONF['sql_...'] values only if we use the multi server mode
			if ($item == "sql_host" && count($CONF['sql_host_s'])) {
				$file .= "\$CONF['" . $item . "']=ue('" . htmlspecialchars(replaceSingleQuote($_SESSION['sql_host_org'])) . "');\n";
			} elseif ($item == "sql_user" && count($CONF['sql_host_s'])) {
				$file .= "\$CONF['" . $item . "']=ue('" . htmlspecialchars(replaceSingleQuote($_SESSION['sql_user_org'])) . "');\n";
			} elseif ($item == "sql_passwd" && count($CONF['sql_host_s'])) {
				$file .= "\$CONF['" . $item . "']=ue('" . htmlspecialchars(replaceSingleQuote($_SESSION['sql_passwd_org'])) . "');\n";
			} elseif ($item == "sql_db" && count($CONF['sql_host_s'])) {
				$file .= "\$CONF['" . $item . "']=ue('" . htmlspecialchars(replaceSingleQuote($_SESSION['sql_db_org'])) . "');\n";
			} else {
				// save the current values for all other settings
				$file .= "\$CONF['" . $item . "']=ue('" . htmlspecialchars(replaceSingleQuote($conf)) . "');\n";
			}
		}
	}

	// unset 'last_scheduled_' values in sys vars which no longer belong to an account
	foreach ($PMBP_SYS_VAR as $key => $value) {
		if (substr($key, 0, 15) == "last_scheduled_" && substr($key, 15) >= count($CONF['sql_host_s'])) unset($PMBP_SYS_VAR[$key]);
	}

	// add system variables
	$file .= "\n";
	foreach ($PMBP_SYS_VAR as $item => $sys_var) {
		$file .= "\$PMBP_SYS_VAR['" . $item . "']=ue('" . htmlspecialchars(replaceSingleQuote($sys_var)) . "');\n";
	}

	$file .= "\n?>";

	if (!$global_conf_path) $global_conf_path = PMBP_GLOBAL_CONF;
	return PMBP_save_to_file($global_conf_path, FALSE, $file, "w");
}

function replaceSingleQuote($string)
{
	return str_replace("'", "\\'", $string);
}

function ue($string)
{
	return htmlspecialchars_decode(str_replace("\\'", "'", $string));
}


// saves $files backup files on $server ftp server in $path path using $user username and $pass password
function PMBP_ftp_store($files)
{
	global $CONF;
	global $PMBP_SYS_VAR;
	$out = FALSE;

	// try to connect to server using username and passwort
	if (!$CONF['ftp_server']) {
		$out .= PMBP_addOutput(C_WRONG_FTP . "!", "red");
	} elseif (!$conn_id = @ftp_connect($CONF['ftp_server'], $CONF['ftp_port'], $PMBP_SYS_VAR['ftp_timeout'])) {
		$out .= PMBP_addOutput(F_FTP_1 . " '" . $CONF['ftp_server'] . "'!", "red");
	} else {
		if (!$login_result = @ftp_login($conn_id, $CONF['ftp_user'], $CONF['ftp_passwd'])) {
			$out .= PMBP_addOutput(F_FTP_2 . " '" . $CONF['ftp_user'] . "'", "red");
		} else {

			// succesfully connected
			if ($CONF['ftp_pasv']) ftp_pasv($conn_id, TRUE); else ftp_pasv($conn_id, FALSE);
			if (!$CONF['ftp_path']) $path = "."; else $path = $CONF['ftp_path'];

			// upload the files
			foreach ($files as $filename) {
				$source_file = $filename;
				if (substr($filename, 0, strlen(PMBP_EXPORT_DIR)) == PMBP_EXPORT_DIR) $filename = substr($filename, strlen(PMBP_EXPORT_DIR));
				$dest_file = $path . "/" . $filename;

				// try three times to upload
				$check = FALSE;
				for ($i = 0; $i < 3; $i++)
					if (!$check) $check = @ftp_put($conn_id, $dest_file, $source_file, FTP_BINARY);
				if (!$check) $out .= PMBP_addOutput(F_FTP_3 . ": '" . $source_file . "' -> '" . $dest_file . "'", "red");
				else $out .= PMBP_addOutput(F_FTP_4 . " '" . $dest_file . "'", "green");
			}

			// close the FTP connection
			if (@function_exists("ftp_close")) @ftp_close($conn_id);
		}
	}
	return $out;
}


// send email with $attachments backup files to $email email using $sitename for sender and subject
function PMBP_email_store($attachments, $backup_info, $packed = false)
{
	global $CONF;
	global $mode;
	if (!isset($mode)) $mode = "web";

	$out = FALSE;
	$lb = "\n";
	$all_emails = explode(",", $CONF['email']);

	$mailtext = F_MAIL_2 . " '" . $CONF['sitename'] . "'." . $lb;

	// send database backups
	if (is_array($backup_info)) {
		if ($backup_info['comp'] == "gzip") $mailtext .= INF_COMP . ": gzip" . $lb;
		elseif ($backup_info['comp'] == "zip") $mailtext .= INF_COMP . ": zip" . $lb;
		else $mailtext .= INF_COMP . ": " . F_NO . $lb;
		if ($backup_info['drop']) $mailtext .= INF_DROP . ": " . F_YES . $lb; else $mailtext .= INF_DROP . ": " . F_NO . $lb;
		if ($backup_info['tables']) $mailtext .= INF_TABLES . ": " . F_YES . $lb; else $mailtext .= INF_TABLES . ": " . F_NO . $lb;
		if ($backup_info['data']) $mailtext .= INF_DATA . ": " . F_YES . $lb; else $mailtext .= INF_DATA . ": " . F_NO . $lb;
		$mailtext .= INF_COMMENT . ":" . $lb . $backup_info['comments'];
	} // send directory backups
	else {
		$mailtext .= INF_COMMENT . ":" . $lb . $backup_info;
	}
	srand((double)microtime() * 1000000);
	$boundary = "=_" . md5(uniqid(rand()) . microtime());
	$parts[-1] = "Content-Type: text/plain; charset=\"utf8\"" . $lb . $lb . $mailtext . $lb;

	// backup as one ZIP file
	if ($packed) {
		// create zip-file
		include_once("pclzip.lib.php");
		$filename = $CONF['sitename'] . "." . time() . ".zip";
		$pclzip = new Pclzip(PMBP_EXPORT_DIR . $filename);
		$pclzip->create($attachments);

		// add to email
		$zipbody = file_get_contents(PMBP_EXPORT_DIR . $filename);
		$zipbody = rtrim(chunk_split(base64_encode($zipbody), 76, $lb)) . $lb;
		$parts[0] = "Content-Type: application/zip; name=\"" . $filename .
			"\"" . $lb . "Content-Transfer-Encoding: base64" . $lb . "Content-Disposition: attachment; filename=\"" . $filename . "\"" . $lb . $lb . $zipbody . $lb;

		// delete local zip
		@unlink(PMBP_EXPORT_DIR . $filename);
	} // add all attachments to email
	else {
		for ($i = 0; $i < count($attachments); $i++) {
			$bodies[$i] = file_get_contents($attachments[$i]);
			$bodies[$i] = rtrim(chunk_split(base64_encode($bodies[$i]), 76, $lb)) . $lb;
			$path_parts = pathinfo($attachments[$i]);

			$parts[$i] = "Content-Type: application/" . $path_parts['extension'] . "; name=\"" . $path_parts['basename'] .
				"\"" . $lb . "Content-Transfer-Encoding: base64" . $lb . "Content-Disposition: attachment; filename=\"" . $path_parts['basename'] . "\"" . $lb . $lb . $bodies[$i] . $lb;
		}
	}

	// add the parts like this to avoid memory problems with impode() (see below)
	// thanks to sourceforge patch #1624792
	$encoded['body'] = "--" . $boundary . $lb;
	$firstRow = true;
	foreach ($parts as $part) {
		if (!$firstRow) {
			$encoded['body'] .= "--" . $boundary . $lb;
		}
		$encoded['body'] .= $part;
		$firstRow = false;
	}
	$encoded['body'] .= "--" . $boundary . "--" . $lb . $lb;
//	 $encoded['body']="--".$boundary.$lb.implode("--".$boundary.$lb,$parts)."--".$boundary."--".$lb.$lb;

	$headers = "From: phpMyBackupPro on " . $CONF['sitename'] . " <" . $all_emails[0] . ">" . $lb . "Mime-Version: 1.0" . $lb . "Content-Type: multipart/mixed;" . $lb . "\tboundary=\"" . $boundary . "\"";

	// send to all addresses
	foreach ($all_emails as $email) {
		// verify email
		if (!preg_match("/^\ *[������a-zA-Z0-9_-]+(\.[������a-zA-Z0-9\._-]+)*@([������a-zA-Z0-9-]+\.)+([a-z]{2,4})$/i", $email)) {
			$out .= PMBP_addOutput(F_MAIL_1, "red");
			continue;
		}
	}

	// create subject
	if (count($CONF['sql_host_s'])) {
		$subject = F_MAIL_4 . " " . $CONF['sitename'] . " (" . $CONF['sql_host'] . ", " . $CONF['sql_user'] . ")";
	} else {
		$subject = F_MAIL_4 . " " . $CONF['sitename'];
	}

	// send mail
	if (!@mail($CONF['email'], $subject, $encoded['body'], $headers)) {
		$out .= PMBP_addOutput(F_MAIL_5, "red");
	} else {
		$out .= PMBP_addOutput(F_MAIL_6 . " " . $CONF['email'], "green");
	}

	return $out;
}

// returns present local backup files after deleting backups files
function PMBP_get_backup_files()
{
	global $CONF;
	$delete_files = FALSE;
	$all_files = FALSE;
	$result_files = FALSE;
	$handle = @opendir(PMBP_EXPORT_DIR);
	$remove_time = time() - ($CONF['del_time'] * 86400);
	while ($file = @readdir($handle)) {
		if ($file != "." && $file != ".." && preg_match("'\.sql|\.sql\.gz|\.sql\.zip'", $file)) {

			// don't delete if del_time is not set
			if ($CONF['del_time']) {
				if (PMBP_file_info("time", $file) < $remove_time) $delete_files[] = $file; else $all_files[] = $file;
			} else {
				$all_files[] = $file;
			}
		}
	}

	// sort descending
	if (is_array($all_files)) rsort($all_files);

	// delete oldest backup files if there are to many for one db
	if (is_array($all_files)) {
		foreach ($all_files as $file) {
			if (!isset($counter[$db = PMBP_file_info("db", PMBP_EXPORT_DIR . $file)])) $counter[$db] = 1; else $counter[$db]++;
			if ($counter[$db] > $CONF['del_number']) $delete_files[] = $file; else $result_files[] = $file;
		}
	}

	// now delete the files
	if ($delete_files) PMBP_delete_backup_files($delete_files);

	// sort ascending
	if (is_array($result_files)) sort($result_files);
	return $result_files;
}


// delete the file(s) in mixed $files from local export dir and remote ftp server
function PMBP_delete_backup_files($files)
{
	global $CONF;
	$out = "";
	if (!is_array($files)) $files = array($files);
	foreach ($files as $file) if (!@unlink(PMBP_EXPORT_DIR . $file))
		$out .= PMBP_addOutput(sprintf(F_DEL_FAILED, $file), "red");

	// find and delete all old files from the ftp server
	if ($CONF['ftp_use'] && $CONF['ftp_del']) $out .= PMBP_ftp_del($files);
	return $out;
}


// deletes $files backup files from $server ftp server in $path path using $user username and $pass password
function PMBP_ftp_del($delete_files = array())
{
	global $CONF;
	global $PMBP_SYS_VAR;
	$out = FALSE;

	// try to connect to server using username and passwort
	if (!$CONF['ftp_server']) {
		$out .= PMBP_addOutput(C_WRONG_FTP, "red");
	} elseif (!$conn_id = @ftp_connect($CONF['ftp_server'], $CONF['ftp_port'], $PMBP_SYS_VAR['ftp_timeout'])) {
		$out .= PMBP_addOutput(F_FTP_1 . " '" . $CONF['ftp_server'] . "'!", "red");
	} else {
		if (!$login_result = @ftp_login($conn_id, $CONF['ftp_user'], $CONF['ftp_passwd'])) {
			$out .= PMBP_addOutput(F_FTP_2 . " '" . $CONF['ftp_user'], "red");
		} else {

			// succesfully connected
			if ($CONF['ftp_pasv']) ftp_pasv($conn_id, TRUE); else ftp_pasv($conn_id, FALSE);

			// get files in remote directory
			if (!$CONF['ftp_path']) $path = "."; else $path = $CONF['ftp_path'];
			$remote_files = ftp_nlist($conn_id, $path);

			if (is_array($remote_files)) {
				// separate filename
				for ($i = 0; $i < count($remote_files); $i++)
					if (strrchr($remote_files[$i], "/")) $remote_files[$i] = substr(strrchr($remote_files[$i], "/"), 1);

				// don't delete if del_time is false
				if ($CONF['del_time']) {
					$remove_time = time() - ($CONF['del_time'] * 86400);
					foreach ($remote_files as $remote_file) {
						if (substr($remote_file, count($remote_file) - 4) != ".sql" &&
							substr($remote_file, count($remote_file) - 7) != ".sql.gz" &&
							substr($remote_file, count($remote_file) - 8) != ".sql.zip") continue;
						if (PMBP_file_info("time", $remote_file) < $remove_time) {
							$delete_files[] = $remote_file;
						} else {
							$all_files[] = $remote_file;
						}
					}
				} else {
					$all_files = $remote_files;
				}

				// sort descending
				if (isset($all_files)) {
					if (is_array($all_files)) rsort($all_files);

					// delete oldest backup files if there are to many for one db
					if (is_array($all_files)) {
						foreach ($all_files as $file) {
							$db = PMBP_file_info("db", $file);
							if (!isset($counter[$db])) $counter[$db] = 1; else $counter[$db]++;
							if ($counter[$db] > $CONF['del_number']) $delete_files[] = $file; else $result_files[] = $file;
						}
					}
				}

				// delete the files in $delete_files
				if (is_array($delete_files)) {
					foreach ($delete_files as $filename) {
						$dest_file = $path . "/" . $filename;

						// try three times to delete
						$check = FALSE;
						for ($i = 0; $i < 3; $i++) {
							if (!$check) $check = @ftp_delete($conn_id, $dest_file);
						}
						if (!$check) $out .= PMBP_addOutput(sprintf(F_FTP_5, $dest_file), "red");
						else $out .= PMBP_addOutput(sprintf(F_FTP_6, $dest_file), "green");
					}
				}
			}

			// close the FTP connection
			if (@function_exists("ftp_close")) @ftp_close($conn_id);
		}
	}
	return $out;
}


// returns list of databases on $host host using $user user and $passwd password
function PMBP_get_db_list()
{
	global $CONF;

	// if there is given the name of a single database
	if ($CONF['sql_db']) {
		PMBP_mysql_connect();
		if (@mysql_select_db($CONF['sql_db'])) {
			$dbs = array($CONF['sql_db']);
		} else {
			$dbs = array();
		}
		return $dbs;
	}

	// else try to get a list of all available databases on the server
	$list = array();
	PMBP_mysql_connect();
	$db_list = @mysql_list_dbs();
	while ($row = @mysql_fetch_array($db_list)) {
		if (@mysql_select_db($row['Database'])) {
			$list[] = $row['Database'];
		}
	}
	return $list;
}


// in dependency on $mode different modes can be selected (see below)
function PMBP_file_info($mode, $path)
{
	$filename = preg_replace("#.*/#", "", $path);
	$parts = explode(".", $filename);

	switch ($mode) {

		// returns the name of the database a $path backup file belongs to
		case "db":
			return $parts[0];

		// returns the creation timestamp $path backup file
		case "time":
			return $parts[1];

		// returns "gz" if $path backup file is gziped
		case "gzip":
			if (isset($parts[3])) if ($parts[3] == "gz") return $parts[3];
			break;

		// returns "zip" if $path backup file is ziped
		case "zip":
			if (isset($parts[3])) if ($parts[3] == "zip") return $parts[3];
			break;

		// returns type of compression of $path backup file or no
		case "comp":
			if (PMBP_file_info("gzip", $path)) return "gzip"; elseif (PMBP_file_info("zip", $path)) return "zip";
			else return F_NO;

		// returns the size of $path backup file
		case "size":
			return filesize($path);

		// returns yes if the backup file contains 'drop table if exists' or no if not
		case "drop":
			while ($line = PMBP_getln($path)) {
				$line = trim($line);
				if (stripos($line, "drop table if exists") === 0 || stripos($line, "drop view if exists") === 0) {
					PMBP_getln($path, true);
					return F_YES;
				} else {
					$drop = F_NO;
				}
			}
			PMBP_getln($path, true);
			return $drop;

		// returns yes if the $path backup files contains tables or no if not
		case "tables":
			while ($line = PMBP_getln($path)) {
				$line = trim($line);
				if (stripos($line, "create table") === 0 || stripos($line, "create temporary table") === 0 || stripos($line, "create algorithm") === 0 || stripos($line, "create view") === 0) {
					PMBP_getln($path, true);
					return F_YES;
				} else {
					$table = F_NO;
				}
			}
			PMBP_getln($path, true);
			return $table;

		// returns yes if the $path backup files contains data or no if not
		case "data":
			while ($line = PMBP_getln($path)) {
				$line = trim($line);
				if (strtolower(substr($line, 0, 6)) == "insert") {
					PMBP_getln($path, true);
					return F_YES;
				} else {
					$data = F_NO;
				}
			}
			PMBP_getln($path, true);
			return $data;

		// returns the comment stored to the backup file
		case "comment":
			while ($line = PMBP_getln($path)) {
				$line = trim($line);
				if (isset($comment) && substr($line, 0, 1) == "#") {
					$comment .= substr($line, 2) . "<br>";
				} elseif (isset($comment) && substr($line, 0, 1) != "#") {
					PMBP_getln($path, true);
					return $comment;
				}
				if ($line == "# comment:") $comment = FALSE;
			}
			PMBP_getln($path, true);
			if (isset($comment)) return $comment; else return FALSE;
	}
}


// returns the content of the [gziped] $path backup file line by line
function PMBP_getln($path, $close = false, $org_path = false)
{
	if (!isset($GLOBALS['lnFile'])) $GLOBALS['lnFile'] = null;
	if (!$org_path) $org_path = $path; else $org_path = PMBP_EXPORT_DIR . $org_path;

	// gz file
	if (PMBP_file_info("gzip", $org_path) == "gz") {
		if (!$close) {
			if ($GLOBALS['lnFile'] == null) {
				$GLOBALS['lnFile'] = gzopen($path, "r");
			}

			if (!gzeof($GLOBALS['lnFile'])) {
				return gzgets($GLOBALS['lnFile']);
			} else {
				$close = true;
			}
		}

		if ($close) {
			// remove the file handler
			@gzclose($GLOBALS['lnFile']);
			$GLOBALS['lnFile'] = null;
			return null;
		}

		// zip file
	} elseif (PMBP_file_info("zip", $org_path) == "zip") {
		if (!$close) {
			if ($GLOBALS['lnFile'] == null) {
				// try to guess the filename of the packed file
				// known problem: ZIP file xyz.sql.zip contains file abc.sql which already exists with different content!
				if (!file_exists(substr($org_path, 0, strlen($org_path) - 4))) {
					// extract the file
					include_once("pclzip.lib.php");
					$pclzip = new PclZip($path);
					$extracted_file = $pclzip->extract(PMBP_EXPORT_DIR, "");

					if ($pclzip->error_code != 0) {
						// print pclzip error message
						echo PMBP_addOutput("pclzip: " . $pclzip->error_string . "<br>" . BI_BROKEN_ZIP . "!", "red");
						return false;
					} else {
						unset($pclzip);
					}
				}
			}

			// read the extracted file
			$line = PMBP_getln(substr($org_path, 0, strlen($org_path) - 4));
			if ($line == null) $close = true;
			else return $line;
		}

		// remove the temporary file
		if ($close) {
			@fclose($GLOBALS['lnFile']);
			$GLOBALS['lnFile'] = null;
			@unlink(substr($org_path, 0, strlen($org_path) - 4));
			return null;
		}

		// sql file
	} else {
		if (!$close) {
			if ($GLOBALS['lnFile'] == null) {
				$GLOBALS['lnFile'] = fopen($path, "r");
			}

			if (!feof($GLOBALS['lnFile'])) {
				return fgets($GLOBALS['lnFile']);
			} else {
				$close = true;
			}
		}

		if ($close) {
			// remove the file handler
			@fclose($GLOBALS['lnFile']);
			$GLOBALS['lnFile'] = null;
			return null;
		}
	}
}


// determines the best size type for filesize $size and returns array('value'=xxx,'type'=yyy)
function PMBP_size_type($size)
{
	$types = array("B", "KB", "MB", "GB");
	for ($i = 0; $size > 1000; $i++, $size /= 1024) ;
	$result['value'] = round($size, 2);
	$result['type'] = $types[$i];
	return $result;
}


// get recursive directory list
function PMBP_get_dirs($dir, $renew = FALSE)
{
	$dirs = array();

	// renew date if the 'renew' link was clicked
	if (isset($_GET['update_dir_list'])) $renew = true;

	// return existing data
	if ($renew) unset($_SESSION['file_system'][$dir]);
	if (isset($_SESSION['file_system'][$dir])) return $_SESSION['file_system'][$dir];

	// create directory list
	$dir_handle = @opendir($dir);
	while ($file = @readdir($dir_handle)) {
		if ($file != "." && $file != "..") {
			if (@is_dir($dir . $file)) {
				$dirs[] = $file . "/";
				$tmp = PMBP_get_dirs($dir . $file . "/", TRUE);
				if (is_array($tmp)) {
					foreach ($tmp as $value) {
						$dirs[] = $file . "/" . $value;
					}
				}
			}
		}
	}
	natsort($dirs);
	$_SESSION['file_system'][$dir] = $dirs;
	return $dirs;
}


// get list of all files in directory
function PMBP_get_files($dir)
{
	global $CONF;

	$dirs = array();
	$dir = trim($dir);
	if ($dir_handle = @opendir($dir)) {
		while (FALSE !== ($file = readdir($dir_handle))) {
			if ($file != "." && $file != "..") {
				if (!is_dir($dir . $file)) {
					$dirs[] = $dir . $file;
					// recursive listing of files
				} elseif ($CONF['dir_rec']) {
					$tmp = PMBP_get_files($dir . $file . "/");
					if (is_array($tmp)) foreach ($tmp as $value) $dirs[] = $value;
				}
			}
		}
		@closedir($dir_handle);
	}
	return $dirs;
}

// creates an output string in html in web mode
function PMBP_addOutput($string, $class)
{
	global $mode;
	if (!isset($mode)) $mode = "web";
	$out = "";

	if ($mode == "web") $out .= "<div class=\"" . $class . "\">" . $string . "</div>\n";
	else if ($mode == "shell") $out .= $string . "\n";
	else if ($mode == "incl") $out .= $string . "<br>\n";

	return $out;
}

// transfer files $files to FTP servers dirictories and create missing folders
// also sends the files by email (if configured)
function PMBP_save_FTP_and_Email($files, $packed = false)
{
	global $CONF;
	global $PMBP_SYS_VAR;
	global $mode;
	if (!isset($mode)) $mode = "web";

	$out = FALSE;

	// try to connect to server using username and passwort
	$ftp_connect_failed = false;
	if (!$CONF['ftp_server']) {
		if ($CONF['ftp_use']) $out .= PMBP_addOutput(C_WRONG_FTP, "red");
		$ftp_connect_failed = true;
	} elseif (!$conn_id = @ftp_connect($CONF['ftp_server'], $CONF['ftp_port'], $PMBP_SYS_VAR['ftp_timeout'])) {
		if ($CONF['ftp_use']) $out .= PMBP_addOutput(F_FTP_1 . " '" . $CONF['ftp_server'] . "'!", "red");
		$ftp_connect_failed = true;
	} elseif (!$login_result = @ftp_login($conn_id, $CONF['ftp_user'], $CONF['ftp_passwd'])) {
		if ($CONF['ftp_use']) $out .= PMBP_addOutput(F_FTP_2 . " '" . $CONF['ftp_user'] . "'", "red");
		$ftp_connect_failed = true;
	}
	if (!$ftp_connect_failed) {
		// succesfully connected -> set passive and change to the right path
		if ($CONF['ftp_pasv']) ftp_pasv($conn_id, TRUE); else ftp_pasv($conn_id, FALSE);
		if (!$CONF['ftp_path']) $path = "."; else $path = $CONF['ftp_path'];
		@ftp_chdir($conn_id, $path);
	}
	// backup as one ZIP file
	if ($packed) {

		include_once("pclzip.lib.php");
		$filename = $CONF['sitename'] . "." . time() . ".zip";
		$pclzip = new Pclzip(PMBP_EXPORT_DIR . $filename);

		$v_list = $pclzip->create($files);

		// try three times to upload zip files
		if (!$ftp_connect_failed) {
			$check = FALSE;
			for ($i = 0; $i < 3; $i++) {
				if (!$check) $check = ftp_put($conn_id, $filename, PMBP_EXPORT_DIR . $filename, FTP_BINARY);
			}
			if ($check) {
				$out .= PMBP_addOutput(F_FTP_4 . " '" . $CONF['ftp_path'] . "/" . $filename . "'", "green");
			} else {
				$out .= PMBP_addOutput(F_FTP_3, "red");
			}
		}

		// backup files by emails
		if ($PMBP_SYS_VAR['dir_email_backup']) {
			$zipFile[] = PMBP_EXPORT_DIR . $filename;
			$out .= PMBP_email_store($zipFile, $_POST['comments']);
		}

		@unlink(PMBP_EXPORT_DIR . $filename);

		// backup each file
	} else {
		if (!$ftp_connect_failed) {
			// create all missing folders
			foreach ($files as $filepath) {
				if ($filepath = trim($filepath)) {
					$folders = explode("/", $filepath);
					$filename = array_pop($folders);
					$deep = 0;
					$all_folders = "";
					$all_folders_local = "";
					foreach ($folders as $folder) {
						$all_folders_local .= $folder . "/";
						if ($folder != "." && $folder != "..") {
							if (!@ftp_chdir($conn_id, $folder)) {
								@ftp_mkdir($conn_id, $folder);
								@ftp_chdir($conn_id, $folder);
							}
							// adjust directory permissions
							//ftp_chmod($conn_id,substr(sprintf('%o', fileperms("../".$folder)), -4),"../".$folder);
							$all_folders .= $folder . "/";
							$deep++;
						}
					}

					// change back to $path
					$rel_path = "";
					for ($i = 0; $i < $deep; $i++) $rel_path .= "../";
					@ftp_chdir($conn_id, $rel_path);

					// define the source and destination pathes
					$dest_file = $all_folders . $filename;
					$source_file = "./" . $filepath;

					// try three times to upload
					$check = FALSE;

					for ($i = 0; $i < 3; $i++) if (!$check) $check = @ftp_put($conn_id, $dest_file, $source_file, FTP_BINARY);
					if ($check) {
						$out .= PMBP_addOutput(F_FTP_4 . " '" . $CONF['ftp_path'] . "/" . $dest_file . "'", "green");
					} else {
						$out .= PMBP_addOutput(F_FTP_3 . ": '" . $source_file . "' -> '" . $dest_file . "'", "red");
					}
				}
			}
		}

		// backup files by emails
		if ($PMBP_SYS_VAR['dir_email_backup']) {
			$out .= PMBP_email_store($files, $_POST['comments']);
		}

		// close the FTP connection
		if (!$ftp_connect_failed && @function_exists("ftp_close")) @ftp_close($conn_id);
	}
	return $out;
}


// login module
function PMBP_auth()
{
	header("WWW-Authenticate: Basic realm=\"phpMyBackupPro\"");
	header("HTTP/1.0 401 Unauthorized");
	echo LI_MSG . "\n";
}

function PMBP_shutdown_handler($buffer)
{
	global $mode;

	if (connection_status() == 1 || connection_status() == 3) {
//		$buffer .= "user abborted!";
	}
	if (connection_status() == 2 || connection_status() == 3) {
		$buffer .= "<br>" . PMBP_addOutput(F_TIMEOUT, "red");
		if (isset($mode) && $mode == "web") {
			$buffer .= "<br><br>\n";
			$buffer .= "<div class=\"bold\">&lt;- <a href=\"javascript:history.back()\">" . EXS_BACK . "</a></div>";
		}
	}
	return $buffer;
}

?>
