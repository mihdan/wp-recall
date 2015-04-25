<?php
if(is_admin()) require_once("admin-pages.php");
require_once("functions/tabs_options.php");
require_once("functions/minify-files/minify-css.php");
require_once("widget.php");
require_once("functions/shortcodes.php");
require_once("functions/rcl_addons.php");
require_once('functions/includes.php');
require_once('functions/navi-rcl.php');
require_once('functions/recallbar.php');
require_once('functions/rcl_custom_fields.php');
require_once('functions/register.php');
require_once('functions/authorize.php');
require_once('functions/loginform.php');

if(class_exists('ReallySimpleCaptcha')){
    require_once('functions/captcha.php');
}

require_once('functions/enqueue-scripts.php');

//добавляем вкладку со списком публикаций хозяина ЛК указанного типа записей в личный кабинет
function add_postlist_rcl($id,$posttype,$name='',$args=false){
    global $rcl_options;
    if(!$rcl_options) $rcl_options = get_option('primary-rcl-options');
    if($rcl_options['publics_block_rcl']!=1) return false;
    if (!class_exists('Rcl_Postlist')) include_once plugin_dir_path( __FILE__ ).'add-on/publicpost/rcl_postlist.php';
    $plist = new Rcl_Postlist($id,$posttype,$name,$args);
}
//добавляем контентный блок в указанное место личного кабинета
function add_block_rcl($place,$callback,$args=false){
    if(is_admin())return false;
    if (!class_exists('Rcl_Blocks')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_blocks.php';
    $block = new Rcl_Blocks($place,$callback,$args);
}
//добавляем уведомление в личном кабинете
function add_notify_rcl($text,$type='warning'){
    if(is_admin())return false;
    if (!class_exists('Rcl_Notify')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_notify.php';
    $block = new Rcl_Notify($text,$type);
}
//добавляем вкладку в личный кабинет
function add_tab_rcl($id,$callback,$name='',$args=false){

    $data = array(
        'id'=>$id,
        'callback'=>$callback,
        'name'=>$name,
        'args'=>$args
    );

    $data = apply_filters('tab_data_rcl',$data);

    if(is_admin())return false;

    if (!class_exists('Rcl_Tabs')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_tabs.php';

    $tab = new Rcl_Tabs($data);
}

//формируем массив данных о вкладках личного кабинета
if(is_admin()) add_filter('tab_data_rcl','get_data_tab_rcl',10);
function get_data_tab_rcl($data){
    global $tabs_rcl;
    $tabs_rcl[$data['id']] = $data;
    return $data;
}

function rcl_notify(){
    $notify = '';
    $notify = apply_filters('notify_lk',$notify);
    if($notify) echo '<div class="notify-lk">'.$notify.'</div>';
}

function get_template_rcl($file_temp,$path=false){
    $dirs   = array(
        TEMPLATEPATH.'/recall/templates/',
        RCL_PATH.'templates/'
    );

    if($path) $dirs[1] = addon_path($path).'templates/';

    foreach($dirs as $dir){
        if(!file_exists($dir.$file_temp)) continue;
        return $dir.$file_temp;
        break;
    }
    return false;
}

function include_template_rcl($file_temp,$path=false){
    include get_template_rcl($file_temp,$path);
}

function get_include_template_rcl($file_temp,$path=false){
    ob_start();
    include_template_rcl($file_temp,$path);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

add_action('wp_ajax_rcl_ajax_tab', 'rcl_ajax_tab');
add_action('wp_ajax_nopriv_rcl_ajax_tab', 'rcl_ajax_tab');
function rcl_ajax_tab(){
    global $wpdb,$array_tabs,$user_LK,$rcl_userlk_action;
    $id_tab = $_POST['id'];
    $func = $array_tabs[$id_tab];
    $user_LK = $_POST['lk'];
    if(!$rcl_userlk_action) $rcl_userlk_action = $wpdb->get_var("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='$user_LK'");
    if (!class_exists('Rcl_Tabs')) include_once plugin_dir_path( __FILE__ ).'functions/rcl_tabs.php';
    if(!$array_tabs[$id_tab]){
        $log['content']=__('Error! Perhaps this addition does not support ajax loading','rcl');
    }else{
        $data = array(
            'id'=>$id_tab,
            'callback'=>$func,
            'name'=>false,
            'args'=>array('public'=>1)
        );
        $tab = new Rcl_Tabs($data);
        $log['content']=$tab->add_tab('',$_POST['lk']);
    }

    $log['result']=100;
    echo json_encode($log);
    exit;
}
add_action('init','init_ajax_tabs');
function init_ajax_tabs(){
        global $array_tabs;
        $id_tabs = '';
	$array_tabs = apply_filters( 'ajax_tabs_rcl', $id_tabs );
	return $array_tabs;
}
function get_key_addon_rcl($path_parts){
    if(!isset($path_parts['dirname'])) return false;
    $key = false;
    $ar_dir = explode('/',$path_parts['dirname']);
    if(!isset($ar_dir[1])) $ar_dir = explode('\\',$path_parts['dirname']);
    $cnt = count($ar_dir)-1;
    for($a=$cnt;$a>=0;$a--){if($ar_dir[$a]=='add-on'){$key=$ar_dir[$a+1];break;}}
    return $key;
}

function get_wp_uploads_dir(){
    if(defined( 'MULTISITE' )){
        $upload_dir = array(
                'basedir' => WP_CONTENT_DIR.'/uploads',
                'baseurl' => WP_CONTENT_URL.'/uploads'
        );
    }else{
        $upload_dir = wp_upload_dir();
    }
    return $upload_dir;
}

function update_dinamic_files_rcl(){
    //include('class_addons.php');
    $rcl_addons = new rcl_addons();
    $rcl_addons->get_update_scripts_file_rcl();
    $rcl_addons->get_update_scripts_footer_rcl();
    minify_style_rcl();
}
function add_user_data_rcl(){
    global $user_ID;
    $data = "<script>
	var user_ID = $user_ID;
	var wpurl = '".preg_quote(trailingslashit(get_bloginfo('wpurl')),'/:')."';
	var rcl_url = '".preg_quote(RCL_URL,'/:')."';
	</script>";
    echo $data;
}
add_action('wp_head','add_user_data_rcl');
function add_popup_contayner_rcl(){
    $popup = '<div id="rcl-overlay"></div><div id="rcl-popup"></div>';
    echo $popup;
}
add_action('wp_footer','add_popup_contayner_rcl');
add_filter('wp_footer', 'add_footer_url_recall');
function add_footer_url_recall(){
	global $rcl_options;
	if($rcl_options['footer_url_recall']!=1) return false;
	if(is_front_page()&&!is_user_logged_in()) echo '<p class="plugin-info">'.__('The site works using the functionality of the plugin').'  <a target="_blank" href="http://wppost.ru/">Wp-Recall</a></p>';
}

function delete_user_action_rcl($user){
	global  $wpdb;
	$wpdb->query("DELETE FROM ".RCL_PREF."user_action WHERE user = '$user'");
}
add_action('delete_user','delete_user_action_rcl');

add_filter('rcl_posthead_user','get_author_name_rcl',10,2);
function get_author_name_rcl($content,$user_id){
	$content .= "<h3>Автор: <a href='".get_author_posts_url($user_id)."'>".get_the_author_meta( 'display_name', $user_id )."</a></h3>".get_miniaction_user_rcl(false,$user_id);
	return $content;
}
function get_author_block_content_rcl(){
    global $post;
    $author = $post->post_author;

    $karma = apply_filters('get_all_rayt_user_rcl',$karma,$author);

    $out = "<div id='block_author-rcl'>
        <div class='avatar-author'>".get_avatar($author,60);
        if(function_exists('get_rayting_block_rcl')) $out .= get_rayting_block_rcl($karma);
        $out .= "</div>
        <div class='content-author-block'>";
                $head = apply_filters('rcl_posthead_user',$head,$author);
                $out .= $head;
                $desc = apply_filters('rcl_postdesc_user',$desc,$author);
                $out .= $desc;
                $footer = apply_filters('rcl_postfooter_user',$footer,$author);
                if($footer) $out .= '<div class="footer-author">'.$footer.'</div>';
        $out .= "</div>
        </div>";
    return $out;
}
function get_miniaction_user_rcl($action,$user_id=false){
    global $wpdb;
    if(!$action) $action = $wpdb->get_var("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='$user_id'");
    $last_action = last_user_action_recall($action);
    $class = (!$last_action&&$action)?'online':'offline';

    $content = '<div class="status_author_mess '.$class.'">';
    if(!$last_action&&$action) $content .= '<i class="fa fa-circle"></i>';
    else $content .= 'не в сети '.$last_action;
    $content .= '</div>';

    return $content;
}

//заменяем ссылку автора комментария на ссылку его ЛК
function add_link_author_in_page($href){
	global $comment;
	if($comment->user_id==0) return $href;
	$href = get_author_posts_url($comment->user_id);
	return $href;
}
function rcl_add_edit_post_button($excerpt,$post=null){
if(!isset($post)) global $post;
global $user_ID;
	if($user_ID){
		if($user_ID==$post->post_author){
			$form_button = "<div class='post-edit-button'>
				<input id='delete-post' type='image' name='delete_post' src='".RCL_URL."img/delete.png' value='".$post->ID."'></div>
				<div class='post-edit-button'>
				<input type='image' id='edit-post' name='update_post' src='".RCL_URL."img/redactor.png' value='".$post->ID."'></div>";
		}

		$form_button = apply_filters('buttons_edit_post_rcl',$form_button,$post);

		if($form_button) $excerpt .= $form_button;
	}
	return $excerpt;
}
//запрещаем доступ в админку
add_action('init','wp_admin_success_rcl',1);
function wp_admin_success_rcl(){
	global $current_user,$rcl_options;
	if(defined( 'DOING_AJAX' ) && DOING_AJAX) return;
	if(defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST) return;
	if(is_admin()){
		$rcl_options = get_option('primary-rcl-options');
		get_currentuserinfo();
		$access = 7;
		if(isset($rcl_options['consol_access_rcl'])) $access = $rcl_options['consol_access_rcl'];
		$user_info = get_userdata($current_user->ID);
		if ( $user_info->user_level < $access ){
			if($_POST['short']==1||$_POST['fetch']==1){
				return true;
			}else{
				if(!$current_user->ID) return true;
				wp_redirect('/'); exit;
			}
		}else {
			return true;
		}
	}
}
function hidden_admin_panel(){
	global $current_user,$rcl_options;
	get_currentuserinfo();
	$access = 7;
	if(isset($rcl_options['consol_access_rcl'])) $access = $rcl_options['consol_access_rcl'];
	$user_info = get_userdata($current_user->ID);
	if ( $user_info->user_level < $access ){
		show_admin_bar(false);
	}else{
		return true;
	}
}
function get_banned_user_redirect(){
    global $user_ID;
    if(!$user_ID) return false;
    $user_data = get_userdata( $user_ID );
    $roles = $user_data->roles;
    $role = array_shift($roles);
    if($role=='banned') wp_die(__('Congratulations! You have been banned.','rcl'));
}
add_action('init','get_banned_user_redirect');
/* Удаление поста вместе с его вложениями*/
function delete_attachments_with_post_rcl($postid){
    $attachments = get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => null, 'post_parent' => $postid ) );
    if($attachments){
	foreach((array)$attachments as $attachment ){
        wp_delete_attachment( $attachment->ID, true ); }
	}
}
//Функция вывода своего аватара
function custom_avatar_recall($avatar, $id_or_email, $size, $default, $alt){
    if (is_numeric($id_or_email)){
            $user_id = $id_or_email;
    }elseif( is_object($id_or_email)){
            $user_id = $id_or_email->user_id;
    }

    $avatar_id = get_option('avatar_user_'.$user_id);
    if ( $avatar_id ){
            $image_attributes = wp_get_attachment_image_src($avatar_id);
            if($image_attributes) $avatar = "<img class='avatar' src='".$image_attributes[0]."' alt='".$alt."' height='".$size."' width='".$size."' />";
    }

    if ( !empty($id_or_email->user_id)) $avatar = '<a height="'.$size.'" width="'.$size.'" href="'.get_author_posts_url($id_or_email->user_id).'">'.$avatar.'</a>';

    return $avatar;
}

function sanitize_title_with_translit_recall($title) {
    $gost = array(
        "Є"=>"EH","І"=>"I","і"=>"i","№"=>"#","є"=>"eh",
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
        "Е"=>"E","Ё"=>"JO","Ж"=>"ZH",
        "З"=>"Z","И"=>"I","Й"=>"JJ","К"=>"K","Л"=>"L",
        "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
        "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"KH",
        "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
        "Ы"=>"Y","Ь"=>"","Э"=>"EH","Ю"=>"YU","Я"=>"YA",
        "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
        "е"=>"e","ё"=>"jo","ж"=>"zh",
        "з"=>"z","и"=>"i","й"=>"jj","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh",
        "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
        "ы"=>"y","ь"=>"","э"=>"eh","ю"=>"yu","я"=>"ya",
        "—"=>"-","«"=>"","»"=>"","…"=>""
    );
    $iso = array(
        "Є"=>"YE","І"=>"I","Ѓ"=>"G","і"=>"i","№"=>"#","є"=>"ye","ѓ"=>"g",
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
        "Е"=>"E","Ё"=>"YO","Ж"=>"ZH",
        "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L",
        "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
        "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"X",
        "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
        "Ы"=>"Y","Ь"=>"","Э"=>"E","Ю"=>"YU","Я"=>"YA",
        "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
        "е"=>"e","ё"=>"yo","ж"=>"zh",
        "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"x",
        "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
        "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
        "—"=>"-","«"=>"","»"=>"","…"=>""
    );

    $rtl_standard = get_option('rtl_standard');

    switch ($rtl_standard) {
            case 'off':
                return $title;
            case 'gost':
                return strtr($title, $gost);
            default:
                return strtr($title, $iso);
    }
}
if(!function_exists('sanitize_title_with_translit')) add_action('sanitize_title', 'sanitize_title_with_translit_recall', 0);
add_filter('the_content','add_message_post_moderation_rcl');
function add_message_post_moderation_rcl($cont){
global $post;
	if($post->post_status=='pending'){
		$mess = '<h3 class="pending-message">'.__('Publication pending approval!','rcl').'</h3>';
		$cont = $mess.$cont;
	}
	return $cont;
}
function get_custom_post_meta_rcl($post_id){
	if($post_id){
            $post = get_post($post_id);
            $posttype = $post->post_type;
        }

	switch($posttype){
		case 'post':
			if($post) $id_form = get_post_meta($post->ID,'publicform-id',1);
			if(!$id_form) $id_form = 1;
			$id_field = 'custom_public_fields_'.$id_form;
		break;
		case 'products': $id_field = 'custom_saleform_fields'; break;
		default: $id_field = 'custom_fields_'.$posttype;
	}

	$get_fields = get_option($id_field);

	if(!$get_fields) return false;

	if($get_fields){

            $cf = new Rcl_Custom_Fields();

            foreach((array)$get_fields as $custom_field){
                $slug = $custom_field['slug'];
                $value = get_post_meta($post_id,$slug,1);
                $show_custom_field .= $cf->get_field_value($custom_field,$value);
            }

            return $show_custom_field;
	}
}
add_filter('author_link','edit_author_link_rcl',99,2);
function edit_author_link_rcl($link, $author_id){
	global $rcl_options;
	if($rcl_options['view_user_lk_rcl']!=1) return $link;
	$get = ! empty( $rcl_options['link_user_lk_rcl'] ) ? $rcl_options['link_user_lk_rcl'] : 'user';
	return add_query_arg( array( $get => $author_id ), get_permalink( $rcl_options['lk_page_rcl'] ) );
	//return get_redirect_url_rcl( get_permalink( $rcl_options['lk_page_rcl'] ) ).$get.'='.$author_id;
}
function get_userfield_array_rcl($array,$field,$name_data){
	global $wpdb;
        $a=0;
	foreach((array)$array as $object){
            if(++$a>1)$userslst .= ',';
            if(is_object($array))$userslst .= $object->$name_data;
                    if(is_array($array))$userslst .= $object[$name_data];
        }

	$users_fields = $wpdb->get_results("SELECT user_id,meta_value FROM ".$wpdb->prefix."usermeta WHERE user_id IN ($userslst) AND meta_key = '$field'");

	foreach((array)$users_fields as $user){
		$fields[$user->user_id] = $user->$field;
	}
	return $fields;
}

function global_recall_wpm_options(){
	$content .= ' <div id="recall" class="left-sidebar wrap">
	<form method="post" action="">
		'.wp_nonce_field('update-options-rmag','_wpnonce',true,false);

	$content = apply_filters('admin_options_rmag',$content);

	$content .= '<div class="submit-block">
	<p><input type="submit" class="button button-primary button-large right" name="primary-rmag-options" value="'.__('Save settings','rcl').'" /></p>
	</form></div>
	</div>';
	echo $content;
}
function update_options_rmag_activate ( ) {
  if ( isset( $_POST['primary-rmag-options'] ) ) {
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rmag' ) ) return false;
    foreach($_POST as $key => $value){
		if($key=='primary-rmag-options') continue;
		$options[$key]=$value;
	}
	update_option('primary-rmag-options',$options);
	wp_redirect(get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-wpm-options');
	exit;
  }
}
add_action('init', 'update_options_rmag_activate');

function get_postmetas($post_id){
    global $wpdb;
    $mts = array();
    $metas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."postmeta WHERE post_id='$post_id'");
    if(!$metas) return false;
    foreach($metas as $meta){
        $mts[$meta->meta_key] = $meta->meta_value;
    }
    //print_r($mts);exit;
    return $mts;
}

function setup_chartdata($mysqltime,$data){
    global $chartArgs;
    $day = date("j", strtotime($mysqltime));
    $price = $data/1000;
    $month = date("n", strtotime($mysqltime));
    $chartArgs[$month][$day]['summ'] += $price;
    $chartArgs[$month]['summ'] += $price;
    $chartArgs[$month][$day]['cnt'] += 1;
    $chartArgs[$month]['cnt'] += 1;
    $chartArgs[$month]['days'] = date("t", strtotime($mysqltime));
    return $chartArgs;
}

function get_chart_rcl($arr=false){
    global $chartData;

    if(!$arr) return false;

    if(count($arr)==1){
        foreach($arr as $month=>$data){
            for($a=1;$a<=$data['days'];$a++){
                $cnt = (isset($data[$a]['cnt']))?$data[$a]['cnt']:0;
                $summ = (isset($data[$a]['summ']))?$data[$a]['summ']:0;
                $chartData['data'][] = array($a, $cnt,$summ);
            }
        }
    }else{
        for($a=1;$a<=12;$a++){
            $cnt = (isset($arr[$a]['cnt']))?$arr[$a]['cnt']:0;
            $summ = (isset($arr[$a]['summ']))?$arr[$a]['summ']:0;
            $chartData['data'][] = array($a, $cnt,$summ);
        }
    }

    if(!$chartData) return false;

    return get_include_template_rcl('chart.php');
}

add_filter('file_scripts_rcl','get_scripts_ajaxload_tabs_rcl');
function get_scripts_ajaxload_tabs_rcl($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";

	$script .= "
	function setAttr_rcl(prmName,val){
		var res = '';
		var d = location.href.split('#')[0].split('?');
		var base = d[0];
		var query = d[1];
		if(query) {
			var params = query.split('&');
			for(var i = 0; i < params.length; i++) {
				var keyval = params[i].split('=');
				if(keyval[0] != prmName) {
					res += params[i] + '&';
				}
			}
		}
		res += prmName + '=' + val;
		return base + '?' + res;
	}
	function get_ajax_content_tab(id){
		var lk = parseInt(jQuery('.wprecallblock').attr('id').replace(/\D+/g,''));
		var dataString = 'action=rcl_ajax_tab&id='+id+'&lk='+lk+'&locale='+jQuery('html').attr('lang');
		jQuery.ajax({
			".$ajaxdata."
			success: function(data){
				if(data['result']==100){
					jQuery('#lk-content').html(data['content']);
				} else {
					alert('Error');
				}
			}
		});
		return false;
	}
	jQuery('.ajax_button').live('click',function(){
		if(jQuery(this).hasClass('active'))return false;
		jQuery('#lk-content').html('<img class=preloader src='+rcl_url+'css/img/loader.gif>');
		var id = jQuery(this).attr('id');
		jQuery('#lk-menu > a').removeClass('active');
		jQuery(this).addClass('active');
		var url = setAttr_rcl('view',id);
		if(url != window.location){
			if ( history.pushState ){
				window.history.pushState(null, null, url);
			}
		}
		get_ajax_content_tab(id);
		return false;
	});
	";
	return $script;
}
function update_options_data_rcl($rcl_options){
    global $wpdb;
    if(isset($rcl_options)&&!is_array($rcl_options)){

    $options = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."options WHERE option_name IN ("
                    . "'custom_orders_field',"
                    . "'custom_profile_field',"
                    . "'custom_profile_search_form',"
                    . "'custom_public_fields_1',"
                    . "'custom_saleform_fields',"
                    . "'primary-rcl-options',"
                    . "'active_addons_recall'"
                    . ")");
        if($options){
            foreach($options as $opt){
                    $val = '';
                    $s2 = substr($opt->option_value,0,2);
                    if($s2=='s:'){
                            $val = str_replace('-','_',unserialize(get_option($opt->option_name)));
                            update_option($opt->option_name,$val);
                    }
            }
        }
        $rcl_options = get_option('primary-rcl-options');
    }

    return $rcl_options;
}