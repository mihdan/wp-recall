<?php
include_once('classes.php');

rcl_enqueue_style('publics',__FILE__);

if (!session_id()) { session_start(); }

if (!is_admin()):
	add_action('wp_enqueue_scripts','script_post_recall');
endif;

function script_post_recall(){
	global $rcl_options;
	if($rcl_options['media_downloader_recall']!=1) return false;
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'script_post_recall', plugins_url('js/scripts.js', __FILE__) );
}

if (!is_admin()):
	add_filter('the_content','add_gallery_recall',10);
endif;

if (!is_admin()||defined('DOING_AJAX'))add_filter('the_content','author_info_recall',100);

add_action('admin_menu', 'public_form_options_page_rcl',30);
function public_form_options_page_rcl(){
	add_submenu_page( 'manage-wprecall', __('Form of publication','rcl'), __('Form of publication','rcl'), 'manage_options', 'manage-public-form', 'recall_public_form_edit');
}

add_filter('taxonomy_public_form_rcl','add_taxonomy_public_post');
function add_taxonomy_public_post($tax){
    if (!isset($tax['post'])) $tax['post'] = 'category';
    return $tax;
}

add_filter('after_public_form_rcl','add_saveform_data_script',10,2);
function add_saveform_data_script($content,$data){
    $idform = 'form-'.$data->post_type.'-';
    $idform .= ($data->post_id)? $data->post_id : 0;
    $content .= '<script type="text/javascript" src="'.addon_url('js/sisyphus.min.js',__FILE__).'"></script>'
            . '<script>jQuery( function() { jQuery( "#'.$idform.'" ).sisyphus({timeout:10}) } );</script>';
    return $content;
}

add_filter('admin_options_wprecall','get_admin_public_page_content');
function get_admin_public_page_content($content){
    global $rcl_options,$_wp_additional_image_sizes;

    $opt = new Rcl_Options(__FILE__);

    $args = array(
        'selected'   => $rcl_options['public_form_page_rcl'],
        'name'       => 'public_form_page_rcl',
        'show_option_none' => '<span style="color:red">'.__('Not selected','rcl').'</span>',
        'echo'             => 0
    );

    for($a=1;$a<=20;$a++){
	$count_img[$a] = $a;
    }

    $_wp_additional_image_sizes['thumbnail'] = 1;
    $_wp_additional_image_sizes['medium'] = 1;
    $_wp_additional_image_sizes['large'] = 1;
    foreach($_wp_additional_image_sizes as $name=>$size){
        $sh_name = $name;
        if($size!=1) $sh_name .= ' ('.$size['width'].'*'.$size['height'].')';
        $d_sizes[$name] = $sh_name;
    }

    $roles = array(
        10=>__('only Administrators','rcl'),
        7=>__('Editors and older','rcl'),
        2=>__('Authors and older','rcl'),
        1=>__('Participants and older','rcl'),
        0=>__('All users','rcl'));

    $content .= $opt->options(
        __('The publish settings','rcl'),array(
        $opt->option_block(
            array(
                $opt->title(__('General settings','rcl')),

                $opt->label(__('Page publishing and editing records','rcl')),
                wp_dropdown_pages( $args ),
                $opt->notice(__('Required for proper formation of links to edit records, you must specify the page where the shortcode is [public-form] no matter where displayed the very form of publication this page will be used to edit the entry','rcl')),

                $opt->label(__('Display information about the author','rcl')),
                $opt->option('select',array(
                    'name'=>'info_author_recall',
                    'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                )),

                $opt->label(__('Tab list of publications','rcl')),
                $opt->option('select',array(
                    'name'=>'publics_block_rcl',
                    'parent'=>true,
                    'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                )),
                $opt->child(
                      array('name'=>'publics_block_rcl','value'=>1),
                      array(
                            $opt->label(__('List of publications of the user','rcl')),
                            $opt->option('select',array(
                                'name'=>'view_publics_block_rcl',
                                'options'=>array(__('Only the owner of the account','Show everyone including guests','rcl'))
                            ))
                      )
                )
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Category','rcl')),

                $opt->label(__('Authorized headings','rcl')),
                $opt->option('text',array('name'=>'id_parent_category')),
                $opt->notice(__('ID columns in which permitted the publication should be separated by commas. '
                        . 'This setting is common to all forms of publication, but it is possible '
                        . 'to specify the desired category in shortcode forms, for example: [public-form cats="72,149"] '
                        . 'or for each form separately on the page generate custom fields','rcl')),
                $opt->notice(__('It is better to specify the parent category, then their child will be withdrawn automatically.','rcl')),

                $opt->label(__('Number of columns to select','rcl')),
                $opt->option('select',array(
                    'name'=>'count_category_post',
                    'default'=>1,
                    'options'=>array(1=>1,2=>2,3=>3,4=>4,5=>5)
                ))
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Media','rcl')),

                $opt->label(__('Load the media files to the publications','rcl')),
                $opt->option('select',array(
                    'name'=>'media_downloader_recall',
                    'parent'=>true,
                    'options'=>array(__('Download Wp-Recall','rcl'),__('The Wordpress Media Library','rcl'))
                )),
                $opt->child(
                    array('name'=>'media_downloader_recall','value'=>0),
                    array(
                        $opt->notice(__('<b>note:</b> Using the ability to upload media to Wp-Recall you disable the ability to use the media library site, the downloaded files will form the gallery of images above the content you publish.','rcl')),
                        $opt->label(__('Number of images in the gallery Wp-Recall','rcl')),
                        $opt->option('select',array(
                            'name'=>'count_image_gallery',
                            'options'=>$count_img
                        )),

                        $opt->label(__('The maximum image size, Mb','rcl')),
                        $opt->option('number',array('name'=>'public_gallery_weight')),
                        $opt->notice(__('To limit image uploads to publish this value in megabytes. By default, 2MB','rcl')),

                        $opt->label(__('The size in the editor by default','rcl')),
                        $opt->option('select',array(
                            'name'=>'default_size_thumb',
                            'options'=>$d_sizes
                        )),
                        $opt->notice(__('Select the picture size in the silence of their use in the visual editor during the publishing process','rcl'))
                    )
                )
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Form of publication','rcl')),

                $opt->label(__('Text editor','rcl')),
                $opt->option('select',array(
                    'name'=>'type_text_editor',
                    'options'=>array(
                        __('Simple TEXTAREA','rcl'),
                        __('Download TinyMCE editor','rcl'),
                        __('Download HTML editor','rcl'),
                        __('TinyMCE and HTML editors','rcl')
                    )
                )),

                $opt->label(__('The output form of publication','rcl')),
                $opt->option('select',array(
                    'name'=>'output_public_form_rcl',
                    'default'=>1,
                    'parent'=>1,
                    'options'=>array(__('Do not display','rcl'),__('Output','rcl'))
                )),
                $opt->child(
                    array('name'=>'output_public_form_rcl','value'=>1),
                    array(
                        $opt->label(__('The form ID','rcl')),
                        $opt->option('number',array('name'=>'form-lk')),
                        $opt->notice(__('Enter the form ID to the conclusion in the personal Cabinet. The default is 1','rcl'))
                    )
                )
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Publication of records','rcl')),

                $opt->label(__('Republishing is allowed','rcl')),
                $opt->option('select',array(
                    'name'=>'user_public_access_recall',
                    'options'=>$roles
                )),

                $opt->label(__('Moderation of publications','rcl')),
                $opt->option('select',array(
                    'name'=>'moderation_public_post',
                    'options'=>array(__('To publish immediately','rcl'),__('Send for moderation','rcl'))
                )),
                $opt->notice(__('<b>If used in moderation:</b> To allow the user to see their publication before it is moderated, it is necessary to have on the website right below the Author','rcl'))
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Custom fields','rcl')),
                $opt->notice(__('Settings only for fields created using the form of the publication wp-recall','rcl')),

                $opt->label(__('Automatic withdrawal','rcl')),

                $opt->option('select',array(
                    'name'=>'pm_rcl',
                    'parent'=>true,
                    'options'=>array(__('No','rcl'),__('Yes','rcl'))
                )),
                $opt->child(
                      array('name'=>'pm_rcl','value'=>1),
                      array(
                            $opt->label(__('Place output fields','rcl')),
                            $opt->option('select',array(
                                'name'=>'pm_place',
                                'options'=>array(__('After the content recording','rcl'),__('On content recording','rcl'))
                            ))
                      )
                )
            )
        ))
    );

    return $content;
}


add_postlist_rcl('rcl','post',__('Records','rcl'),array('order'=>30));

add_action('init','init_publics_block_rcl');
function init_publics_block_rcl(){
	global $rcl_options;
	if($rcl_options['publics_block_rcl']==1){
                $view = 0;
                if($rcl_options['view_publics_block_rcl']) $view = $rcl_options['view_publics_block_rcl'];
                add_tab_rcl('publics','recall_posts_block',__('Posts','rcl'),array('public'=>$view,'class'=>'fa-list','order'=>50));
	}
	if($rcl_options['output_public_form_rcl']==1){
                add_tab_rcl('postform','recall_block_postform',__('Publication','rcl'),array('class'=>'fa-pencil','order'=>60,'path'=>__FILE__));
	}
}

function recall_block_postform($author_lk){
	global $user_ID,$rcl_options;
	if($user_ID!=$author_lk) return false;
        $id_form = 1;
        if(isset($rcl_options['form-lk'])&&$rcl_options['form-lk']) $id_form = $rcl_options['form-lk'];
	return do_shortcode('[public-form id="'.$id_form.'"]');
}

function add_tab_publics_rcl($array_tabs){
    $array_tabs['publics']='recall_posts_block';
    return $array_tabs;
}
add_filter('ajax_tabs_rcl','add_tab_publics_rcl');

function recall_posts_block($author_lk){
	global $user_ID;
        $p_button='';
	$p_button = apply_filters('posts_button_rcl',$p_button,$author_lk);
	$posts_block = '<div class="rcl-menu">'.$p_button.'</div>';
        $p_block='';
	$p_block = apply_filters('posts_block_rcl',$p_block,$author_lk);
	$posts_block .= $p_block;

	return $posts_block;
}

function get_page_content_rcl(){
	global $wpdb;

	$type = $_POST['type'];
	$start = $_POST['start'];
	$author_lk = $_POST['id_user'];

	$start .= ',';

	//$edit_url = get_redirect_url_rcl(get_permalink($rcl_options['public_form_page_rcl']));

	$posts = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts WHERE post_author='$author_lk' AND post_type='$type' AND post_status NOT IN ('draft','auto-draft') ORDER BY post_date DESC LIMIT $start 20");

		$rayting = false;
		if(function_exists('get_rayting_block_rcl')){
                        $b=0;
			foreach((array)$posts as $p){if(++$b>1) $p_list .= ',';$p_list .= $p->ID;}
			$rayt_p = $wpdb->get_results("SELECT * FROM ".RCL_PREF."total_rayting_posts WHERE post_id IN ($p_list)");
			foreach((array)$rayt_p as $r){$rayt[$r->post_id] = $r->total;}
			$rayting = true;
		}

		$posts_block .='<table class="publics-table-rcl">
		<tr>
			<td>'.__('Date','rcl').'</td><td>'.__('Title','rcl').'</td><td>'.__('Status','rcl').'</td>';
			//if($user_ID==$author_lk) $posts_block .= '<td>Ред.</td>';
			$posts_block .= '</tr>';
		foreach((array)$posts as $post){
			if($post->post_status=='pending') $status = '<span class="pending">'.__('on approval','rcl').'</span>';
			elseif($post->post_status=='trash') $status = '<span class="pending">'.__('deleted','rcl').'</span>';
			else $status = '<span class="publish">'.__('publish','rcl').'</span>';
			$posts_block .= '<tr>
			<td>'.mysql2date('d-m-Y', $post->post_date).'</td><td><a target="_blank" href="'.$post->guid.'">'.$post->post_title.'</a>';
			if($rayting) $posts_block .= ' '.get_rayting_block_rcl($rayt[$post->ID]);
			$posts_block .= '</td><td>'.$status.'</td>';
			//if($user_ID==$author_lk) $posts_block .= '<td><a target="_blank" href="'.$edit_url.'rcl-post-edit='.$post->ID.'">Ред.</a></td>';
			$posts_block .= '</tr>';
		}
		$posts_block .= '</table>';

	$log['post_content']=$posts_block;
	$log['recall']=100;

	echo json_encode($log);
    exit;
}
add_action('wp_ajax_get_page_content_rcl', 'get_page_content_rcl');
add_action('wp_ajax_nopriv_get_page_content_rcl', 'get_page_content_rcl');

function recall_public_form_edit(){
	global $wpdb;

        add_sortable_scripts();

	$form = (isset($_GET['form'])) ? $_GET['form']: false;

	if(isset($_POST['delete-form'])&&wp_verify_nonce( $_POST['_wpnonce'], 'update-public-fields' )){
            $id_form = $_POST['id-form'];
            $_GET['status'] = 'old';
            $wpdb->query("DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE 'custom_public_fields_$id_form'");
	}

	if(!$form){
		$option_name = $wpdb->get_var("SELECT option_name FROM ".$wpdb->prefix."options WHERE option_name LIKE 'custom_public_fields%'");
		if($option_name) $form = preg_replace("/[a-z_]+/", '', $option_name);
		else $form = 1;
	}

        include_once RCL_PATH.'functions/rcl_editfields.php';
        $f_edit = new Rcl_EditFields('post',array('id'=>$form,'custom-slug'=>1,'terms'=>1));

	if($f_edit->verify()){
            $_GET['status'] = 'old';
            $fields = $f_edit->update_fields();
	}

	$custom_public_form_data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."options WHERE option_name LIKE 'custom_public_fields%'");

	if($custom_public_form_data){
		$form_navi = '<h3>'.__('Available forms','rcl').'</h3><div class="form-navi">';
		foreach((array)$custom_public_form_data as $form_data){
			$id_form = preg_replace("/[a-z_]+/", '', $form_data->option_name);
			if($form==$id_form) $class = 'button-primary';
			else $class = 'button-secondary';
			$form_navi .= '<input class="'.$class.'" type="button" onClick="document.location=\''.get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-public-form&form='.$id_form.'\';" value="ID:'.$id_form.'" name="public-form-'.$id_form.'">';
		}
		if(!isset($_GET['status'])||$_GET['status']!='new') $form_navi .= '<input class="button-secondary" type="button" onClick="document.location=\''.get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-public-form&form='.++$id_form.'&status=new\';" value="'.__('To add another form').'" name="public-form-'.$id_form.'">';
		$form_navi .= '</div>

		<h3>'.__('Form ID','rcl').':'.$form.' </h3>';
		if(!isset($_GET['status'])||$_GET['status']!='new') $form_navi .= '<form method="post" action="">
			'.wp_nonce_field('update-public-fields','_wpnonce',true,false).'
			<input class="button-primary" type="submit" value="'.__('To remove all fields','rcl').'" onClick="return confirm(\''.__('Вы уверены?','rcl').'\');" name="delete-form">
			<input type="hidden" value="'.$form.'" name="id-form">
		</form>';
	}else{
		$form = 1;
		$form_navi = '<h3>'.__('Form ID','rcl').':'.$form.' </h3>';
	}

	$users_fields = '<h2>'.__('Arbitrary form fields publishing','rcl').'</h2>
	<small>Для размещения формы публикации используем шорткод [public-form]</small><br>
        <small>Можно создавать разный набор произвольных полей для разных форм.<br>
        Чтобы вывести определенный набор полей через шорткод следует указать идентификатор формы, например, [public-form id="2"]</small><br>
	<small>Форма публикации уже содержит обязательные поля для заголовка записи, контента, ее категории и указания метки.</small><br>
	'.$form_navi.'
	'.$f_edit->edit_form(array(
            $f_edit->option('select',array(
                'name'=>'requared',
                'notice'=>__('required field','rcl'),
                'value'=>array(__('No','rcl'),__('Yes','rcl'))
            ))
        )).'
	<p>Чтобы вывести все данные занесенные в созданные произвольные поля формы публикации внутри опубликованной записи можно воспользоваться функцией<br />
	<b>get_custom_post_meta_recall($post_id)</b><br />
	Разместите ее внутри цикла и передайте ей идентификатор записи первым аргументом<br />
	Также можно вывести каждое произвольное поле в отдельности через функцию<br />
	<b>get_post_meta($post_id,$slug,1)</b><br />
	где<br />
	$post_id - идентификатор записи<br />
	$slug - ярлык произвольного поля формы</p>';
	echo $users_fields;
}

//формируем галерею записи
function add_gallery_recall($content){
	global $post;
	if(get_post_meta($post->ID, 'recall_slider', 1)!=1||!is_single()||$post->post_type=='products') return $content;
	$gallery = do_shortcode('[gallery-rcl post_id="'.$post->ID.'"]');
	return $gallery.$content;
}

add_shortcode('gallery-rcl','get_gallery_rcl');
function get_gallery_rcl($atts, $content = null){
    global $post;

    add_bxslider_scripts();

    extract(shortcode_atts(array(
            'post_id' => false
    ),
    $atts));

    $post_id = $post->ID;

    $args = array(
            'post_parent' => $post_id,
            'post_type'   => 'attachment',
            'numberposts' => -1,
            'post_status' => 'any',
            'post_mime_type'=> 'image'
    );
    $childrens = get_children($args);

    if( $childrens ){
            $gallery = '<ul class="rcl-gallery">';
            foreach((array) $childrens as $children ){
                    $large = wp_get_attachment_image_src( $children->ID, 'large' );
                    $gallery .= '<li><a class="fancybox" href="'.$large[0].'"><img src="'.$large[0].'"></a></li>';
                    $thumbs[] = $large[0];
            }
            $gallery .= '</ul>';

            if(count($thumbs)>1){
                    $gallery .= '<div id="bx-pager">';
                            foreach($thumbs as $k=>$src ){
                                    $gallery .= '<a data-slide-index="'.$k.'" href=""><img src="'.$src.'" /></a>';
                            }
                    $gallery .= '</div>';
            }
    }

    return $gallery;
}

add_filter('rcl_postfooter_user','get_allpost_button_rcl',30,2);
function get_allpost_button_rcl($content,$user_id){
	global $rcl_options;
	if($rcl_options['view_publics_block_rcl']!=1) return $content;
	$content .= get_button_rcl(__('Publication','rcl'),get_redirect_url_rcl(get_author_posts_url($user_id),'publics'),array('icon'=>'fa-list'));
	return $content;
}

//Выводим инфу об авторе записи в конце поста
function author_info_recall($content){
	global $post,$rcl_options;
	if($rcl_options['info_author_recall']!=1) return $content;
	if(!is_single()) return $content;
	if($post->post_type=='products'||$post->post_type=='page') return $content;
	$out = get_author_block_content_rcl();
        if($post->post_type=='task') return $out.$content;
	return $content.$out;
}

/*************************************************
Удаление поста
*************************************************/
function delete_redactor_post(){
	global $user_ID;

	if($user_ID){
		$log['result']=100;
		wp_delete_post( $_POST['post_id'] );

		$temp_gal = unserialize(get_the_author_meta('tempgallery',$user_ID));
		if($temp_gal){
			$cnt = count($temp_gal);
			foreach((array)$temp_gal as $key=>$gal){ if($gal['ID']==$_POST['post_id']) unset($temp_gal[$key]); }
			foreach((array)$temp_gal as $t){ $new_temp[] = $t; }
			if($new_temp) update_usermeta($user_ID,'tempgallery',serialize($new_temp));
			else delete_usermeta($user_ID,'tempgallery');
		}
	}else {
		$log['result']=1;
	}

	echo json_encode($log);
    exit;
}
add_action('wp_ajax_delete_redactor_post', 'delete_redactor_post');

function get_basedir_image_rcl($path){
	$dir = explode('/',$path);
	$cnt = count($dir) - 2;
	for($a=0;$a<=$cnt;$a++){
		$base_path .= $dir[$a].'/';
	}
	return $base_path;
}

function get_single_image_gallery_rcl($atts,$content=null){
	global $post;
	extract(shortcode_atts(array('id'=>'','size'=>'thumbnail'),$atts));
	if(!$id) return false;

	$upl_dir = wp_upload_dir();
	$meta = wp_get_attachment_metadata($id);

	if(!$meta) return false;

	$full = $upl_dir['baseurl'].'/'.$meta['file'];

	if($size=='full'){
		$img = '<img class="thumbnail full"  src="'.$full.'">';
	}else{

		$size_ar = explode(',',$size);
		if(isset($size_ar[1])){
			$img = get_the_post_thumbnail($post->ID,$size_ar);
		}else{
			$dir_img = get_basedir_image_rcl($meta['file']);
			$img = '<img class="thumbnail"  src="'.$upl_dir['baseurl'].'/'.$dir_img.'/'.$meta['sizes'][$size]['file'].'">';
		}

	}

	$image .= '<a href="'.$upl_dir['baseurl'].'/'.$meta['file'].'" rel="lightbox">';
	$image .= $img;
	$image .= '</a>';
	return $image;
}
add_shortcode('art','get_single_image_gallery_rcl');

function add_attachments_in_temps($id_post){
    global $user_ID;

    $temp_gal = unserialize(get_the_author_meta('tempgallery',$user_ID));
    if($temp_gal){
            //$cnt = count($temp_gal);
            foreach((array)$temp_gal as $key=>$gal){
                    if($thumb[$gal['ID']]==1) add_post_meta($id_post, '_thumbnail_id', $gal['ID']);
                    wp_update_post( array('ID'=>$gal['ID'],'post_parent'=>$id_post) );
            }
            if($_POST['add-gallery-rcl']==1) add_post_meta($id_post, 'recall_slider', 1);
            delete_usermeta($user_ID,'tempgallery');

            if(!$thumb){
                $args = array(
                'post_parent' => $id_post,
                'post_type'   => 'attachment',
                'numberposts' => 1,
                'post_status' => 'any',
                'post_mime_type'=> 'image'
                );
                $child = get_children($args);
                if($child){ foreach($child as $ch){add_post_meta($id_post, '_thumbnail_id',$ch->ID);} }
            }
    }
    return $temp_gal;
}

function update_tempgallery_rcl($attach_id,$attach_url){
	global $user_ID;
	$temp_gal = unserialize(get_the_author_meta('tempgallery',$user_ID));
	if(!$temp_gal) $temp_gal = array();
	$cnt = count($temp_gal);
	$temp_gal[$cnt]['ID'] = $attach_id;
	$temp_gal[$cnt]['url'] = $attach_url;
	update_usermeta($user_ID,'tempgallery',serialize($temp_gal));
	return $temp_gal;
}

function insert_post_attachment_rcl($attachment,$image,$id_post=false){
	$attach_id = wp_insert_attachment( $attachment, $image['file'], $id_post );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	if(!$id_post) update_tempgallery_rcl($attach_id,$image['url']);

	return get_html_post_attach_rcl($attach_id,$attachment['post_mime_type']);
}

function get_html_post_attach_rcl($attach_id,$mime_type){

        $editpost = $_GET['rcl-post-edit'];

	$mime = explode('/',$mime_type);

	$rt = "<li id='li-".$attach_id."'>
		<span title='".__("Delete?",'rcl')."' class='delete'></span>
		<label>
			".get_insert_image_rcl($attach_id,$mime[0]);
			if($mime[0]=='image') $rt .= "<span>
				<input type='checkbox' class='thumb-foto' ".checked(get_post_thumbnail_id( $editpost ),$attach_id,false)." id='thumb-".$attach_id."' name='thumb[".$attach_id."]' value='1'> - главное
			</span>";
		$rt .= "</label>
	</li>";
	return $rt;
}

function edit_post_rcl_recall(){
    global $user_ID;
    if(!$user_ID) return false;

    include_once 'rcl_editpost.php';
    $edit = new Rcl_EditPost();
}

function edit_post_rcl_recall_activate ( ) {
  if ( isset( $_POST['edit-post-rcl'] )&&wp_verify_nonce( $_POST['_wpnonce'], 'edit-post-rcl' ) ) {
    add_action( 'wp', 'edit_post_rcl_recall' );
  }
}
add_action('init', 'edit_post_rcl_recall_activate');

function delete_post_rcl_recall(){
	global $rcl_options;
	$post = wp_update_post( array('ID'=>$_POST['post-rcl'],'post_status'=>'trash'));
        do_action('after_delete_post_rcl',$post);
	wp_redirect(get_redirect_url_rcl(get_author_posts_url($user_ID)).'&public=deleted');
	exit;
}

add_action('wp','add_notify_public_deleted');
function add_notify_public_deleted(){
    if (isset($_GET['public'])&&$_GET['public']=='deleted') add_notify_rcl(__('The publication has been successfully removed!','rcl'),'warning');
}

function delete_post_rcl_recall_activate ( ) {
  if ( isset( $_POST['delete-post-rcl'] )&&wp_verify_nonce( $_POST['_wpnonce'], 'delete-post-rcl' ) ) {
    add_action( 'wp', 'delete_post_rcl_recall' );
  }
}
add_action('init', 'delete_post_rcl_recall_activate');

function public_form_recall($atts, $content = null){
    include_once 'rcl_publicform.php';
    $form = new Rcl_PublicForm($atts);
    return $form->public_form();
}
add_shortcode('public-form','public_form_recall');

add_action('admin_init', 'custom_fields_editor_post_rcl', 1);
function custom_fields_editor_post_rcl() {
    add_meta_box( 'custom_fields_editor_post', __('Arbitrary form fields publishing','rcl'), 'custom_fields_list_posteditor_rcl', 'post', 'normal', 'high'  );
}

function custom_fields_list_posteditor_rcl($post){
	echo get_custom_fields_list_rcl($post->ID); ?>
	<input type="hidden" name="custom_fields_nonce_rcl" value="<?php echo wp_create_nonce(__FILE__); ?>" />
	<?php
}

add_action('save_post', 'custom_fields_fields_update_rcl', 0);
function custom_fields_fields_update_rcl( $post_id ){
    if(!isset($_POST['custom_fields_nonce_rcl'])) return false;
    if ( !wp_verify_nonce($_POST['custom_fields_nonce_rcl'], __FILE__) ) return false;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false;
	if ( !current_user_can('edit_post', $post_id) ) return false;

	edit_custom_fields_post_rcl($post_id);

	return $post_id;
}

function edit_custom_fields_post_rcl($post_id,$id_form=false){

	$post = get_post($post_id);

	switch($post->post_type){
            case 'post':
                if(!$id_form){
                        $id_form = get_post_meta($post->ID,'publicform-id',1);
                        if(!$id_form) $id_form = 1;
                }
                $id_field = 'custom_public_fields_'.$id_form;
            break;
            case 'products': $id_field = 'custom_saleform_fields'; break;
            default: $id_field = 'custom_fields_'.$post->post_type;
	}

	$get_fields = get_option($id_field);

	if($get_fields){
            foreach((array)$get_fields as $custom_field){
                $slug = $custom_field['slug'];
                if($custom_field['type']=='checkbox'){
                    $select = explode('#',$custom_field['field_select']);
                    $count_field = count($select);
                    foreach($_POST[$slug] as $val){
                        for($a=0;$a<$count_field;$a++){
                            if($select[$a]==$val){
                                $vals[] = $val;
                            }
                        }
                    }
                    if($vals){
                        $res = update_post_meta($post_id, $slug, $vals);
                    }else{
                        delete_post_meta($post_id, $slug);
                    }

                }else{

                    if($_POST[$slug]){
                        update_post_meta($post_id, $slug, $_POST[$slug]);
                    }else{
                        if(get_post_meta($post_id, $slug, 1)) delete_post_meta($post_id, $slug);
                    }

                }
            }
	}
}

function get_custom_fields_list_rcl($post_id,$posttype=false,$id_form=false){

	$get_fields = get_custom_fields_rcl($post_id,$posttype,$id_form);

	if(!$get_fields) return false;

        $public_fields = '';

        $data = array(
            'ID'=>$post_id,
            'post_type'=>$posttype,
            'form_id'=>$id_form
        );

        $cf = new Rcl_Custom_Fields();

	foreach((array)$get_fields as $key=>$custom_field){
                if($key==='options') continue;

                $custom_field = apply_filters('custom_field_public_form',$custom_field,$data);

                $star = ($custom_field['requared']==1)? ' <span class="required">*</span> ': '';
		$postmeta = ($post_id)? get_post_meta($post_id,$custom_field['slug'],1):'';

		$public_fields .= '<tr><th><label>'.$custom_field['title'].$star.':</label></th>';
		$public_fields .= '<td>'.$cf->get_input($custom_field,$postmeta).'</td>';
		$public_fields .= '</tr>';
	}

	if(isset($public_fields)){
            $public_fields = '<table>'.$public_fields.'</table>';
            return $public_fields;
        }else{
            return false;
        }

}

if(!is_admin()) add_filter('get_edit_post_link','rcl_edit_post_link',100,2);
function rcl_edit_post_link($admin_url, $post_id){
	global $user_ID,$rcl_options;
	get_currentuserinfo();
	$access = 7;
	if(isset($rcl_options['consol_access_rcl'])&&$rcl_options['consol_access_rcl']) $access = $rcl_options['consol_access_rcl'];
	$user_info = get_userdata($user_ID);
        //echo $user_info->user_level.' - '.$access;exit;
	if ( $user_info->user_level < $access ){
		$edit_url = get_redirect_url_rcl(get_permalink($rcl_options['public_form_page_rcl']));
		return $edit_url.'rcl-post-edit='.$post_id;
	}else{
		return $admin_url;
	}
}

function get_edit_post_link_rcl($content){
	global $post,$user_ID;
	if(is_tax('groups')||$post->post_type=='page') return $content;

	if( $post->post_author==$user_ID ) {

            if($post->post_type=='task'){
                if(get_post_meta($post->ID,'step_order',1)!=1) return $content;
            }

            $content = get_post_edit_button_rcl($post->ID).$content;
	}
	return $content;
}
add_filter('the_content','get_edit_post_link_rcl',999);
add_filter('the_excerpt','get_edit_post_link_rcl',999);

function get_post_edit_button_rcl($post_id){
    return '<p class="post-edit-button">'
        . '<a title="Редактировать" object-id="none" href="'. get_edit_post_link($post_id) .'">'
            . '<i class="fa fa-pencil-square-o"></i>'
        . '</a>'
    . '</p>';
}

function get_footer_scripts_public_rcl($script){
	global $rcl_options;
	$weight = (isset($rcl_options['public_gallery_weight'])&&$rcl_options['public_gallery_weight'])? $rcl_options['public_gallery_weight']: $weight = '2';
	$cnt = (isset($rcl_options['count_image_gallery'])&&$rcl_options['count_image_gallery'])? $rcl_options['count_image_gallery']: 1;

	$script .= "
	var post_id_edit = jQuery('input[name=\"post-rcl\"]').val();
	jQuery('#postupload').fileapi({
		   url: rcl_url+'add-on/publicpost/upload-file.php?post_id='+post_id_edit,
		   multiple: true,
		   maxSize: ".$weight." * FileAPI.MB,
		   maxFiles:".$cnt.",
		   clearOnComplete:true,
		   autoUpload: true,
		   paramName:'uploadfile',
		   accept: 'image/*',
		   elements: {
			  ctrl: { upload: '.js-upload' },
			  empty: { show: '.b-upload__hint' },
			  emptyQueue: { hide: '.js-upload' },
			  list: '.js-files',
			  file: {
				 tpl: '.js-file-tpl',
				 preview: {
					el: '.b-thumb__preview',
					width: 100,
					height: 100
				 },
				 upload: { show: '.progress', hide: '.b-thumb__rotate' },
				 complete: { hide: '.progress' },
				 progress: '.progress .bar'
				},
			  dnd: {
				 el: '.b-upload__dnd',
				 hover: 'b-upload__dnd_hover',
				 fallback: '.b-upload__dnd-not-supported'
			  }
		   },
		   onSelect: function (evt, data){
				data.all;
				data.files;
				if( data.other.length ){
					var errors = data.other[0].errors;
					if( errors ){
						if(errors.maxSize) alert('Превышен допустимый размер файла.\nОдин файл не более ".$weight."MB');
					}
				}
			},
			onFilePrepare:function(evt, uiEvt){";
			if($cnt){
				$script .= "var num = jQuery('#temp-files li').size();
				if(num>=".$cnt."){
					jQuery('#status-temp').html('<span style=\"color:red;\">Вы уже достигли предела загрузок</span>');
					jQuery('#postupload').fileapi('abort');
				}";
			}
			$script .= "},
			onFileComplete:function(evt, uiEvt){
				var result = uiEvt.result;
				if(result['string']){
					jQuery('#temp-files').append(result['string']);";
				if($cnt){
					$script .= "var num = jQuery('#temp-files li').size();
					if(num>=".$cnt."){
						jQuery('#status-temp').html('<span style=\"color:red;\">Вы уже достигли предела загрузок</span>');
						jQuery('#postupload').fileapi('abort');
					}";
				}
			$script .= "}
			},
			onComplete:function(evt, uiEvt){
				var result = uiEvt.result;
				jQuery('#postupload .js-files').empty();
			}
	});";
	return $script;
}
add_filter('file_footer_scripts_rcl','get_footer_scripts_public_rcl');

function get_scripts_public_rcl($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";
	$ajaxfile = "type: 'POST', data: dataString, dataType: 'json', url: rcl_url+'add-on/publicpost/ajax-request.php',";

	$script .= "
		jQuery('form[name=\'public_post\'] input[name=\'edit-post-rcl\'],form[name=\'public_post\'] input[name=\'add_new_task\']').click(function(){
			var error=0;
			jQuery('form[name=\'public_post\']').find(':input').each(function() {
				for(var i=0;i<field.length;i++){
					if(jQuery(this).attr('name')==field[i]){
						if(jQuery(this).val()==''){
							jQuery(this).attr('style','border:1px solid red !important');
							error=1;
						}else{
							jQuery(this).attr('style','border:1px solid #E6E6E6 !important');
						}
					}
				}
			});
			if(error==0) return true;
			else return false;
		});
		jQuery('#rcl-popup .rcl-navi a').live('click',function(){
			var page = jQuery(this).text();
			var dataString = 'action=get_media&user_ID='+user_ID+'&page='+page;

			jQuery.ajax({
				".$ajaxfile."
				success: function(data){
					if(data['result']==100){
						jQuery('#rcl-overlay').fadeIn();
						jQuery('#rcl-popup').html(data['content']);
						var screen_top = jQuery(window).scrollTop();
						var popup_h = jQuery('#rcl-popup').height();
						var window_h = jQuery(window).height();
						screen_top = screen_top + 60;
						jQuery('#rcl-popup').css('top', screen_top+'px').delay(100).slideDown(400);
					}else{
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
		jQuery('#get-media-rcl').live('click',function(){
			var dataString = 'action=get_media&user_ID='+user_ID;

			jQuery.ajax({
				".$ajaxfile."
				success: function(data){
					if(data['result']==100){
						jQuery('#rcl-overlay').fadeIn();
						jQuery('#rcl-popup').html(data['content']);
						var screen_top = jQuery(window).scrollTop();
						var popup_h = jQuery('#rcl-popup').height();
						var window_h = jQuery(window).height();
						screen_top = screen_top + 60;
						jQuery('#rcl-popup').css('top', screen_top+'px').delay(100).slideDown(400);
					}else{
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
	/* Первый шаг редактирования поста */
		jQuery('.edit-post-button').live('click',function(){
			var edit_post = jQuery(this).attr('data-post');
			var dataString = 'action=step_one_redactor_post&post_id='+edit_post+'&user_ID='+user_ID;

			jQuery.ajax({
				".$ajaxfile."
				success: function(data){
					if(data['result']==100){
						jQuery('#rcl-overlay').fadeIn();
						jQuery('#rcl-popup').html(data['content']);
						var screen_top = jQuery(window).scrollTop();
						var popup_h = jQuery('#rcl-popup').height();
						var window_h = jQuery(window).height();
						screen_top = screen_top + 60;
						jQuery('#rcl-popup').css('top', screen_top+'px').delay(100).slideDown(400);
					}else{
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
	/* Второй шаг редактирования поста */
		jQuery('.updatesteptwo').live('click',function(){
			var post_title = jQuery('#post_title_edit').attr('value');
			var post_content = jQuery('#content_area_edit').attr('value');
			var post_post_id = jQuery('#post_id_edit').attr('value');
			var dataString = 'action=step_two_redactor_post&post_title='+post_title+'&post_content='+post_content+'&post_id='+post_post_id+'&user_ID='+user_ID;;

			jQuery.ajax({
			".$ajaxfile."
			success: function(data){
				if(data['otvet']==100){
					jQuery('#rcl-popup').html('<div class=\'float-window-recall\' style=\'display:block;\'><p style=\'padding:5px;text-align:center;background:#fff\'>Материал обновлен</p></div>');
					jQuery('#rcl-popup').delay(1000).fadeOut(1000);
					jQuery('#rcl-overlay').delay(1500).fadeOut(1000);
					jQuery('#post-title-'+data['post_id']).html(data['post_title']);
				} else {
				   alert('Нет данных.');
				}
			}
			});
			return false;
		});
	/* Удаление поста */
		jQuery('.delete-post-button').live('click',function(){
			if(confirm('Действительно удалить?')){
				var del_post = jQuery(this).attr('data-post');
				jQuery('#post-'+del_post).remove();
				var dataString = 'action=delete_redactor_post&post_id='+ del_post;
				jQuery.ajax({
					".$ajaxdata."
				});
			}
			return false;
		});
		jQuery('ul#temp-files li .delete').live('click',function(){
			var id_attach = parseInt(jQuery(this).parent().attr('id').replace(/\D+/g,''));
			var dataString = 'action=delete_redactor_post&post_id='+ id_attach;

			jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['result']==100){
						jQuery('#temp-files #li-'+id_attach).remove();
						jQuery('#status-temp').empty();
					}else{
						alert('Ошибка!');
					}
				}
			});
			return false;
		});
		jQuery('.posts_rcl_block .sec_block_button').live('click',function(){
			var btn = jQuery(this);
			get_page_content_rcl(btn,'posts_rcl_block');
			return false;
		});
	function get_page_content_rcl(btn,id_page_rcl){
			if(btn.hasClass('active'))return false;
			var start = btn.attr('data');
			var type = btn.attr('type');
			var id_user = parseInt(jQuery('.wprecallblock').attr('id').replace(/\D+/g,''));
			jQuery('.'+id_page_rcl+' .sec_block_button').removeClass('active');
			btn.addClass('active');
			var dataString = 'action=get_page_content_rcl&start='+start+'&type='+type+'&id_user='+id_user;
			jQuery.ajax({
				type: 'POST', data: dataString, dataType: 'json', url: rcl_url+'functions/posts-list.php',
				success: function(data){
					if(data['recall']==100){
						jQuery('.'+id_page_rcl+' .publics-table-rcl').html(data['post_content']);
					} else {
						alert('Error');
					}
				}
			});
			return false;
	}
		";
	return $script;
}
add_filter('file_scripts_rcl','get_scripts_public_rcl');
