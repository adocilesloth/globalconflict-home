<?php
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
require_once($phpbb_root_path . 'includes/functions_content.' . $phpEx);


// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('viewtopic');

/*Get time of next battleday*/
$timezone = date_default_timezone_get();
date_default_timezone_set('UTC');
$current_time = strtotime("now");

$sql = "SELECT battle_name, battle_start, battle_length FROM abc_battles WHERE battle_start + (battle_length * 3600) > $current_time ORDER BY battle_start ASC";
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow();
$db->sql_freeresult($result);

date_default_timezone_set($timezone);

$battleday = false;
$battle_name = '';
$battle_start = 0;
$battle_end = 0;
$local_time = '';
$local_string  = '';
if($row)
{
	/*Time need to be *1000 as javascript does eveything in ms*/
	$battleday = true;
	$battle_start = $row['battle_start'] * 1000;
	$battle_end = ($battle_start + ($row['battle_length'] * 3600)) * 1000;
	$battle_name = $row['battle_name'];
	
	$local_date = date('Y-m-d', $battle_start/1000);
	$local_time = date('H:i:s', $battle_start/1000);
	$local_string = "at $local_time on $local_date";
}

/*$battleday = true;
$battle_name = 'Test Battle';
$battle_start = 1555174800000;
$battle_end = 1555196400000;
$local_date = date('Y-m-d', $battle_start/1000);
$local_time = date('H:i:s', $battle_start/1000);
$local_string = "at $local_time on $local_date";*/

/*Determine correct SBT times*/
date_default_timezone_set('Europe/London');
$EU_DST = date('I', $current_time);
date_default_timezone_set('America/New_York');
$NA_DST = date('I', $current_time);

$sbt = "<b>SBT</b> = ";
if($EU_DST && $NA_DST)	//Summer/DST
{
	$sbt .= "10am Pacific, 12pm Central, 1pm Eastern, 17:00 UTC, 18:00 BST, 19:00 CEST";
}
elseif($EU_DST && !$NA_DST) //Autumn, follow NA
{
	$sbt .= "10am Pacific, 12pm Central, 1pm Eastern, 17:00 UTC, 17:00 BST, 18:00 CEST";
}
elseif(!$EU_DST && $NA_DST) //Spring, follow EU
{
	$sbt .= "11am Pacific, 1pm Central, 2pm Eastern, 18:00 UTC, 18:00 GMT, 19:00 CET";
}
else //if(!EU_DST && !NA_DST) //Winter
{
	$sbt .= "10am Pacific, 12pm Central, 1pm Eastern, 18:00 UTC, 18:00 GMT, 19:00 CET";
}

$template->assign_vars(array(
	'B_BATTLE'		=> $battleday,
	'BATTLE_NAME'	=> $battle_name,
	'T_BD_START'	=> $battle_start,
	'T_BD_END'		=> $battle_end,
	'BATTLE_DATE'	=> $local_string,
	'SBT'			=> $sbt,
));

/* Get Latest Announcements */
$num_anno = 3;	//Number of announcements to get
$anno_forum = 'Announcements';	//Name of announcement forum

$sql = "SELECT tt.*, pt.*
		FROM ".TOPICS_TABLE." AS tt
		JOIN ".FORUMS_TABLE." AS ft ON tt.forum_id = ft.forum_id
		JOIN ".POSTS_TABLE." AS pt ON tt.topic_first_post_id = pt.post_id
		WHERE ft.forum_name = '$anno_forum'
		ORDER BY tt.topic_id DESC";
$result = $db->sql_query_limit($sql, $num_anno);
$rowset = $db->sql_fetchrowset();
$db->sql_freeresult($result);

$announcements = '';

foreach($rowset as $topics_row)
{
	$topic_title = $topics_row['topic_title'];
	$topic_author = get_username_string('full', $topics_row['topic_poster'], $topics_row['topic_first_poster_name'], $topics_row['topic_first_poster_colour']);
	$topic_date = $user->format_date($topics_row['topic_time']);
	$topic_link = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $topics_row['forum_id'] . '&amp;t=' . $topics_row['topic_id']);
	$topic_views = $topics_row['topic_views'];
	
	$topics_row['bbcode_options'] = (($topics_row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
								(($topics_row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
								(($topics_row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
	$post_text = generate_text_for_display($topics_row['post_text'], $topics_row['bbcode_uid'], $topics_row['bbcode_bitfield'], $topics_row['bbcode_options']);

	$topic_title = censor_text($topic_title);
	
	$announcements .= "<div class=\"post_body\">";
	/*Title*/
	$announcements .= "<h3 class=\"first\"><a href=\"$topic_link\">$topic_title</a></h3>";
	/*Author*/
	$announcements .= "<p class=\"author\">";
	$announcements .= "<a class=\"unread\" href=\"$topic_link\" title=\"Post\">";
	$announcements .= "<i class=\"icon fa-file fa-fw icon-lightgray icon-md\" aria-hidden=\"true\"></i><span class=\"sr-only\">Post</span>";
	$announcements .= "</a>";
	$announcements .= "<span class=\"responsive-hide\">by $topic_author » </span>$topic_date";
	$announcements .= "</p>";
	/*Content*/
	$announcements .= "<div class=\"content\">$post_text</div>";
	$announcements .= "</div>";
}

$template->assign_vars(array(
	'HOME_ANNO'		=> $announcements,
));

$anon = $user->data['user_id'] == ANONYMOUS;
if(!$anon)
{
	/*Count new, unread and your posts8?
	/*From Board3Portal*/
	/* https://www.phpbb.com/community/viewtopic.php?t=2002265#p12787688 */
	$ex_fid_ary = array_unique(array_merge(array_keys($auth->acl_getf('!f_read', true)), array_keys($auth->acl_getf('!f_search', true))));

	if ($auth->acl_get('m_approve'))
	{
		$m_approve_fid_ary = array(-1);
		$m_approve_fid_sql = '';
	}
	else if ($auth->acl_getf_global('m_approve'))
	{
		$m_approve_fid_ary = array_diff(array_keys($auth->acl_getf('!m_approve', true)), $ex_fid_ary);
		$m_approve_fid_sql = ' AND (p.post_approved = 1' . ((sizeof($m_approve_fid_ary)) ? ' OR ' . $db->sql_in_set('p.forum_id', $m_approve_fid_ary, true) : '') . ')';
	}
	else
	{
		$m_approve_fid_ary = array();
		$m_approve_fid_sql = ' AND p.post_approved = 1';
	}

	$sql = 'SELECT COUNT(distinct t.topic_id) as total
		FROM ' . TOPICS_TABLE . ' t
		WHERE t.topic_last_post_time > ' . $user->data['user_lastvisit'] . '
		AND t.topic_moved_id = 0 ' . ((sizeof($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '');
		/*' . str_replace(array('p.', 'post_'), array('t.', 'topic_'), $m_approve_fid_sql) . '
		' . ((sizeof($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '');*/
	$result = $db->sql_query($sql);
	$new_posts_count = (int)$db->sql_fetchfield('total');
	$db->sql_freeresult($result);

	// your post number
	$sql = "SELECT user_posts
	FROM " . USERS_TABLE . "
	WHERE user_id = " . $user->data['user_id'];
	$result = $db->sql_query($sql);
	$you_posts_count = (int) $db->sql_fetchfield('user_posts');
	$db->sql_freeresult($result);

	// unread posts
	$sql_where = 'AND t.topic_moved_id = 0 ' . ((sizeof($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '');
	/*' . str_replace(array('p.', 'post_'), array('t.', 'topic_'), $m_approve_fid_sql) . '
	' . ((sizeof($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '');*/
	$unread_list = array();
	$unread_list = get_unread_topics($user->data['user_id'], $sql_where, 'ORDER BY t.topic_id DESC');

	if (!empty($unread_list))
	{
		$sql = 'SELECT COUNT(distinct t.topic_id) as total
		FROM ' . TOPICS_TABLE . ' t
		WHERE ' . $db->sql_in_set('t.topic_id', array_keys($unread_list));
		$result = $db->sql_query($sql);
		$unread_posts_count = (int) $db->sql_fetchfield('total');
		$db->sql_freeresult($result);
	}
	else
	{
		$unread_posts_count = 0;
	}

	/*Other data and pass to .html*/
	$username = $user->data['username'];
	$unread_pm_count = $user->data['user_unread_privmsg'];
	
	$template->assign_vars(array(
		'L_NEW_POSTS'		=> "View new posts ($new_posts_count)",
		'L_UNREAD_POSTS'	=> "View unread posts ($unread_posts_count)",
		'L_SELF_POSTS'		=> "View your posts ($you_posts_count)",
		'L_UNREAD_PM'		=> "$unread_pm_count new messages",
		'L_HP_LOG_OUT'		=> "Logout [$username]",
	));

}

page_header('Global Conflict - Portal');

$template->set_filenames(array(
    'body' => 'home_body.html',
));

make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"));
page_footer();
?>
