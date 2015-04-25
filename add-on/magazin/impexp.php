<?php
$path_parts = pathinfo(__FILE__);
$url_ar = explode('/',$path_parts['dirname']);
if(!$url_ar[1]) $url_ar = explode('\\',$path_parts['dirname']);
for($a=count($url_ar);$a>=0;$a--){if($url_ar[$a]=='wp-content'){ $path .= 'wp-load.php'; break; }else{ $path .= '../'; }}
require_once( $path );

global $wpdb;
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'get-csv-file' ) ) exit;
	$file_name = 'products.xml';
	$file_src    = plugin_dir_path( __FILE__ ).'xml/'.$file_name;

	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

	$sql_field = "ID";
	if($_POST['post_title']==1) $sql_field .= ',post_title';
	if($_POST['post_content']==1) $sql_field .= ',post_content';
	$sql_field .= ',post_status';

	$posts = $wpdb->get_results("SELECT $sql_field FROM ".$wpdb->prefix ."posts WHERE post_type = 'products' AND post_status!='draft'");
	$postmeta = $wpdb->get_results("SELECT meta_key FROM ".$wpdb->prefix ."postmeta GROUP BY meta_key ORDER BY meta_key");

	$sql_field = explode(',',$sql_field);
	$cnt = count($sql_field);

	if($posts){
	$xml .= "<posts>\n";
		foreach($posts as $post){
			$trms = array();
			$xml .= "<post>\n";
			for($a=0;$a<$cnt;$a++){
				$xml .= "<".$sql_field[$a].">";
				if($a==0) $xml .= $post->$sql_field[$a];
				else $xml .= "<![CDATA[".$post->$sql_field[$a]."]]>";
				$xml .= "</".$sql_field[$a].">\n";
			}
			foreach ($postmeta as $key){
				if (strpos($key->meta_key, "goods_id") === FALSE && strpos($key->meta_key , "_") !== 0){
					if($_POST[$key->meta_key]==1){
						$xml .= "<".$key->meta_key.">";
						$xml .= "<![CDATA[".get_post_meta($post->ID, $key->meta_key, true)."]]>";
						$xml .= "</".$key->meta_key.">\n";
					}
				}
			}

			$terms = get_the_terms( $post->ID, 'prodcat' );
			$xml .= "<prodcat>";
			if($terms){
				foreach($terms as $term){
					$trms[] = $term->term_id;
				}
				$xml .= "<![CDATA[".implode(',',$trms)."]]>";
			}else{
				$xml .= "<![CDATA[0]]>";
			}
			$xml .= "</prodcat>\n";

			$xml .= "</post>\r";
		}
	$xml .= "</posts>";
	}

	$f = fopen($file_src, 'w');
	if(!$f)exit;
	fwrite($f, $xml);
	fclose($f);

	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename="'.$file_name.'"');
	header('Content-Type: text/xml; charset=utf-8');
	readfile($file_src);
	exit;
?>