<?php
require_once 'core.php';
require_once 'addon-options.php';

if(function_exists('rcl_enqueue_style')) rcl_enqueue_style('rayt',__FILE__);

if (is_admin()):
	add_action('admin_head','output_script_admin_rayt_recall');
endif;

function output_script_admin_rayt_recall(){
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'ajax_admin_rayt_recall', plugins_url('js/admin.js', __FILE__) );
}

add_filter('array_rayt_chek','add_rayt_array_rayting_rcl');
function add_rayt_array_rayting_rcl($array){
	global $rcl_options;
	if(isset($rcl_options['rayt_comment'])) $array['rayt_comment'] = $rcl_options['rayt_comment'];
	if(isset($rcl_options['rayt_post'])) $array['rayt_post'] = $rcl_options['rayt_post'];
	return $array;
}

//Получаем значение рейтинга указанного пользователя
function get_user_rayting_rcl($user_id){
    return apply_filters('get_all_rayt_user_rcl',0,$user_id);
}

//Получаем код блока рейтинга
function get_rayting_block_rcl($rayt){
        $array = '';
	$array = apply_filters('array_rayt_chek',$array);
	foreach($array as $ar){
		if($ar==1) return '<span title="'.__('rating','rcl').'" class="rayting-rcl">'.raytout($rayt).'</span>';
	}
	return false;
}

function raytout($rayt){
    if($rayt>0){$class="rayt-plus";$rayt='+'.$rayt;}
    elseif($rayt<0)$class="rayt-minus";
    else{$class="null";$rayt=0;}
    return '<span class="'.$class.'">'.$rayt.'</span>';
}

add_filter('post_options_rcl','post_rayting_options',10,2);
function post_rayting_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'rayting-none', 1);
    $options .= '<p>'.__('To disable the rating for publication','rcl').':
        <label><input type="radio" name="wprecall[rayting-none]" value="" '.checked( $mark_v, '',false ).' />'.__('No','rcl').'</label>
        <label><input type="radio" name="wprecall[rayting-none]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','rcl').'</label>
    </p>';
    return $options;
}

if(function_exists('add_block_rcl')) add_block_rcl('sidebar','get_content_rayting_rcl',array('id'=>'rt-block','order'=>2));
function get_content_rayting_rcl($author_lk){
	global $user_ID;

        $karma = get_user_rayting_rcl($author_lk);
        $rayt_user = get_rayting_block_rcl($karma);

        if($karma)	{
                if($user_ID) $content_lk = '<a href="#" id="rayt-user-'.$author_lk.'" class="all-rayt-user">'.$rayt_user.'</a>';
                else $content_lk = $rayt_user;
        } else {
                $content_lk = $rayt_user;
        }
	return $content_lk;
}

function user_rayting_admin_column( $columns ){
	return array_merge( $columns,array( 'user_rayting_admin' => "Рейтинг" ));
}
add_filter( 'manage_users_columns', 'user_rayting_admin_column' );

function user_rayting_admin_content( $custom_column, $column_name, $user_id ){
	  switch( $column_name ){
		case 'user_rayting_admin':
			$custom_column = '<input type="text" class="raytinguser-'.$user_id.'" size="4" value="'.get_all_rayt_user(0,$user_id).'">
			<input type="button" class="recall-button edit_rayting" id="user-'.$user_id.'" value="'.__('OK','rcl').'">';
		break;
	  }
	  return $custom_column;
}
add_filter( 'manage_users_custom_column', 'user_rayting_admin_content', 10, 3 );

function edit_rayting_user_recall(){
	global $wpdb;
	$user = esc_sql($_POST['user']);
	$rayting = esc_sql($_POST['rayting']);

	if(isset($_POST['rayting'])){

		$allrayt = $wpdb->get_var("SELECT total FROM ".RCL_PREF."total_rayting_users WHERE user_id='$user'");

		if(isset($allrayt))
                    $wpdb->update(RCL_PREF.'total_rayting_users', array( 'total' => $rayting ), array( 'user_id' => $user ));
		else
                    $wpdb->insert( RCL_PREF.'total_rayting_users', array( 'user_id' => $user, 'total' => $rayting ));

		$log['otvet']=100;

	}else {
		$log['otvet']=1;
	}
	echo json_encode($log);
    exit;
}
if(is_admin()) add_action('wp_ajax_edit_rayting_user_recall', 'edit_rayting_user_recall');

//Выводим код рейтинга комментария в тексте комментария
add_filter('comment_text', 'add_rayting_comment');
function add_rayting_comment($text,$comment=null){
    global $rcl_options;
    if($rcl_options['rayt_comment_recall']!=1) return $text;
    if(!isset($comment)) global $comment;

    $text .= get_rayting_block_comment($comment->comment_ID);

    return $text;
}

//Получаем блок рейтинга заданного комментария
function get_rayting_block_comment($comment_id){
    global $user_ID,$wpdb,$rcl_options,$access_to_voting;

    $comment = get_comment($comment_id);

    $rcl_comments_rayt = $wpdb->get_results("SELECT user,rayting FROM ".RCL_PREF."rayting_comments WHERE comment_id = '".$comment->comment_ID."' ORDER BY ID DESC");
    $sum_rayt = null;
    foreach((array)$rcl_comments_rayt as $val){
        $sum_rayt = $sum_rayt + $val->rayting;
    }

    $access_to_voting = false;
    foreach((array)$rcl_comments_rayt as $val){
        if($val->user==$user_ID){ $access_to_voting = true; break; }
    }

    $html = get_html_rayting_comment($comment,$sum_rayt);

    return $html;
}

//Формируем хтмл код блока рейтинга комментария по переданному объекту комментария и значению рейтинга
function get_html_rayting_comment($comment,$sum_rayt=null){
    global $user_ID,$rcl_options,$access_to_voting;

    $vote_results = '';

    if(isset($sum_rayt)&&$user_ID) $vote_results = '<div title="'.__('view messeges','rcl').'" id="vote-results-'.$comment->comment_ID.'" class="fa fa-question-circle vote-results"></div>';

    if(!isset($sum_rayt)) $sum_rayt = 0;

    $html = '<div id="com-'.$comment->comment_ID.'" class="comment-rayt">';
    if($access_to_voting) $html .= '<a href="#" class="cancel-rayt-rcl floatright" id="cancel-rayt-'.$comment->comment_ID.'" data="comment">'.__('To remove your vote','rcl').'</a>';
    $html .= '<div class="rayt-res">'
                . '<span>'
                    . __('Rating','rcl').': <span id="com-karma-'.$comment->comment_ID.'">'.raytout($sum_rayt).'</span>'
                . '</span>'
                . ''.$vote_results.''
            . '</div>';

    if($access_to_voting == false&&$comment->user_id!=$user_ID){
            $rt = rayt_pow($comment->comment_ID,$rcl_options['count_rayt_comment']);
            if($rcl_options['type_rayt_comment']==1) $html .= '<div data="'.$rt['plus'].'" class="like_rayt rayt" title="'.__('I like','rcl').'"><i class="fa fa-thumbs-o-up"></i></div>';
            else $html .= '<div data="'.$rt['minus'].'" class="fa fa-minus-square-o minus_rayt rayt" title="'.__('minus','rcl').'"></div>
            <div data="'.$rt['plus'].'" class="fa fa-plus-square-o plus_rayt rayt" title="'.__('plus','rcl').'"></div>';
    }

    $html .= '</div>';

    return $html;
}

function rayt_pow($id_post,$count_rayt=1){
    if(!$count_rayt) $count_rayt=1;
    $id_rayt_plus = $id_post + $count_rayt;
    $id_rayt_plus = pow($id_rayt_plus, 2);
    $id_rayt_minus = $id_post - $count_rayt;
    $id_rayt_minus = pow($id_rayt_minus, 2);
    return array('plus'=>$id_rayt_plus,'minus'=>$id_rayt_minus);
}

//Выводим инфу о рейтинге поста в кратком описании
add_filter('the_excerpt', 'excerpt_rayt_posts');
function excerpt_rayt_posts($excerpt){
	global $rcl_options,$post;
	if($rcl_options['rayt_post_recall']!=1) return $excerpt;

	if($post->post_type=='products') return $excerpt;

	$total = get_post_total_rayting($post->ID);

	if (!$total)$total = 0;

	$excerpt .= "<div class=rayt-sistem-post>"
                    . "<div class='post-rayt-title'>"
                        . "<span class='post-rayt-content'>"
                            . __("Rating",'rcl').": <span class='rayt'>".raytout($total)."</span>"
                        . "</span>"
                    . "</div>"
                . "</div>";

	return $excerpt;
}

//Выводим код рейтинга публикации после контента публикации
add_filter('the_content', 'rcl_post_rayting');
function rcl_post_rayting($content,$post=null){
	global $rcl_options,$wp_query, $wpdb,$user_ID;

	if($rcl_options['rayt_post_recall']!=1) return $content;
	if(!isset($post))global $post;
	if($wp_query->is_archive||$post->post_type=='page'||$post->post_type=='products') return $content;
        if(get_post_meta($post->ID, 'rayting-none', 1)) return $content;

	$content .= get_rayting_block_post($post->ID);

	return $content;
}

//Получаем код рейтинга указанной публикации
function get_rayting_block_post($post_id){
    $post = get_post($post_id);
    $karma_post = get_post_total_rayting($post->ID);
    return get_html_post_rayting($post,$karma_post);
}

//Формируем хтмл код блока рейтинга публикации по переданному объекту комментария и значению рейтинга
function get_html_post_rayting($post,$post_karma=null){
    global $user_ID,$access_to_voting,$rcl_options,$wpdb;

    if($post_karma&&$user_ID) $vote_results = '<div id="vote-post-results-'.$post->ID.'" title="'.__('view messeges','rcl').'" class="fa fa-question-circle vote-post-results"></div>';

    if (!isset($post_karma))$post_karma = 0;
    $access_to_voting = false;
    if($user_ID&&$user_ID != $post->post_author){
        $vote = $wpdb->get_var("SELECT * FROM ".RCL_PREF."rayting_post WHERE post = '$post->ID' AND user = '$user_ID'");
        if(!$vote) $access_to_voting = true;
        else $delvote = '<a href="#" class="cancel-rayt-rcl floatright" id="cancel-rayt-'.$post->ID.'" data="post">'.__('To remove your vote','rcl').'</a>';
    }

    if($access_to_voting){

        $count_rayt = $rcl_options['count_rayt_'.$post->post_type];
        $rt = rayt_pow($post->ID,$rcl_options['count_rayt_'.$post->post_type]);

        $html = '<div class="rayt-sistem-post">'
                    . '<div id="post-'.$post->ID.'" class="post-rayt">'
                    . '<div class="rayt-res">'
                        . '<span class="post-rayt-content">'
                            . __('Rating','rcl').': <span id="post-karma-'.$post->ID.'">'.raytout($post_karma).'</span>'
                        . '</span>';
                    if(isset($vote_results)) $html .= $vote_results;
                    $html .= '</div>';

            if($rcl_options['type_rayt_post']==1) $html .= '<div data="'.$rt['plus'].'" class="like_rayt raytpost" title="'.__('I like','rcl').'"><i class="fa fa-thumbs-o-up"></i></div>';
            else $html .= '<div data="'.$rt['minus'].'" class="fa fa-minus-square-o minus_rayt raytpost" title="'.__('minus','rcl').'"></div>'
                    . '<div data="'.$rt['plus'].'" class="fa fa-plus-square-o plus_rayt raytpost" title="'.__('plus','rcl').'"></div>';

            $html .= '</div>'
                . '</div>';

    } else {

        $html = '';
        if(isset($delvote)) $html .= $delvote;
        $html .= "<div class=rayt-sistem-post>"
                . "<div class='post-rayt-title'>"
                    . "<span class='post-rayt-content'>"
                        . __("Rating",'rcl').": <span id='post-karma-".$post->ID."' class='rayt'>".raytout($post_karma)."</span>"
                    . "</span>";
                   if(isset($vote_results)) $html .= $vote_results;
                $html .= "</div>"
            ."</div>";
    }
	return $html;
}

add_filter('get_all_rayt_user_rcl','get_all_rayt_user',10,2);
function get_all_rayt_user($karma,$user_id){
    global $wpdb;
    $karma_all = get_user_rayting($user_id);
    $karma+=$karma_all;
    return $karma;
}

function scripts_rayt_rcl($script){

	$ajaxrayt = "type: 'POST', data: dataString, dataType: 'json', url: rcl_url+'add-on/rayting/ajax-rayt.php',";

	$script .= "
	var rcl_ray_comment;
	var rcl_ray_post;
	/* Рейтинг комментария */
		jQuery('.rayt').live('click',function(){
			var com = jQuery(this).parent().attr('id');
			if(rcl_ray_comment==com) return false;
			rcl_ray_comment = com;
			var id_rayt = jQuery(this).attr('data');

			var dataString = 'action=add_rayting_comment_recall&com='+com+'&id_rayt='+id_rayt+'&user_ID='+user_ID;
			jQuery.ajax({
				".$ajaxrayt."
				success: function(data){
					if(data['otvet']==100){
						var com_karma = jQuery('#com-karma-'+data['com']).text();
						jQuery('#com-'+data['com']+' .rayt').remove();
						com_karma = parseInt(com_karma) + parseInt(data['rayt']);
						jQuery('#com-karma-'+data['com']).html(com_karma);
					}else{
						alert('Вы не можете голосовать!');
					}
				}
			});
			return false;
		});
	/* Рейтинг поста */
		jQuery('.raytpost').live('click',function(){
				var post = jQuery(this).parent().attr('id');
				if(rcl_ray_post==post) return false;
				rcl_ray_post = post;
				var id_rayt = jQuery(this).attr('data');
				var dataString = 'action=add_post_rayting_recall&post='+post+'&id_rayt='+id_rayt+'&user_ID='+user_ID;
				jQuery.ajax({
					".$ajaxrayt."
					success: function(data){
						if(data['otvet']==100){
							var post_karma = jQuery('#post-karma-'+data['post']).text();
							jQuery('#post-'+data['post']+' .raytpost').remove();
							post_karma = parseInt(post_karma) + parseInt(data['rayt']);
							jQuery('#post-karma-'+data['post']).html(post_karma);
						}else if(data['otvet']==120){
							alert(data['message']);
						}else{
							alert('Вы не можете проголосовать!');
						}
					}
				});
				return false;
			});
	/* Получаем голоса за комментарий */
		jQuery('.vote-results').live('click',function(){
			var id_com = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
			var dataString = 'action=get_vote_comment_recall&id_com='+id_com;
			jQuery.ajax({
				".$ajaxrayt."
				success: function(data){
					if(data['otvet']==100){
						jQuery('#vote-results-'+data['id_com']).after(data['votes']);
						jQuery('#votes-comment-'+data['id_com']).slideDown(data['votes']);
					} else {
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
	/* Получаем голоса за пост */
		jQuery('.vote-post-results').live('click',function(){
			var id_post = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
			var dataString = 'action=get_vote_post_recall&id_post='+id_post;
			jQuery.ajax({
				".$ajaxrayt."
				success: function(data){
					if(data['otvet']==100){
						jQuery('#vote-post-results-'+data['id_post']).after(data['votes']);
						jQuery('#votes-post-'+data['id_post']).slideDown(data['votes']);
					} else {
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
	/* Получаем голоса общего рейтинга пользователя */
		jQuery('.all-rayt-user').live('click',function(){

			var iduser = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
			var dataString = 'action=get_vote_user_recall&iduser='+iduser+'&user_ID='+user_ID;
			jQuery.ajax({
				".$ajaxrayt."
				success: function(data){
					if(data['otvet']==100){
						jQuery('#rayt-user-'+data['iduser']).after(data['votes']);
						jQuery('#votes-user-'+data['iduser']).slideDown();
					} else {
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
	/* Получаем общий рейтинг записей пользователя */
		jQuery('.view-rayt-posts').live('click',function(){
                    if(jQuery(this).hasClass('active')) return false;
			var iduser = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
			var dataString = 'action=get_vote_user_posts&iduser='+iduser+'&user_ID='+user_ID;
			jQuery.ajax({
				".$ajaxrayt."
				success: function(data){
					if(data['otvet']==100){
                                            jQuery('.float-window-recall .recall-button').toggleClass('active');
                                            jQuery('#votes-user-'+data['iduser']+' .content-rayting-block').html(data['votes']);
					} else {
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
	/* Получаем общий рейтинг комментариев пользователя */
		jQuery('.view-rayt-comments').live('click',function(){
                    if(jQuery(this).hasClass('active')) return false;
			var iduser = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
			var dataString = 'action=get_vote_user_comments&iduser='+iduser+'&user_ID='+user_ID;
			jQuery.ajax({
				".$ajaxrayt."
				success: function(data){
					if(data['otvet']==100){
                                            jQuery('.float-window-recall .recall-button').toggleClass('active');
                                            jQuery('#votes-user-'+data['iduser']+' .content-rayting-block').html(data['votes']);
					} else {
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
		jQuery('.cancel-rayt-rcl').live('click',function(){
			var id = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
			var type = jQuery(this).attr('data');
			var dataString = 'action=cancel_rayt_rcl&id='+id+'&type='+type+'&user_ID='+user_ID;
			jQuery.ajax({
				".$ajaxrayt."
				success: function(data){
					if(data['result']==100){
						jQuery('#cancel-rayt-'+data['idpost']).remove();
						var newrayt = parseInt(data['rayt'].replace(/\D+/g,''));
						if(data['type']=='comment'){
							jQuery('#com-karma-'+data['idpost']).html(data['rayt']);
							if(newrayt===0) jQuery('#vote-results-'+data['idpost']).remove();
						}else{
							jQuery('#post-karma-'+data['idpost']).html(data['rayt']);
							if(newrayt===0) ('#vote-post-results-'+data['idpost']).remove();
						}
					} else {
						alert('Ошибка!');
					}
				}
			});
			return false;
		});";
	return $script;
}
add_filter('file_scripts_rcl','scripts_rayt_rcl');