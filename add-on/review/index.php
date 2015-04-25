<?php
rcl_enqueue_style('review',__FILE__);

$rcl_review = new rcl_review();

add_filter('array_rayt_chek','add_rayt_array_review_rcl');
function add_rayt_array_review_rcl($array){
	global $rcl_options;
	$array['rayt_recall'] = $rcl_options['rayt_recall_user_rayt'];
	return $array;
}


add_action('init','add_tab_review');
function add_tab_review(){
    add_tab_rcl('recall',array('rcl_review','get_content_review'),__('Reviews','rcl'),array(
                            'public'=>1,
                            'class'=>'fa-trophy',
                            'order'=>50,
                            'path'=>__FILE__
                        ));
}

class rcl_review{

	public function __construct() {
		if (!is_admin()){
			add_action('init', array(&$this, 'add_recall_user_activate'));
			add_action('init', array(&$this, 'recall_user_delete_activate'));

		}else{
			add_filter('admin_options_wprecall',array(&$this, 'get_admin_review_page_content'));
		}
		add_action('delete_user',array(&$this, 'delete_reviews_user_rcl'));

    }

	//Оставляем пользователю отзыв
	function add_recall_user(){
		global $user_ID,$wpdb,$rcl_options;

		if(!$user_ID) wp_die(__('You dont have that right!','rcl'));

		$adressat_id = pow($_POST['user_id'], 0.5);
		$content_otziv = esc_textarea($_POST['content_otz']);
		$status = esc_sql($_POST['status']);
		$online = esc_sql($_POST['online']);
		$count_rayt = $rcl_options['count_rayt_recall'];
		if($status<0) $count_rayt = $count_rayt*-1;
		if($status==0)$count_rayt = 0;

		$otziv = $wpdb->get_row("SELECT * FROM ".RCL_PREF."profile_otziv WHERE user_id = '$adressat_id' AND author_id = '$user_ID'");

		if(!$otziv){

			$content_otziv = apply_filters('rcl_content_recall',$content_otziv,$user_ID,$adressat_id);

			$result = $wpdb->insert(
				RCL_PREF.'profile_otziv',
				array( 'author_id' => "$user_ID", 'content_otziv' => "$content_otziv", 'user_id' => "$adressat_id", 'status' => "$count_rayt" ),
				array( '%d', '%s', '%d', '%d' )
			);

			if($rcl_options['rayt_recall_user_rayt']==1&&function_exists('update_user_rayting')) update_user_rayting($adressat_id,$count_rayt,'review');
		}

		if (!$result) wp_die('Error');

		do_action('rcl_add_recall',$user_ID,$adressat_id);

		if($online != 0){
			wp_redirect( get_redirect_url_rcl(get_author_posts_url($adressat_id),'recall'));  exit;
		}

		$title = __('You left a review','rcl');
		$to = get_the_author_meta('user_email',$adressat_id);
		$mess = '
		<h3>'.__('You have been leaving feedback','rcl').'</h3>
		<p>'.__('from the user','rcl').' '.get_the_author_meta('display_name',$user_ID).'</p>
		<p>'.__('You can read the message by clicking on','rcl').' <a href="'.get_redirect_url_rcl(get_author_posts_url($adressat_id),'recall').'">'.__('the link','rcl').'</a></p>';

		rcl_mail($to, $title, $mess);

		wp_redirect( get_redirect_url_rcl(get_author_posts_url($adressat_id),'recall') );  exit;

	}

	function add_recall_user_activate ( ) {
	  if ( isset( $_POST['add_recall_user'] ) ) {
		add_action( 'wp', array(&$this, 'add_recall_user') );
	  }
	}

	function delete_reviews_user_rcl($user){
		global  $wpdb,$rcl_options;
		$rews = $wpdb->get_results("SELECT * FROM ".RCL_PREF."profile_otziv WHERE author_id = '$user'");
		if(!$rews)return false;
		foreach($rews as $rew){
			$wpdb->query("DELETE FROM ".RCL_PREF."profile_otziv WHERE ID = '$rew->ID'");
			if($rcl_options['rayt_recall_user_rayt']==1&&function_exists('update_user_rayting')){
				$rec = $rew->status;
				if($rec>0) $rec = $rec*(-1);
				else if($rec<0) $rec = abs($rec);
                                update_user_rayting($rew->user_id,$rec,'review');
			}
		}
	}

	//Удаляем отзыв
	function recall_user_delete(){
		global $wpdb,$user_ID,$rcl_options;
		if($user_ID){
			$recall_id = esc_sql($_POST['recall_id']);
			$user_id = esc_sql($_POST['user_id']);

			$rec = $wpdb->get_var("SELECT status FROM ".RCL_PREF."profile_otziv WHERE ID = '$recall_id'");

			$result = $wpdb->query("DELETE FROM ".RCL_PREF."profile_otziv WHERE ID = '$recall_id'");

			if($rcl_options['rayt_recall_user_rayt']==1&&function_exists('update_user_rayting')){
				if($rec>0) $rec = $rec*(-1);
				else if($rec<0) $rec = abs($rec);
				update_user_rayting($user_id,$rec,'review');
			}

			if ($result) {

				wp_redirect( get_redirect_url_rcl(get_author_posts_url($user_id),'recall') );  exit;

				} else {
			  wp_die('Error');
			}
		}
	}

	function recall_user_delete_activate ( ) {
	  if ( isset( $_POST['recall_user_delete'] ) ) {
		add_action( 'wp', array(&$this, 'recall_user_delete') );
	  }
	}


	function get_admin_review_page_content($content){

            $opt = new Rcl_Options(__FILE__);

            $content .= $opt->options(
                __('Settings reviews','rcl'),
                $opt->option_block(
                    array(
                        $opt->title(__('Reviews','rcl')),
                        $opt->label(__('Scores for the tip','rcl')),
                        $opt->option('text',array('name'=>'count_rayt_recall')),
                        $opt->notice(__('set how many points the ranking will be awarded for a positive vote or how many points will be subtracted from the rating for a negative vote(default 1)','rcl')),
                        $opt->label(__('To accept and leave feedback can','rcl')),
                        $opt->option('select',array(
                            'name'=>'type_recall',
                            'options'=>array(__('All','rcl'),__('With published posts','rcl'))
                        )),
                        $opt->label(__('The effect of feedback on overall rating','rcl')),
                        $opt->option('select',array(
                            'name'=>'rayt_recall_user_rayt',
                            'options'=>array(__('No','rcl'),__('Yes','rcl'))
                        ))
                    )
                )
            );

            return $content;
	}

	function get_status($st){
            if($st>0) return '<span class="plus status"><i class="fa fa-thumbs-o-up"></i></span>';
            if($st<0) return '<span class="minus status"><i class="fa fa-thumbs-o-down"></i></span>';
	}

        function get_content_review($user_LK){
            global $wpdb,$user_ID,$rcl_options;

            $online = 0;

            $otzivy = $wpdb->get_results("SELECT * FROM ".RCL_PREF."profile_otziv WHERE user_id = '$user_LK'");
            if($otzivy){
                $recall_block = '';
                foreach($otzivy as $otziv){

                    if($otziv->status>0) $status = 1;
                    else if($otziv->status<0) $status = '-1';
                    else $status = 0;

                    $recall_block .= '<div class="public-post recall'.$status.'">
                    <div class="author-avatar">'.get_avatar($otziv->author_id, 60).'</div>
                    <div class="content-recall">
                    '.$this->get_status($otziv->status).'
                    <p>
                    <strong><a href="'.get_author_posts_url($otziv->author_id).'">'.get_the_author_meta('display_name', $otziv->author_id).'</a> '.__('leave a review','rcl').':</strong>
                    </p>'.nl2br($otziv->content_otziv).'</div>';
                    if($user_ID==$otziv->author_id){
                            $recall_block .= '<form method="post" action="" style="text-align: right; padding-top: 10px;">
                            <input type="hidden" name="user_id" value="'.$otziv->user_id.'">
                            <input type="hidden" name="recall_id" value="'.$otziv->ID.'">
                            <input type="submit" class="recall-button" name="recall_user_delete" value="'.__('Delete','rcl').'">
                            </form>';
                    }
                    $recall_block .= '</div>';
                }
            }else if($user_ID==$user_LK){
                    $recall_block = '<p>'.__('You have not left any reviews','rcl').'</p>';
            }else if($user_ID!=$user_LK){
                    $recall_block = '<h3>'.__('The user has no reviews yet','rcl').'</h3>';
            }
            //получаем кол-во отзывов текущего пользователя об авторе

            if($user_ID!=$user_LK&&$user_ID) {

                    if($rcl_options['type_recall']==1){

                        $count_post_author = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->prefix ."posts WHERE post_author = '$user_ID' AND post_status = 'publish' LIMIT 1");
                        $count_post_user = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->prefix ."posts WHERE post_author = '$user_LK' AND post_status = 'publish' LIMIT 1");

                        if(!$count_post_author||!$count_post_user){
                                $recall_block .= __('Users without published records cannot accept and add reviews.','rcl');
                                $block_wprecall .= $recall_block;
                                return $block_wprecall;
                        }

                    }

                    $user_ID_true = $wpdb->get_var("SELECT COUNT(ID) FROM ".RCL_PREF."profile_otziv WHERE user_id = '$user_LK' AND author_id = '$user_ID' LIMIT 1");

                    if($user_ID_true==0):

                        $addres_user = pow($user_LK, 2);
                        $recall_block .= '<div class="otziv">
                                <form name="addrecall" method="post" action="">
                                <p>'.__('Review text','rcl').':</p>
                                <input type="radio" name="status" value="1" id="labeled_1" /><label for="labeled_1">'.__('Positively','rcl').'</label>
                                <input type="radio" name="status" value="0" id="labeled_2" checked="checked"/><label for="labeled_2">'.__('Neutral','rcl').'</label>
                                <input type="radio" name="status" value="-1" id="labeled_3" /><label for="labeled_3">'.__('Negatively','rcl').'</label><br />
                                <label for="content_otz"></label>
                                <textarea required name="content_otz" id="content_otz" rows="5" style="width:100%;padding:0;"></textarea>
                                <input type="hidden" name="online" value="'.$online.'">
                                <input type="hidden" name="user_id" value="'.$addres_user.'">
                                <p style="text-align:right;"><input type="submit" name="add_recall_user" class="recall-button" value="'.__('Add a review','rcl').'"></p>
                                </form>
                                </div>';

                    endif;
            }

            return $recall_block;
        }
}

function add_tab_recall_rcl($array_tabs){
    $array_tabs['recall']=array('rcl_review','get_content_review');
    return $array_tabs;
}
add_filter('ajax_tabs_rcl','add_tab_recall_rcl');

//ajax_tab_rcl('recall',array('rcl_review','get_content_review'));
?>
