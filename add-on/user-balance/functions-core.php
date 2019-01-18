<?php

function Rcl_Gateways(){
    global $rcl_gateways;
    return $rcl_gateways;
}

function rcl_gateway_register($gateway_id, $gatewayClassName){
    Rcl_Gateways()->add_gateway($gateway_id, $gatewayClassName);
}

//получение данных из таблицы произведенных платежей
function rcl_get_payments($args = false){
    require_once 'classes/class-rcl-payments.php';
    $payments = new Rcl_Payments();
    return $payments->get_results($args);
}

function rcl_payform($attr){
    return rcl_get_pay_form($attr);
}

function rcl_get_user_balance($user_id=false){
    global $wpdb,$user_ID;

    if(!$user_id) $user_id = $user_ID;

    $balance = $wpdb->get_var($wpdb->prepare("SELECT user_balance FROM ".RMAG_PREF."users_balance WHERE user_id='%d'",$user_id));

    return $balance;
}

function rcl_update_user_balance($newmoney,$user_id,$comment=''){
    global $wpdb;

    $newmoney = round(str_replace(',','.',$newmoney), 2);

    $money = rcl_get_user_balance($user_id);

    if(isset($money)){

        do_action('rcl_pre_update_user_balance',$newmoney,$user_id,$comment);

        $result = $wpdb->update(RMAG_PREF .'users_balance',
            array( 'user_balance' => $newmoney ),
            array( 'user_id' => $user_id )
        );

        if(!$result){
            rcl_add_log(
                'rcl_update_user_balance: '.__('Failed to refresh user balance','wp-recall'),
                array($newmoney,$user_id,$comment)
            );
        }

        return $result;

    }

    return rcl_add_user_balance($newmoney,$user_id,$comment);
}

function rcl_add_user_balance($money,$user_id,$comment=''){
    global $wpdb;

    $result =  $wpdb->insert( RMAG_PREF .'users_balance',
	array( 'user_id' => $user_id, 'user_balance' => $money ));

    if(!$result){
        rcl_add_log(
            'rcl_add_user_balance: '.__('Failed to add user balance','wp-recall'),
            array($money,$user_id,$comment)
        );
    }

    do_action('rcl_add_user_balance',$money,$user_id,$comment);

    return $result;
}

function rcl_get_html_usercount(){
    global $user_ID,$rmag_options;

    $id = rand(1,100);

    $usercount = '<div class="rcl-widget-balance" id="rcl-widget-balance-'.$id.'">';

    $user_count = rcl_get_user_balance();
    if(!$user_count) $user_count = 0;

    $usercount .= '<div class="rcl-usercount usercount"><span class="rcl-usercount-num">'.$user_count.'</span>'.rcl_get_primary_currency(1).'</div>';

    $usercount = apply_filters('count_widget_rcl',$usercount);

    if($rmag_options['connect_sale']!='')
        $usercount .= "<div class='rcl-toggle-form-balance'>"
                . rcl_get_button(array(
                    'label' => __("Top up",'wp-recall'),
                    'class' => 'rcl-toggle-form-link'
                ))
                ."</div>
            <div class='rcl-form-balance'>
                ".rcl_form_user_balance(array('idform'=>$id))."
            </div>";

    $usercount .= '</div>';

    return $usercount;
}

function rcl_mail_payment_error($hash=false,$other=false){
    global $rmag_options,$post;

    if($other){
        foreach($other as $k=>$v){
            $textmail .= $k.' - '.$v.'<br>';
        }
    }

    foreach($_REQUEST as $key=>$R){
        $textmail .= $key.' - '.$R.'<br>';
    }

    if($hash){
        $textmail .= 'Cформированный хеш - '.$hash.'<br>';
        $title = 'Неудачная оплата';
    }else{
        $title = 'Данные платежа';
    }

    $textmail .= 'Текущий пост - '.$post->ID.'<br>';
    $textmail .= 'RESULT - '.$rmag_options['page_result_pay'].'<br>';
    $textmail .= 'SUCCESS - '.$rmag_options['page_success_pay'].'<br>';

    $email = get_option( 'admin_email' );

    rcl_mail($email, $title, $textmail);
}

