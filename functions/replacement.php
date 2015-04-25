<?php
/*Файл используется для хранения функций заменяющих стандартные функции ядра WordPress,
 * файл подключается при работе скриптов в режиме SHORTINIT*/

$path_parts = pathinfo(__FILE__);
require_once($path_parts['dirname'].'/includes.php');

if(!function_exists('esc_sql')){
    function esc_sql( $data ) {
        global $wpdb;
        return $wpdb->_escape( $data );
    }
}

function get_post($post_id){
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."posts WHERE ID='$post_id'");
}

function get_post_type($post_id){
    global $wpdb;
    return $wpdb->get_var("SELECT post_type FROM ".$wpdb->prefix."posts WHERE ID='$post_id'");
}

function get_post_meta($post_id,$key,$type=1){
    global $wpdb;
    return $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id='$post_id' AND meta_key='$key'");
}

function update_post_meta($post_id,$key,$value){
    global $wpdb;
    return $wpdb->update(
                $wpdb->prefix.'postmeta',
                array('meta_value'=>$value),
                array('meta_key'=>$key,'post_id'=>$post_id)
           );
}

function get_comment($comment_id){
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."comments WHERE comment_ID='$comment_id'");
}

function get_basedir_image_rcl($path){
	$dir = explode('/',$path);
	$cnt = count($dir) - 2;
        $base_path='';
	for($a=0;$a<=$cnt;$a++){
		$base_path .= $dir[$a].'/';
	}
	return $base_path;
}

function untrailingslashit( $string ) {
    return rtrim( $string, '/\\' );
}

function stripslashes_deep($value) {
	if ( is_array($value) ) {
		$value = array_map('stripslashes_deep', $value);
	} elseif ( is_object($value) ) {
		$vars = get_object_vars( $value );
		foreach ($vars as $key=>$data) {
			$value->{$key} = stripslashes_deep( $data );
		}
	} elseif ( is_string( $value ) ) {
		$value = stripslashes($value);
	}

	return $value;
}

function esc_textarea( $text ) {
	return $safe_text = htmlspecialchars( $text, ENT_QUOTES, get_option( 'blog_charset' ) );
}

function wp_get_attachment_image_src($post_id,$size = 'thumbnail'){
	global $wpdb;
	$metadata = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_wp_attachment_metadata' AND post_id='$post_id'");
	$meta = unserialize($metadata);
	//print_r($meta);
	if(!isset($meta['sizes'][$size])){
		$url = $meta['file'];
		$width = $meta['width'];
		$height = $meta['height'];
	}else{
		$url = get_basedir_image_rcl($meta['file']).$meta['sizes'][$size]['file'];
		$width = $meta['sizes'][$size]['width'];
		$height = $meta['sizes'][$size]['height'];
	}
	$img[] = '/wp-content/uploads/'.$url;
	$img[] = $width;
	$img[] = $height;
	return $img;
}

function wp_constrain_dimensions( $current_width, $current_height, $max_width=0, $max_height=0 ) {
	if ( !$max_width and !$max_height )
		return array( $current_width, $current_height );

	$width_ratio = $height_ratio = 1.0;
	$did_width = $did_height = false;

	if ( $max_width > 0 && $current_width > 0 && $current_width > $max_width ) {
		$width_ratio = $max_width / $current_width;
		$did_width = true;
	}

	if ( $max_height > 0 && $current_height > 0 && $current_height > $max_height ) {
		$height_ratio = $max_height / $current_height;
		$did_height = true;
	}

	// Calculate the larger/smaller ratios
	$smaller_ratio = min( $width_ratio, $height_ratio );
	$larger_ratio  = max( $width_ratio, $height_ratio );

	if ( intval( $current_width * $larger_ratio ) > $max_width || intval( $current_height * $larger_ratio ) > $max_height )
 		// The larger ratio is too big. It would result in an overflow.
		$ratio = $smaller_ratio;
	else
		// The larger ratio fits, and is likely to be a more "snug" fit.
		$ratio = $larger_ratio;

	// Very small dimensions may result in 0, 1 should be the minimum.
	$w = max ( 1, intval( $current_width  * $ratio ) );
	$h = max ( 1, intval( $current_height * $ratio ) );

	// Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117x176... a pixel short
	// We also have issues with recursive calls resulting in an ever-changing result. Constraining to the result of a constraint should yield the original result.
	// Thus we look for dimensions that are one pixel shy of the max value and bump them up
	if ( $did_width && $w == $max_width - 1 )
		$w = $max_width; // Round it up
	if ( $did_height && $h == $max_height - 1 )
		$h = $max_height; // Round it up

	return array( $w, $h );
}

if(!function_exists('custom_avatar_recall')):
    add_filter('get_avatar','custom_avatar_recall', 1, 5);
    function custom_avatar_recall($avatar, $id_or_email, $size, $default, $alt){
        if (is_numeric($id_or_email)){
            $avatar_id = get_option('avatar_user_'.$id_or_email);
            //if(!$avatar_id) $avatar_id = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix."usermeta WHERE meta_key = 'ulogin_photo' AND user_id='id_or_email'");
            if($avatar_id){
                    $image_attributes = wp_get_attachment_image_src($avatar_id);
                    $avatar = "<img class='avatar' src='".$image_attributes[0]."' alt='".$alt."' height='".$size."' width='".$size."' />";
            }
        }elseif( is_object($id_or_email)){
            $avatar_id = get_option('avatar_user_'.$id_or_email->user_id);
            if ( !empty($id_or_email->user_id) && $avatar_id ){
                    $image_attributes = wp_get_attachment_image_src($avatar_id);
                    $avatar = "<img class='avatar' src='".$image_attributes[0]."' alt='".$alt."' height='".$size."' width='".$size."' />";
            }
        }
        if ( !empty($id_or_email->user_id)) $avatar = '<a height="'.$size.'" width="'.$size.'" href="'.get_author_posts_url($id_or_email->user_id).'">'.$avatar.'</a>';

        return $avatar;
    }
endif;

function get_other_plugins_avatar( $user_id ) {
    global $active_plugins;

    if(false !== strpos($active_plugins, 'wp-social-login.php')){
        $user_avatar = get_usermeta( $user_id, 'wsl_current_user_image');
        if(!$user_avatar)$user_avatar = get_usermeta( $user_id, 'wsl_user_image');
    }
    if(false !== strpos($active_plugins, 'ulogin.php')) $user_avatar = get_usermeta( $user_id, 'ulogin_photo');

    return $user_avatar;
}

function get_avatar( $id_or_email, $size = '96', $default = '', $alt = false ) {
	global $wpdb;
	$user = 0;
	//$default = RCL_URL.'img/guest.png';

	if ( false === $alt) $safe_alt = '';
	else $safe_alt = esc_attr( $alt );

	if ( !is_numeric($size) ) $size = '96';

	$email = '';

        $id = (int) $id_or_email;
        $user_email = $wpdb->get_var("SELECT user_email FROM ".$wpdb->prefix."users WHERE ID = '$id'");

        if ( isset($user_email) ){if(isset($user_email))$email = $user_email;}


	if ( empty($default) ) {
		$avatar_default = get_option('avatar_default');
                if($avatar_default=='ulogin') $default = 'monsterid';
		else if ( empty($avatar_default) ) $default = 'mystery';
		else $default = $avatar_default;
	}

	if ( !empty($email) ) $email_hash = md5( strtolower( trim( $email ) ) );

	if ( is_ssl() ) {
		$host = 'https://secure.gravatar.com';
	} else {
		if ( !empty($email) ) $host = sprintf( "http://%d.gravatar.com", ( hexdec( $email_hash[0] ) % 2 ) );
		else $host = 'http://0.gravatar.com';
	}

	if ( 'mystery' == $default )
		$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
	elseif ( 'blank' == $default )
		$default = $email ? 'blank' : includes_url( 'images/blank.gif' );
	elseif ( !empty($email) && 'gravatar_default' == $default )
		$default = '';
	elseif ( 'gravatar_default' == $default )
		$default = "$host/avatar/?s={$size}";
	elseif ( empty($email) )
		$default = "$host/avatar/?d=$default&amp;s={$size}";
	elseif ( strpos($default, 'http://') === 0 )
		$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}";

	if ( !empty($email) ) {
		$out = "$host/avatar/";
		$out .= $email_hash;
		$out .= '?s='.$size;
		$out .= '&amp;d=' . urlencode( $default );

		$rating = get_option('avatar_rating');
		if ( !empty( $rating ) )
			$out .= "&amp;r={$rating}";

		$out = str_replace( '&#038;', '&amp;', $out );
		$avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
	} else {
		$avatar = "<img alt='{$safe_alt}' src='{$default}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
	}

        if(isset($id)) $aurl = get_other_plugins_avatar( $id );

        if(isset($aurl)){
            $avatar = "<img alt='{$safe_alt}' src='{$aurl}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        }

	return apply_filters( 'get_avatar', $avatar, $id_or_email, $size, $default, $alt );
}

function get_bloginfo($name){
    switch($name){
        case 'wpurl': return get_wpurl();
    }
}

function get_wpurl(){
	global $wpdb;
	$wpurl = $wpdb->get_var("SELECT option_value FROM ".$wpdb->prefix."options WHERE option_name = 'siteurl'");
	return $wpurl;
}

function get_the_author_meta($key,$user_id){
	global $wpdb;
	if($key=='display_name'||$key=='user_email'){
		$value = $wpdb->get_var("SELECT $key FROM ".$wpdb->prefix."users WHERE ID='$user_id'");
	}else{
		$value = get_usermeta($user_id, $key);
	}
	return $value;
}

function get_usermeta($user_id, $key){
	global $wpdb;
	$value = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix."usermeta WHERE meta_key = '$key' AND user_id='$user_id'");
	return $value;
}

function update_usermeta($user_id, $key, $value){
	global $wpdb;

	$val = get_usermeta($user_id, $key);
	if($val){
		$res = $wpdb->update( $wpdb->prefix.'usermeta',
			array('meta_value' => $value),
			array(
				'meta_key' => $key,
				'user_id' => $user_id
			)
		);
	}else{
		$res = add_usermeta($user_id, $key, $value);
	}
	return $res;
}

function add_usermeta($user_id, $key, $value){
	global $wpdb;
	$res = $wpdb->insert( $wpdb->prefix.'usermeta',
			array('meta_value' => $value,'meta_key' => $key, 'user_id' => $user_id)
		);
	return $res;
}

function delete_usermeta($user_id, $key){
	global $wpdb;
	$res = $wpdb->query("DELETE FROM ".$wpdb->prefix."usermeta WHERE user_id ='$user_id' AND meta_key='$key'");
	return $res;
}

function has_post_thumbnail($post_id){
	global $wpdb;
	$thumb_id = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_thumbnail_id' AND post_id='$post_id'");
	return $thumb_id;
}

function get_the_post_thumbnail($post_id,$size,$attr){
	global $wpdb;
	$thumb_id = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_thumbnail_id' AND post_id='$post_id'");
	$image_attr = wp_get_attachment_image_src($thumb_id,$size);
	$image = '<img src="'.$image_attr[0].'" '.$attr.'>';
	return $image;
}

function get_comments_number( $post_id ){
	global $wpdb;
	$cnt = $wpdb->get_var("SELECT COUNT(comment_ID) FROM ".$wpdb->prefix."comments WHERE comment_post_ID = '$post_id'");
	return $cnt;
}

function get_comment_link($comment_id){
	global $wpdb;
	$post_id = $wpdb->get_var("SELECT comment_post_ID FROM ".$wpdb->prefix."comments WHERE comment_ID = '$comment_id'");
	$url = '/?p='.$post_id.'#comment-'.$comment_id;
	return $url;
}

function get_author_posts_url($user_id){
	global $rcl_options;
	if($rcl_options['view_user_lk_rcl']!=1) return get_wpurl().'/?author='.$user_id;
	$get = 'user';
	if($rcl_options['link_user_lk_rcl']!='') $get = $rcl_options['link_user_lk_rcl'];
	return get_wpurl().'/?p='.$rcl_options['lk_page_rcl'].'&'.$get.'='.$user_id;
}

function wp_mail($to, $title, $mess, $headers){
	mail($to, $title, $mess, $headers);
}

function make_clickable($ret) {
	$ret = ' ' . $ret;
	// in testing, using arrays here was found to be faster
	$ret = preg_replace_callback('#(?<!=[\'"])(?<=[*\')+.,;:!&$\s>])(\()?([\w]+?://(?:[\w\\x80-\\xff\#%~/?@\[\]-]|[\'*(+.,;:!=&$](?![\b\)]|(\))?([\s]|$))|(?(1)\)(?![\s<.,;:]|$)|\)))+)#is', '_make_url_clickable_cb', $ret);
	$ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', '_make_web_ftp_clickable_cb', $ret);
	$ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $ret);
	// this one is not in an array because we need it to run last, for cleanup of accidental links within links
	$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
	$ret = trim($ret);
	return $ret;
}

function _make_url_clickable_cb($matches) {
	$url = $matches[2];
	$suffix = '';

	/** Include parentheses in the URL only if paired **/
	while ( substr_count( $url, '(' ) < substr_count( $url, ')' ) ) {
		$suffix = strrchr( $url, ')' ) . $suffix;
		$url = substr( $url, 0, strrpos( $url, ')' ) );
	}

	//$url = esc_url($url);
	if ( empty($url) )
		return $matches[0];

	return $matches[1] . "<a href=\"$url\" rel=\"nofollow\">$url</a>" . $suffix;
}

function _make_web_ftp_clickable_cb($matches) {
	$ret = '';
	$dest = $matches[2];
	$dest = 'http://' . $dest;
	//$dest = esc_url($dest);
	if ( empty($dest) )
		return $matches[0];

	// removed trailing [.,;:)] from URL
	if ( in_array( substr($dest, -1), array('.', ',', ';', ':', ')') ) === true ) {
		$ret = substr($dest, -1);
		$dest = substr($dest, 0, strlen($dest)-1);
	}
	return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\">$dest</a>$ret";
}

function _make_email_clickable_cb($matches) {
	$email = $matches[2] . '@' . $matches[3];
	return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
}
function popuplinks($text) {
	$text = preg_replace('/<a (.+?)>/i', "<a $1 target='_blank' rel='external'>", $text);
	return $text;
}

/*Замена функции из дополнения MW*/
function add_wallet_history_row($user_id,$count,$comment,$type){
	global $wpdb;
	$time_action = current_time('mysql');

	$wpdb->insert(
		RCL_PREF.'wallet_history',
		array( 'user_id' => $user_id, 'count_pay' => $count, 'comment_pay' => $comment, 'time_pay' => $time_action, 'type_pay' => $type)
	);
}

function get_permalink($post_id){
	return get_wpurl().'/?p='.$post_id;
}