<?php
require_once 'core.php';

class RCL_Rayt{
	function add_post_rayting_recall(){
		global $wpdb,$rcl_options,$user_ID;
		if(!$user_ID) exit;

		$p = esc_sql($_POST['post']);
		$post = explode('-', $p);
		$id = esc_sql($_POST['id_rayt']);
		$id_rayt = round(pow($id, 0.5));
		$rayt = $id_rayt - $post[1];
		$post_id = $post[1];

                $rcl_options['count_rayt_products'] = 10;

		$point = get_post_rayting($post_id, $user_ID);

		if($point){
                    $log['otvet']=110;
                    echo json_encode($log);
                    exit;
		}

		$post_data = get_post($post_id);

                if(!$rcl_options['count_rayt_'.$post_data->post_type]) $rcl_options['count_rayt_'.$post_data->post_type] = 1;
                if(absint($rayt)!=$rcl_options['count_rayt_'.$post_data->post_type]){ echo 'Error abs '.$rayt.'-'.$rcl_options['count_rayt_'.$post_data->post_type]; exit;}
                //print_r($post_data);exit;
		if($post_data->post_type=='products'){
			$salefile = $wpdb->get_var("SELECT ID FROM ".$wpdb->prefix."posts WHERE post_parent = '".$post[1]."' AND post_title = 'salefile'");
			if($salefile){
				$sale = $wpdb->get_var("SELECT ID FROM ".$wpdb->prefix."rmag_files_downloads WHERE parent_id = '".$post[1]."' AND user_id = '$user_ID'");
				if(!$sale){
					$log['otvet']=120;
					$log['message'] = __('You cant change the rating of a product that was purchased personally!','rcl');
					echo json_encode($log);
					exit;
				}
			}
		}

		insert_post_rayting($post_id,$user_ID,$rayt);

		$log['otvet']=100;
		$log['post']=$post[1];
		$log['rayt']=$rayt;

		echo json_encode($log);
		exit;
	}
	function add_rayting_comment_recall(){
            global $wpdb,$user_ID,$rcl_options;

            if(!$user_ID){
                    $log['otvet']=110;
                    echo json_encode($log);
                    exit;
            }

            $c = esc_sql($_POST['com']);
            $com = explode('-', $c);
            $id = esc_sql($_POST['id_rayt']);
            $id_rayt = round(pow($id, 0.5));
            $rayt = $id_rayt - $com[1];
            $comment_id = esc_sql($com[1]);

            if(!$rcl_options['count_rayt_comment']) $rcl_options['count_rayt_comment'] = 1;
            if(abs($rayt)!=$rcl_options['count_rayt_comment']) exit;

            $point = get_comment_rayting($comment_id,$user_ID);

            if($point){
                $log['otvet']=110;
                echo json_encode($log);
                exit;
            }

            insert_comment_rayting($comment_id,$user_ID,$rayt);

            $log['otvet']=100;
            $log['com']=$com[1];
            $log['rayt']=$rayt;

            echo json_encode($log);
            exit;
	}
	function get_vote_comment_recall(){
		global $wpdb;
		$id_com = esc_sql($_POST['id_com']);
		$votes_com = $wpdb->get_results("SELECT rayting,user FROM ".RCL_PREF."rayting_comments WHERE comment_id = '$id_com' ORDER BY ID DESC LIMIT 200");
		if($votes_com){

			$names = get_names_array_rcl($votes_com,'user');

			$recall_votes = '<ul>';
			foreach((array)$votes_com as $vote){
				$rayt = $vote->rayting;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($vote->user).'">'.$names[$vote->user].'</a> '.__('vote','rcl').': '.raytout($rayt).'</li>';
			}
			$recall_votes .= '</ul>';

			$log['otvet']=100;
			$log['id_com']=$id_com;
			$log['votes'] = get_block_rayting_rcl($recall_votes,$id_com,'comment');
		}
		echo json_encode($log);
		exit;
	}
	function get_vote_post_recall(){
		global $wpdb;
		$id_post = esc_sql($_POST['id_post']);
		$votes_post = $wpdb->get_results("SELECT user,status FROM ".RCL_PREF."rayting_post WHERE post = '$id_post' ORDER BY ID DESC LIMIT 200");
		if($votes_post){

			$names = get_names_array_rcl($votes_post,'user');

			$recall_votes = '<ul>';
			foreach((array)$votes_post as $vote){
				$rayt = $vote->status;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($vote->user).'">'.$names[$vote->user].'</a> '.__('vote','rcl').': '.raytout($rayt).'</li>';
			}
			$recall_votes .= '</ul>';

			$log['otvet']=100;
			$log['id_post']=$id_post;
			$log['votes']= get_block_rayting_rcl($recall_votes,$id_post,'post');
		}
		echo json_encode($log);
		exit;
	}
	function get_vote_user_comments(){
		global $wpdb,$user_ID;
		if(!$user_ID){
			$log['otvet']=1;
			echo json_encode($log);
			exit;
		}

		$id_user = esc_sql($_POST['iduser']);
		$rcl_comments_rayt = $wpdb->get_results("SELECT user,comment_id,author_com,rayting FROM ".RCL_PREF."rayting_comments WHERE author_com = '$id_user' ORDER BY ID DESC LIMIT 200");

			$recall_votes = '<ul class="rayt-list-user">';
			$n=0;

			$names = get_names_array_rcl($rcl_comments_rayt,'user');

			foreach((array)$rcl_comments_rayt as $comments){

				$n++;
				$rayt = $comments->rayting;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li>'.$comments->ID.'<a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($comments->user).'">'.$names[$comments->user].'</a> '.__('vote','rcl').': '.raytout($rayt).' <a href="'.get_comment_link( $comments->comment_id ).'">'.__('comment','rcl').'</a> '.__('entry','rcl').'</li>';

			}

			$recall_votes .= '</ul>';

		if($n!=0){
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']=$recall_votes;
		}else{
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']='<p>'.__('For user comments no one voted','rcl').'</p>';
		}
		echo json_encode($log);
		exit;
	}
	function get_vote_user_posts(){
		global $wpdb,$user_ID;
		if(!$user_ID){
                    $log['otvet']=1;
                    echo json_encode($log);
                    exit;
		}

		$id_user = esc_sql($_POST['iduser']);


                $rcl_rayting_post = $wpdb->get_results("SELECT user,post,status,author_post FROM ".RCL_PREF."rayting_post WHERE author_post = '$id_user' ORDER BY ID DESC LIMIT 200");

                if(!$rcl_rayting_post){
                    $log['otvet']=100;
                    $log['iduser']=$id_user;
                    $log['votes']='<p>'.__('For publishing user nobody voted','rcl').'</p>';
                    echo json_encode($log);
                    exit;

                }

                $recall_votes = '<ul class="rayt-list-user">';
		$n=0;

		foreach((array)$rcl_rayting_post as $user){
			$userslst[$user->user] = $user->user;
			$postslst[$user->post] = $user->post;
		}

		$c = 0;
		foreach((array)$userslst as $id){
			if(++$c>1) $uslst .= ',';
			$uslst .= $id;
		}

		$display_names = $wpdb->get_results("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN ($uslst)");

		foreach((array)$display_names as $name){
			$names[$name->ID] = $name->display_name;
		}

		$b = 0;
		foreach((array)$postslst as $id){
			if(++$b>1) $plst .= ',';
			$plst .= $id;
		}

		$postdata = $wpdb->get_results("SELECT ID,post_title FROM ".$wpdb->prefix."posts WHERE ID IN ($plst)");

		foreach((array)$postdata as $p){
			$title[$p->ID] = $p->post_title;
		}

		foreach((array)$rcl_rayting_post as $post){
			if($post->author_post==$id_user){
				$n++;
				$rayt = $post->status;
                                $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
				$recall_votes .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($post->user).'">'.$names[$post->user].'</a> '.__('vote','rcl').': '.raytout($rayt).' '.__('entry','rcl').' <a href="/?p='.$post->post.'">'.$title[$post->post].'</a></li>';
			}
		}

		$recall_votes .= '</ul>';

		if($n!=0){
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']=$recall_votes;
		}else{
			$log['otvet']=100;
			$log['iduser']=$id_user;
			$log['votes']='<p>'.__('For publishing user nobody voted','rcl').'</p>';
		}
		echo json_encode($log);
		exit;
	}
	function get_vote_user_recall(){
            global $wpdb,$user_ID;
            if(!$user_ID){
                    $log['otvet']=1;
                    echo json_encode($log);
                    exit;
            }
            $id_user = esc_sql($_POST['iduser']);

            $n=0;

            $rcl_comments_rayt = $wpdb->get_results("SELECT user,comment_id,author_com,rayting FROM ".RCL_PREF."rayting_comments WHERE author_com = '$id_user' ORDER BY ID DESC LIMIT 200");

            if($rcl_comments_rayt){
                    $names = get_names_array_rcl($rcl_comments_rayt,'user');
                    $content = '<ul class="rayt-list-user">';
                    foreach((array)$rcl_comments_rayt as $comments){
                        $n++;
                        $rayt = $comments->rayting;
                        $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
                        $content .= '<li>'
                                . $comments->ID
                                .'<a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($comments->user).'">'
                                .$names[$comments->user].'</a> '.__('vote','rcl').': '.raytout($rayt)
                                .' <a href="'.get_comment_link( $comments->comment_id ).'">'.__('comment','rcl').'</a> '.__('recording','rcl').'</li>';
                    }
                    $content .= '</ul>';
                    $recall_votes = get_block_user_rayting($content,$id_user,'comments');
            }

            if($n==0){

                $rcl_rayting_post = $wpdb->get_results("SELECT user,post,status,author_post FROM ".RCL_PREF."rayting_post WHERE author_post = '$id_user' ORDER BY ID DESC LIMIT 200");

                if(!$rcl_rayting_post){
                    $content = '<p>'.__('For publications and user comments no one voted','rcl').'</p>';
                    $recall_votes = get_block_user_rayting($content,$id_user);
                    $log['otvet']=100;
                    $log['iduser']=$id_user;
                    $log['votes']=$recall_votes;
                    echo json_encode($log);
                    exit;
                }

                foreach((array)$rcl_rayting_post as $user){
                        $userslst[$user->user] = $user->user;
                        $postslst[$user->post] = $user->post;
                }

                $b = 0;
                foreach((array)$userslst as $id){
                        if(++$b>1) $uslst .= ',';
                        $uslst .= $id;
                }

                $display_names = $wpdb->get_results("SELECT ID,display_name FROM ".$wpdb->prefix."users WHERE ID IN ($uslst)");

                foreach((array)$display_names as $name){
                        $names[$name->ID] = $name->display_name;
                }

                $c = 0;
                foreach((array)$postslst as $id){
                        if(++$c>1) $plst .= ',';
                        $plst .= $id;
                }

                $postdata = $wpdb->get_results("SELECT ID,post_title FROM ".$wpdb->prefix."posts WHERE ID IN ($plst)");

                foreach((array)$postdata as $p){
                        $title[$p->ID] = $p->post_title;
                }

                $content = '<ul class="rayt-list-user">';
                foreach((array)$rcl_rayting_post as $post){
                    if($post->author_post==$id_user){
                        $n++;
                        $rayt = $post->status;
                        $class = ($rayt>0) ? 'fa-thumbs-o-up' : 'fa-thumbs-o-down';
                        $content .= '<li><a class="fa '.$class.'" target="_blank" href="'.get_author_posts_url($post->user).'">'.$names[$post->user].'</a> '.__('vote','rcl').': '.raytout($rayt).' '.__('entry','rcl').' <a href="/?p='.$post->post.'">'.$title[$post->post].'</a></li>';
                    }
                }
                $content .= '</ul>';
                $recall_votes .= get_block_user_rayting($content,$id_user,'posts');
            }

            if($n!=0){
                $log['otvet']=100;
                $log['iduser']=$id_user;
                $log['votes']=$recall_votes;
            }else{
                $log['otvet']=1;
            }
            echo json_encode($log);
            exit;
	}
	function cancel_rayt_rcl(){
		global $wpdb,$user_ID;

		$type = esc_sql($_POST['type']);
		$id = esc_sql($_POST['id']);

		if($type=='comment'){
                    $point = get_comment_rayting($id,$user_ID);
                    if(!$point) return false;
                    $total = get_comment_total_rayting($id);
                    delete_comment_rayting($id,$user_ID,$point);
		}
		if($type=='post'){
                    $point = get_post_rayting($id,$user_ID);
                    if(!$point) return false;
                    $total = get_post_total_rayting($id);
                    delete_post_rayting($id,$user_ID,$point);
		}

                $newrayt = $total - $point;

		$log['result']=100;
		$log['type']=$type;
		$log['idpost']=$id;
		$log['rayt']=raytout($newrayt);
		echo json_encode($log);
	exit;
	}
}

function get_block_user_rayting($content,$id_user,$type='false'){

    $btns = array('posts'=>__('Publication','rcl'),'comments'=>__('Comments','rcl'));
    $block = '<div id="votes-user-'.$id_user.'" class="float-window-recall">
    <div id="close-votes-'.$id_user.'" class="close"><i class="fa fa-times-circle"></i></div>';

    foreach($btns as $key=>$title){
        $class = ($type==$key)? 'active' : '';
        $block .= get_button_rcl($title,'#',array('class'=> $class.' view-rayt-'.$key,'id'=>'view-rayt-'.$key.'-'.$id_user)).' ';
    }

    $block .= '<div class="content-rayting-block">'
    .$content
    . '</div>'
    . '</div>';

    return $block;
}

function get_block_rayting_rcl($content,$id_block,$type){
    return '<div id="votes-'.$type.'-'.$id_block.'" class="float-window-recall">
    <div id="close-votes-'.$id_block.'" class="close"><i class="fa fa-times-circle"></i></div>'
    .$content
    .'</div>';
}

function raytout($rayt){
    if($rayt>0){$class="rayt-plus";$rayt='+'.$rayt;}
    elseif($rayt<0)$class="rayt-minus";
    else{$class="null";$rayt=0;}
    return '<span class="'.$class.'">'.$rayt.'</span>';
}