<?php
require_once( '../../load-rcl.php' );
require_once( 'index.php' );
if($action=='get_comments_feed_recall')include('../rayting/index.php');
$rcl_feed->$action();