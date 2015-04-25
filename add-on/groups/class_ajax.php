<?php
class RCL_Ajax_Group{
	function remove_user_publics_group_rcl(){
		global $wpdb,$user_ID;
		if(!$user_ID) exit;
		$userid = $_POST['user_id'];
		$group_id = $_POST['group_id'];
		$admin_id = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_$group_id' AND user_id = '$user_ID'");
		if(!$admin_id) exit;

		$posts = $wpdb->get_results("
			SELECT
				b.ID
			FROM
				`wp_term_relationships` as a
			INNER JOIN
				`wp_posts` as b on (b.ID = a.object_id)
			WHERE
				a.term_taxonomy_id = '$group_id' && b.post_author = '$userid' && b.post_type = 'post-group'
		");

		foreach($posts as $p){
			if(++$a>1)$p_list .= ',';
			$p_list .= $p->ID;
		}

		$wpdb->get_results("SELECT * FROM ".RCL_PREF."total_rayting_posts WHERE post_id IN ($p_list)");

		$wpdb->query("DELETE FROM ".$wpdb->prefix ."posts WHERE ID IN ($p_list)");
		$wpdb->query("DELETE FROM ".$wpdb->prefix ."comments WHERE comment_post_ID IN ($p_list)");

		$log['content']= __('Deleted user!','rcl').' '.get_button_rcl(__('To delete all publications','rcl'),'#',array('icon'=>false,'class'=>'remove-public-group','id'=>false,'attr'=>'user-data='.$userid.' group-data='.$group_id));
		$log['content']= __('Deleted user!','rcl');
		$log['int']=100;
		echo json_encode($log);
		exit;
	}
	function group_ban_user_rcl(){
		global $wpdb,$user_ID;
		if(!$user_ID) exit;
		$userid = $_POST['user_id'];
		$group_id = $_POST['group_id'];
		$admin_id = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_$group_id' AND user_id = '$user_ID'");
		if(!$admin_id) exit;
		$wpdb->query("DELETE FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'user_group_$group_id' AND user_id = '$userid'");
		//$log['content']='Пользователь удален! <a href="#" user-data="'.$userid.'" group-data="'.$group_id.'" class="remove-public-group recall-button">Удалить все публикации</a>';
		$log['content']= __('Deleted user!','rcl');
		$log['int']=100;
		echo json_encode($log);
		exit;
	}

	function all_users_group_recall(){
		global $wpdb;
		$page = $_POST['page'];
		if(!$_POST['page']) $page = 1;
		include('class_group.php');
		include('../../functions/shortcodes.php');
		$group = new Rcl_Group($_POST['id_group']);
		$block_users = '<div class="backform" style="display: block;"></div>
		<div class="float-window-recall" style="display:block;"><p align="right">'.get_button_rcl('Закрыть','#',array('icon'=>false,'class'=>'close_edit','id'=>false,'attr'=>false)).'</p><div>';
		$block_users .= $group->all_users_group($page);
		$block_users .= '</div></div>
		<script type="text/javascript"> jQuery(function(){ jQuery(".close_edit").click(function(){ jQuery(".group_content").empty(); }); }); </script>';
		$log['recall']=100;
		$log['block_users']=$block_users;
		echo json_encode($log);
		exit;
	}

	function edit_group_wp_recall(){
		global $wpdb,$user_ID;
		if(!$user_ID) exit;
		$new_name_group = $_POST['new_name_group'];
		$new_desc_group = $_POST['new_desc_group'];
		$id_group = $_POST['id_group'];
		$admin_id = $wpdb->get_var("SELECT meta_value FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'admin_group_$id_group' AND user_id = '$user_ID'");
		if(!$admin_id){
			$options_gr = unserialize($wpdb->get_var("SELECT option_value FROM ".RCL_PREF."groups_options WHERE group_id='$id_group'"));
			if($options_gr['admin']==$user_ID) $admin_id = $options_gr['admin'];
		}

		if($admin_id){

			$taxonomy = 'groups';

			if($new_name_group){
			   $res = $wpdb->update( $wpdb->prefix.'terms',
					array( 'name' => $new_name_group ),
					array( 'term_id' => $id_group )
				);
			}
			if($new_desc_group){
			   $res = $wpdb->update(  $wpdb->prefix.'term_taxonomy',
					array( 'description' => $new_desc_group ),
					array( 'term_id' => $id_group )
				);
			}

			if($res) $log['int']=100;
			else $log['int']=200;

		}
		echo json_encode($log);
		exit;
	}
}