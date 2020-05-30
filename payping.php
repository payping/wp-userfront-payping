<?php
if ( !defined('ABSPATH') ) exit;

if ( !class_exists('WPUF_PayPing') ) {

    class WPUF_PayPing
    {
        private $payment_status;
        private $payment_message;

        //---wpuf.ir---
        public function __construct()
        {
            $this->payment_status = '';
            $this->payment_message = '';

            add_filter('wpuf_payment_gateways', array($this, 'WPUF_PayPing_Setup_Sht'));
            add_action('wpuf_options_payment', array($this, 'WPUF_PayPing_Options_Sht'));
            add_action('wpuf_gateway_PayPing', array($this, 'WPUF_PayPing_Request_Sht'));
            add_action('init', array($this, 'WPUF_PayPing_Callback_Sht'));
            add_filter('gettext', array($this, 'WPUF_PayPing_Text_Sht'));

            if ( !function_exists('WPUF_Iranian_Currencies_Sht') ) {
                add_action('wpuf_options_payment', array($this, 'WPUF_Iranian_Currencies_Sht'));
            }
        }

        //---wpuf.ir---
        public function WPUF_Iranian_Currencies_Sht( $settings )
        {

            foreach ( (array)$settings as $setting ) {

                if ( in_array('currency', $setting) ) {

                    $setting['default'] = 'IRT';
                    $iran_currencies = array(
                        'IRR' => __('ریال ایران', 'wpuf'),
                        'IRT' => __('تومان ایران', 'wpuf'),
                    );
                    $setting['options'] = array_unique(array_merge($iran_currencies, $setting['options']));
                }

                if ( in_array('currency_symbol', $setting) ) {
                    $setting['default'] = __('تومان', 'wpuf');
                }

                $settings[] = $setting;
            }

            return $settings;
        }

        //---wpuf.ir---
        public function WPUF_PayPing_Setup_Sht( $gateways )
        {

            $gateways['PayPing'] = array(
                'admin_label' => function_exists('wpuf_get_option') ? ( wpuf_get_option('PayPing_name', 'wpuf_payment') ? wpuf_get_option('PayPing_name', 'wpuf_payment') : __('پی‌پینگ', 'wpuf') ) : '',
                'checkout_label' => function_exists('wpuf_get_option') ? ( wpuf_get_option('PayPing_name', 'wpuf_payment') ? wpuf_get_option('PayPing_name', 'wpuf_payment') : __('پی‌پینگ', 'wpuf') ) : '',
                'icon' => apply_filters('wpuf_PayPing_checkout_icon', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/logo.png')
            );

            return $gateways;
        }


        //---wpuf.ir---
        public function WPUF_PayPing_Options_Sht( $options )
        {

            $options[] = array(
                'name' => 'PayPing_header',
                'label' => __('پیکربندی درگاه پی‌پینگ', 'wpuf'),
                'type' => 'html',
                'desc' => '<hr/>'
            );

            $options[] = array(
                'name' => 'TokenCode',
                'label' => __('توکن پی‌پینگ', 'wpuf')
            );

            $options[] = array(
                'name' => 'PayPing_name',
                'label' => __('نام نمایشی درگاه', 'wpuf'),
                'default' => __('پی‌پینگ', 'wpuf'),
            );

            $options[] = array(
                'name' => 'PayPing_query',
                'label' => __('نام لاتین درگاه', 'wpuf'),
                'default' => 'PayPing',
                'desc' => __('<br/>این نام در هنگام بازگشت از بانک در آدرس بازگشت از بانک نمایان خواهد شد . از به کاربردن حروف زائد و فاصله جدا خودداری نمایید .', 'wpuf')
            );

            $options[] = array(
                'name' => 'PayPing_transaction',
                'label' => __('ثبت تراکنش ها', 'wpuf'),
                'desc' => sprintf(__('ثبت تراکنش های ناموفق علاوه بر تراکنش های موفق در %s', 'wpuf'), '<a href="' . admin_url('admin.php?page=wpuf_transaction') . '" target="_blank">لیست تراکنش ها</a>'),
                'type' => 'checkbox',
                'default' => 'on'
            );

            $options[] = array(
                'name' => 'gate_instruct_PayPing',
                'label' => __('توضیحات درگاه پی‌پینگ', 'wpuf'),
                'type' => 'textarea',
                'default' => __('پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه پی‌پینگ', 'wpuf'),
                'desc' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'wpuf'),
            );

            $options[] = array(
                'name' => 'PayPing_success',
                'label' => __('متن تراکنش پرداخت موفق', 'wpuf'),
                'type' => 'textarea',
                'desc' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید. همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (شماره ارجاع) پی‌پینگ استفاده نمایید.', 'wpuf'),
                'default' => __('با تشکر از شما . سفارش شما با موفقیت پرداخت شد.', 'wpuf'),
            );

            $options[] = array(
                'name' => 'PayPing_failed',
                'label' => __('متن تراکنش پرداخت ناموفق', 'wpuf'),
                'type' => 'textarea',
                'desc' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید. همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید. این دلیل خطا از سایت پی‌پینگ ارسال میگردد.', 'wpuf'),
                'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.', 'wpuf'),
            );

            $options[] = array(
                'name' => 'PayPing_footer',
                'label' => '<hr/>',
                'type' => 'html',
                'desc' => '<hr/>'
            );

            return apply_filters('wpuf_PayPing_options', $options);
        }

        //---wpuf.ir---
        public function WPUF_PayPing_Request_Sht( $data )
        {
            ob_start();

            //item detail
            $item_number = !empty($data['item_number']) ? $data['item_number'] : 0;
            $item_name = !empty($data['custom']['post_title']) ? $data['custom']['post_title'] : $data['item_name'];

            $post_id = $data['type'] == 'post' ? $item_number : 0;
            $pack_id = $data['type'] == 'pack' ? $item_number : 0;

            $user_id = 0;
            $form_id = 0;
            if ( isset($data['post_data']) && isset($data['post_data']['post_id']) ) {
                $_post_id = $data['post_data']['post_id'];
                $post_object = get_post($_post_id);
                $user_id = $post_object->post_author;
                $form_id = get_post_meta($_post_id, '_wpuf_form_id', true);
            }

            global $current_user;
            if ( is_object($current_user) && !empty($current_user->ID) && $current_user->ID != 0 ) {
                $user_id = $current_user->ID;
            }

            $user_name = __('مهمان', 'wpuf');
            $user_email = '';
            if ( $user_id && $user_data = get_userdata($user_id) ) {
                $user_name = $user_data->user_login;
                $user_email = $user_data->user_email;
            }

            $user_id = !empty($data['user_info']['id']) ? $data['user_info']['id'] : $user_id;
            $user_email = !empty($data['user_info']['email']) ? $data['user_info']['email'] : $user_email;
            $first_name = !empty($data['user_info']['first_name']) ? $data['user_info']['first_name'] : '';
            $last_name = !empty($data['user_info']['last_name']) ? $data['user_info']['last_name'] : '';
            $full_user = $first_name . ' ' . $last_name;
            $full_user = strlen($full_user) > 5 ? $full_user : $user_name;

            $query = 'PayPing';
            $currency = '';
            $redirect_page_id = 0;
            $subscription_page_id = 0;
            $TokenCode = $UserName = $PassWord = '';
            $set_transaction = false;
            $payment_gateway = __('پی‌پینگ', 'wpuf');

            if ( function_exists('wpuf_get_option') ) {

                $currency = wpuf_get_option('currency', 'wpuf_payment');
                $query = wpuf_get_option('PayPing_query', 'wpuf_payment');
                $set_transaction = wpuf_get_option('PayPing_transaction', 'wpuf_payment');
                $payment_gateway = wpuf_get_option('PayPing_name', 'wpuf_payment');
                $TokenCode = wpuf_get_option('TokenCode', 'wpuf_payment');
                $redirect_page_id = wpuf_get_option('payment_success', 'wpuf_payment');
                $subscription_page_id = wpuf_get_option('subscription_page', 'wpuf_payment');
            }

            if( $redirect_page_id ){
                $Return_url = add_query_arg('pay_method', $query, get_permalink($redirect_page_id));
            }else{
                $Return_url = add_query_arg('pay_method', $query, get_permalink($subscription_page_id));
            }

            //currency
            $currency = !empty($data['currency']) ? $data['currency'] : $currency;

            //price
            $price = empty($data['price']) ? 0 : intval(str_replace(',', '', $data['price']));

            //coupon
            $coupon_id = '';
            if ( !empty($_POST['coupon_id']) ) {
                $coupon_id = $_POST['coupon_id'];
                if ( get_post_meta($coupon_id, '_coupon_used', true) >= get_post_meta($coupon_id, '_usage_limit', true) ) {
                    wp_die(__('تعداد دفعات استفاده از کد تخفیف به اتمام رسیده است .', 'wpuf'));
                    return false;
                }
                $price = WPUF_Coupons::init()->discount($price, $_POST['coupon_id'], $data['item_number']);
            }
            $price = intval($price);


            //store
            $fg_data = array();

            if ( !empty($data['type']) )
                $fg_data = array_merge($fg_data, array('type' => $data['type']));

            if ( !empty($price) )
                $fg_data = array_merge($fg_data, array('price' => $price));

            if ( !empty($currency) )
                $fg_data = array_merge($fg_data, array('currency' => $currency));

            if ( !empty($item_number) )
                $fg_data = array_merge($fg_data, array('item_number' => $item_number));

            if ( !empty($user_id) )
                $fg_data = array_merge($fg_data, array('user_id' => $user_id));

            if ( !empty($user_email) )
                $fg_data = array_merge($fg_data, array('user_email' => $user_email));

            if ( !empty($full_user) )
                $fg_data = array_merge($fg_data, array('full_user' => $full_user));

            if ( !empty($coupon_id) )
                $fg_data = array_merge($fg_data, array('coupon_id' => $coupon_id));

            if ( !empty($first_name) )
                $fg_data = array_merge($fg_data, array('first_name' => $first_name));

            if ( !empty($last_name) )
                $fg_data = array_merge($fg_data, array('last_name' => $last_name));

            if ( !empty($form_id) )
                $fg_data = array_merge($fg_data, array('form_id' => $form_id));

            if ( !empty($_post_id) )
                $fg_data = array_merge($fg_data, array('_post_id' => $_post_id));


            $payment_data = $this->json_encode($fg_data);
            $this->WPUF_PayPing_Data_Sht('set', $payment_data);

            if( $set_transaction == 'on' ){

                $data = array(
                    'user_id' => $user_id,
                    'status' => __('pending', 'wpuf'),
                    'cost' => $price,
                    'post_id' => $post_id,
                    'pack_id' => $pack_id,
                    'payer_first_name' => !empty($first_name) ? $first_name : $full_user,
                    'payer_last_name' => !empty($last_name) ? $last_name : null,
                    'payer_email' => $user_email,
                    'payment_type' => $payment_gateway,
                    'payer_address' => null,
                    'transaction_id' => $item_number,
                    'created' => current_time('mysql'),
                    'profile_id' => $user_id,
                );

                $this->insert_payment($data, $item_number, false);
            }

            do_action('WPUF_PayPing_before_Proccessing_to_Sending', $data);

			if( $price == 0 ){
                WPUF_Subscription::init()->new_subscription($user_id, $item_number, $profile_id = null, false, 'free');
                wp_redirect(add_query_arg('pay', 'no', $Return_url));
                exit();
            }

            //srart of PayPing
            $Amount = intval($price);
            if ( strtolower($currency) == 'irr' )
                $Amount = $Amount/10 ;

            $Description = sprintf( __('پرداخت برای آیتم به شماره %s برای کاربر %s | نام آیتم : %s', 'wpuf' ), $item_number, $full_user, $item_name );
            $Email = $user_email;
            $Mobile = '';
			if( !empty( $Mobile ) ){
				$payerIdentity = $Mobile;
			}else{
				$payerIdentity = $Email;
			}
			
            //Hooks for iranian developer
            $Description = apply_filters('WPUF_PayPing_Description', $Description, $fg_data);
            $Email = apply_filters('WPUF_PayPing_Email', $Email, $fg_data);
            $Mobile = apply_filters('WPUF_PayPing_Mobile', $Mobile, $fg_data);
            do_action('WPUF_PayPing_Gateway_Payment', $fg_data, $Description, $Email, $Mobile);
            do_action('WPUF_Gateway_Payment', $fg_data);
			
			$data = array(
					'payerName'     => $full_user,
					'Amount'        => $Amount,
					'payerIdentity' => $payerIdentity ,
					'returnUrl'     => $Return_url,
					'Description'  	=> $Description,
					'clientRefId'   => date('ymdHis')
				);

                $args = array(
                    'body'            => json_encode( $data ),
                    'timeout'         => '45',
                    'redirection'     => '5',
                    'httpsversion'    => '1.0',
                    'blocking'        => true,
	               'headers'          => array(
		              'Authorization' => 'Bearer ' . $TokenCode,
		              'Content-Type'  => 'application/json',
		              'Accept' 		  => 'application/json'
		              ),
                    'cookies' 		  => array()
                );
				
				$response = wp_remote_post( 'https://api.payping.ir/v2/pay', $args );

				$XPP_ID = $response["headers"]["x-paypingrequest-id"];
				if( is_wp_error($response) ){
					echo $response->get_error_message();
				}else{
					$code = wp_remote_retrieve_response_code( $response );
					if( $code === 200){
						if (isset($response["body"]) and $response["body"] != '') {
							$code_pay = wp_remote_retrieve_body($response);
							$code_pay =  json_decode($code_pay, true);
							wp_redirect(sprintf('https://api.payping.ir/v2/pay/gotoipg/%s', $code_pay["code"]));
							exit;
						} else {
							echo ' تراکنش ناموفق بود- کد خطا : '.$XPP_ID;
						}
					}elseif( $code == 400){
						echo wp_remote_retrieve_body( $response ).'<br /> کد خطا: '.$XPP_ID;
					}else{
						echo wp_remote_retrieve_body( $response ).'<br /> کد خطا: '.$XPP_ID;
					}
				}
            //end of PayPing
        }
        //---wpuf.ir---
        public function WPUF_PayPing_Callback_Sht()
        {

            $query = function_exists('wpuf_get_option') ? ( wpuf_get_option('PayPing_query', 'wpuf_payment') ? wpuf_get_option('PayPing_query', 'wpuf_payment') : 'PayPing' ) : '';
            if( !empty($_GET['pay_method']) && $_GET['pay_method'] == $query ){

                //from store
                $PayPing_data = $this->WPUF_PayPing_Data_Sht('call', '');
                $payment_data = json_decode(stripcslashes($PayPing_data));
                $new_payment = !empty($PayPing_data) && $PayPing_data != '' ? true : false;
				
                if( isset($_GET['fault']) ){

                    $this->payment_status = 'failed';

                    if ( function_exists('wpuf_get_option') )
                        $message = wpautop(wptexturize(wpuf_get_option('PayPing_failed', 'wpuf_payment')));

                    $transaction_id = !empty($_GET['transaction_id']) ? $_GET['transaction_id'] : '';
                    $message = str_replace(array('{transaction_id}', '{fault}'), array($transaction_id, $this->WPUF_PayPing_Fault( $_GET['fault']) ), $message);
                    $this->payment_message = $message;

                    add_filter('the_content', array($this, 'WPUF_PayPing_Content_After_Return_Sht'));
                    return false;

                }elseif( !$new_payment ){
                    $this->payment_status = 'failed';
                    $this->payment_message = __('وضعیت پرداخت قبلا مشخص شده است .', 'wpuf');
                    add_filter('the_content', array($this, 'WPUF_PayPing_Content_After_Return_Sht'));
                    return false;
                }

                //price
                $price = !empty($payment_data->price) ? $payment_data->price : $this->POST('price');
                $price = !empty($price) ? intval($price) : 0;

                $currency = !empty($payment_data->currency) ? $payment_data->currency : ( function_exists('wpuf_get_option') ? wpuf_get_option('currency', 'wpuf_payment') : $this->POST('currency') );
                $currency = !empty($currency) ? $currency : '';

                $coupon_id = !empty($payment_data->coupon_id) ? $payment_data->coupon_id : $this->POST('coupon_id');
                $coupon_id = !empty($coupon_id) ? $coupon_id : false;

                $form_id = !empty($payment_data->form_id) ? $payment_data->form_id : $this->POST('form_id');
                $form_id = !empty($form_id) ? $form_id : false;

                $_post_id = !empty($payment_data->_post_id) ? $payment_data->_post_id : $this->POST('_post_id');
                $_post_id = !empty($_post_id) ? $_post_id : false;


                $user_id = !empty($payment_data->user_id) ? (int)$payment_data->user_id : '';
                $user_id = !empty($user_id) ? $user_id : (int)$this->POST('user_id');
                if ( empty($user_id) || !$user_id )
                    $user_id = 0;

                global $current_user;
                if ( !$user_id && is_object($current_user) && !empty($current_user->ID) && $current_user->ID != 0 ) {
                    $user_id = $current_user->ID;
                }

                $user_name = __('مهمان', 'wpuf');
                if ( $user_id && $user_data = get_userdata($user_id) ) {
                    $user_name = $user_data->user_login;
                }

                $user_email = !empty($payment_data->user_email) ? $payment_data->user_email : $user_email;
                $user_email = !empty($user_email) ? $user_email : $this->POST('user_email');

                $first_name = !empty($payment_data->first_name) ? $payment_data->first_name : '';
                $first_name = !empty($first_name) ? $first_name : $this->POST('first_name');

                $last_name = !empty($payment_data->last_name) ? $payment_data->last_name : '';
                $last_name = !empty($last_name) ? $last_name : $this->POST('last_name');

                $full_user = $first_name . ' ' . $last_name;
                $full_user = strlen($full_user) > 5 ? $full_user : $user_name;

                //item
                $item_number = !empty($payment_data->item_number) ? $payment_data->item_number : $this->POST('item_number');

                $pay_type = !empty($payment_data->type) ? $payment_data->type : $this->POST('type');

                if ( !empty($pay_type) ) {

                    switch ( $pay_type ) {
                        case 'post':
                            $post_id = $item_number;
                            $pack_id = 0;
                            break;
                        case 'pack':
                            $post_id = 0;
                            $pack_id = $item_number;
                            break;
                    }
                } else {
                    $post_id = 0;
                    $pack_id = 0;
                }


                $has_gateway = ( !empty($_GET['pay']) && $_GET['pay'] == 'no' ) ? 'no' : 'yes';

                if( $has_gateway == 'yes' ){

                    if ( function_exists('wpuf_get_option') ) {
                        $redirect_page_id = wpuf_get_option('payment_page', 'wpuf_payment');
                        $subscription_page_id = wpuf_get_option('subscription_page', 'wpuf_payment');
                    }
                    if ( $redirect_page_id ) {
                        $Failed_url = add_query_arg('pay_method', $query, get_permalink($redirect_page_id));
                    } else {
                        $Failed_url = add_query_arg('pay_method', $query, get_permalink($subscription_page_id));
                    }

                    //start of PayPing
                    $Amount = intval($price);
                    if ( strtolower($currency) != 'irr' )
                        $Amount = $Amount ;
                    //Start of PayPing
                    $TokenCode = wpuf_get_option('TokenCode', 'wpuf_payment');
					$data = array('refId' => $_POST['refid'], 'amount' => $Amount);
					$args = array(
						'body' => json_encode($data),
						'timeout' => '45',
						'redirection' => '5',
						'httpsversion' => '1.0',
						'blocking' => true,
						'headers' => array(
						'Authorization' => 'Bearer ' . $TokenCode,
						'Content-Type'  => 'application/json',
						'Accept' => 'application/json'
						),
					 'cookies' => array()
					);
                    $response = wp_remote_post( 'https://api.payping.ir/v2/pay/verify', $args );
					
					$XPP_ID = $response["headers"]["x-paypingrequest-id"];
                    if( is_wp_error($response) ){
                        $Status = 'failed';
				        $Fault = $response->get_error_message();
						$Message = 'خطا در ارتباط به پی‌پینگ : شرح خطا '.$response->get_error_message();
					}else{
						$code = wp_remote_retrieve_response_code( $response );
						if( $code === 200 ){
							if( isset($_POST["refid"]) and $_POST["refid"] != ''){
								$Status = 'completed';
								$transaction_id = $_POST["refid"];
								$fault = 0;
							}else{
                                $Status = 'failed';
								$Transaction_ID = $_POST['refid'];
								$Message = 'متافسانه سامانه قادر به دریافت کد پیگیری نمی باشد! نتیجه درخواست : ' .wp_remote_retrieve_body( $response ).'<br /> شماره خطا: '.$XPP_ID;
								$Fault = $code;
							}
						}else{
                            $Status = 'failed';
				            $Transaction_ID = $_POST['refid'];
							$Message = wp_remote_retrieve_body( $response ).'<br /> شماره خطا: '.$XPP_ID;
                            $Fault = $code;
						}
					}
                    //End of BankPayPing
                    $status = $Status;
                    //
                }else{
                    $Status = 'completed';
                    $transaction_id = rand(10000, 99999);
                    $fault = 0;
                }

                $data = array(
                    'user_id' => $user_id,
                    'profile_id' => $user_id,
                    'status' => $status,
                    'cost' => $price,
                    'post_id' => $post_id,
                    'pack_id' => $pack_id,
                    'payer_first_name' => !empty($first_name) ? $first_name : $full_user,
                    'payer_last_name' => !empty($last_name) ? $last_name : '',
                    'payer_email' => $user_email,
                    'payment_type' => ( function_exists('wpuf_get_option') ? ( wpuf_get_option('PayPing_name', 'wpuf_payment') ? wpuf_get_option('PayPing_name', 'wpuf_payment') : __('پی‌پینگ', 'wpuf') ) : '' ) . ( !empty($fault) && $fault ? sprintf(__(' - خطا : %s', 'wpuf'), $this->WPUF_PayPing_Fault($fault)) : '' ),
                    'payer_address' => null,
                    'transaction_id' => $transaction_id,
                    'created' => current_time('mysql'),
                );


                do_action('WPUF_Return_from_Gateway_' . $status, $data, $transaction_id);

                //WP_User_Frontend::log( $transaction_id, $status );
                $message = '';
                $this->payment_status = $status;

                if ( $status == 'completed' ) {

                    $this->insert_payment($data, $item_number, false);

                    do_action('wpuf_payment_received', $data, false);

                    if ( $coupon_id ) {
                        $pre_usage = get_post_meta($coupon_id, '_coupon_used', true);
                        $new_use = $pre_usage + 1;
                        update_post_meta($coupon_id, '_coupon_used', $new_use);
                    }

                    delete_user_meta($user_id, '_wpuf_user_active');
                    delete_user_meta($user_id, '_wpuf_activation_key');

                    do_action('WPUF_PayPing_Return_from_Gateway_Success', $data, $transaction_id);
                    if ( function_exists('wpuf_get_option') ) {
                        $message = wpautop(wptexturize(wpuf_get_option('PayPing_success', 'wpuf_payment')));
                    }
                    $message = str_replace('{transaction_id}', $transaction_id, $message);

                    if ( $form_id && $pay_type == 'post' ) {

                        $form_settings = wpuf_get_form_settings($form_id);
                        $redirect_to = isset($form_settings['redirect_to']) ? $form_settings['redirect_to'] : 'post';

                        if ( $redirect_to == 'page' ) {
                            $redirect_to = isset($form_settings['page_id']) ? get_permalink($form_settings['page_id']) : false;
                        } elseif ( $redirect_to == 'url' ) {
                            $redirect_to = isset($form_settings['url']) ? $form_settings['url'] : false;
                        } elseif ( $redirect_to == 'same' ) {
                            $redirect_to = false;

                            if ( !empty($form_settings['message']) ) {
                                $message .= $form_settings['message'];
                            }


                        } else {
                            $redirect_to = $_post_id ? get_permalink($_post_id) : false;
                        }

                        if ( $redirect_to != false && $redirect_to !== false ) {
                            wp_redirect($redirect_to);
                            exit();
                        }
                    }

                    $this->payment_message = $message;
                } else {
                    if ( function_exists('wpuf_get_option') && wpuf_get_option('PayPing_transaction', 'wpuf_payment') == 'on' ) {
                        $this->insert_payment($data, $item_number, false);
                    }
                    do_action('WPUF_PayPing_Return_from_Gateway_Failed', $data, $transaction_id, $fault);
                    wp_redirect(add_query_arg(array('fault' => $fault, 'transaction_id' => $transaction_id), $Failed_url));
                    exit();
                }
                add_filter('the_content', array($this, 'WPUF_PayPing_Content_After_Return_Sht'));
            }
        }

        public function insert_payment( $data, $transaction_id = 0, $recurring = false )
        {
            global $wpdb;

            $sql = "SELECT transaction_id, status
					FROM " . $wpdb->prefix . "wpuf_transaction
					WHERE transaction_id = '" . esc_sql($transaction_id) . "' LIMIT 1";
            $result = $wpdb->get_row($sql);
            if ( $recurring != false ) {
                $profile_id = $data['profile_id'];
            }
            if ( isset($data['profile_id']) || empty($data['profile_id']) ) {
                unset($data['profile_id']);
            }

            if ( !empty($result->transaction_id) && ( empty($result->status) || $result->status == __('pending', 'wpuf') ) ) {
                $wpdb->update($wpdb->prefix . 'wpuf_transaction', $data, array('transaction_id' => $transaction_id));
            } else {
                $wpdb->insert($wpdb->prefix . 'wpuf_transaction', $data);
            }
            if ( isset($profile_id) ) {
                $data['profile_id'] = $profile_id;
            }
        }

        //---wpuf.ir---
        public function WPUF_PayPing_Content_After_Return_Sht( $content )
        {
            return $this->payment_status == 'completed' ? ( $content . $this->payment_message ) : $this->payment_message;
        }

        //---wpuf.ir---
        public function POST( $name )
        {
            if ( isset($_POST[$name]) )
                return stripslashes_deep($_POST[$name]);
            return '';
        }

        //---wpuf.ir---
        public function WPUF_PayPing_Text_Sht( $translated )
        {

            if ( is_admin() && get_post_type() == 'wpuf_subscription' ) {
                $translated = str_replace(
                    array('فعال کرن پرداخت دوره ای'),
                    array('فعال کرن پرداخت دوره ای (مخصوص پی پال)'),
                    $translated
                );
            }

            return $translated;
        }

        //---wpuf.ir---
        public function WPUF_PayPing_Data_Sht( $action, $payment_data = '' )
        {

            if ( !class_exists('Sht_Store') )
                require_once( 'data.php' );
            $data = Sht_Store::get_instance();

            @session_start();
            if ( $action == 'set' ) {
                unset($data['wpuf_PayPing']);
                unset($_SESSION['wpuf_PayPing']);
                $data['wpuf_PayPing'] = $_SESSION["wpuf_PayPing"] = $payment_data;
            }

            if ( $action == 'call' ) {
                $payment_data = '';
                if ( !empty($data['wpuf_PayPing']) ) {
                    $payment_data = $data['wpuf_PayPing'];
                } else if ( !empty($_SESSION["wpuf_PayPing"]) ) {
                    $payment_data = $_SESSION["wpuf_PayPing"];
                }
                unset($data['wpuf_PayPing']);
                unset($_SESSION['wpuf_PayPing']);
                return $payment_data;
            }
        }


        public function json_encode( $json )
        {

            if ( defined('JSON_UNESCAPED_UNICODE') )
                return json_encode($json, JSON_UNESCAPED_UNICODE);

            $encoded = json_encode($json);
            $unescaped = preg_replace_callback('/\\\\u(\w{4})/', function ( $matches ) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
            }, $encoded);
            return $unescaped;
        }

        //---wpuf.ir---
        private function WPUF_PayPing_Fault( $Result )
        {
            $message = ' ';
            switch ( $Result ) {

                case '-1':
                case -1:
                    $message = __('اطلاعات ارسال شده ناقص است. .', 'wpuf');
                    break;

                case '-2':
                case -2:
                    $message = __('و يا مرچنت كد پذيرنده صحيح نيست. IP .', 'wpuf');
                    break;

                //case '0':
                //$message =  __('تراکنش با موفقیت انجام شد .', 'wpuf' );
                //break;
                case '-3':
                case -3:
                    $message = __(' با توجه به محدوديت هاي شاپرك امكان پرداخت با رقم درخواست شده ميسر نمي باشد. .', 'wpuf');
                    break;

                case '-4':
                case -4:
                    $message = __('سطح تاييد پذيرنده پايين تر از سطح نقره اي است.', 'wpuf');
                    break;

                case '-11':
                case -11:
                    $message = __('درخواست مورد نظر يافت نشد..', 'wpuf');
                    break;

                case '-12':
                case -12:
                    $message = __('امكان ويرايش درخواست ميسر نمي باشد.', 'wpuf');
                    break;

                case '-21':
                case -21:
                    $message = __('هيچ نوع عمليات مالي براي اين تراكن يافت نشد. .', 'wpuf');
                    break;

                case '-22':
                case -22:
                    $message = __('تراكنش نا موفق ميباشد.ز است .', 'wpuf');
                    break;

                case '-33':
                case -33:
                    $message = __('رقم تراكنش با رقم پرداخت شده مطابقت ندارد.ده اید .', 'wpuf');
                    break;

                case '-34':
                case -34:
                    $message = __('سقف تقسيم تراكنش از لحاظ تعداد يا رقم عبور نموده است است .', 'wpuf');
                    break;

                case '-40':
                case -40:
                    $message = __('اجازه دسترسي به متد مربوطه وجود ندارد..', 'wpuf');
                    break;

                case '-41':
                case -41:
                    $message = __('غيرمعتبر ميباشد. AdditionalData اطلاعات ارسال شده مربوط به', 'wpuf');
                    break;

                case '-42':
                case -42:
                    $message = __('مدت زمان معتبر طول عمر شناسه پرداخت بايد بين 30 دقيه تا 45 روز مي باشد.ده است .', 'wpuf');
                    break;

                case '-54':
                case -54:
                    $message = __('درخواست مورد نظر آرشيو شده است..', 'wpuf');
                    break;

                case '100':
                case 100:
                    $message = __('عمليات با موفقيت انجام گرديده است.نش نمی باشد .', 'wpuf');
                    break;

                case '101':
                case 101:
                    $message = __('تراكنش انجام شده است. PaymentVerification عمليات پرداخت موفق بوده و قبلانیست .', 'wpuf');
                    break;

                case '23':
                case 23:
                    $message = __('خطای امنیتی رخ داده است .', 'wpuf');
                    break;

                case '24':
                case 24:
                    $message = __('اطلاعات کاربری پذیرنده معتبر نیست .', 'wpuf');
                    break;

                case '25':
                case 25:
                    $message = __('مبلغ نامعتبر است .', 'wpuf');
                    break;

                case '31':
                case 31:
                    $message = __('پاسخ نامعتبر است .', 'wpuf');
                    break;

                case '32':
                case 32:
                    $message = __('فرمت اطلاعات وارد شده صحیح نیست .', 'wpuf');
                    break;

                case '33':
                case 33:
                    $message = __('حساب نامعتبر است .', 'wpuf');
                    break;

                case '34':
                case 34:
                    $message = __('خطای سیستمی رخ داده است .', 'wpuf');
                    break;

                case '35':
                case 35:
                    $message = __('تاریخ نامعتبر است .', 'wpuf');
                    break;

                case '41':
                case 41:
                    $message = __('شماره درخواست تکراری است .', 'wpuf');
                    break;

                case '42':
                case 42:
                    $message = __('همچین تراکنشی وجود ندارد .', 'wpuf');
                    break;

                case '43':
                case 43:
                    $message = __('قبلا درخواست Verify داده شده است', 'wpuf');
                    break;

                case '44':
                case 44:
                    $message = __('درخواست Verify یافت نشد .', 'wpuf');
                    break;

                case '45':
                case 45:
                    $message = __('تراکنش قبلا Settle شده است .', 'wpuf');
                    break;

                case '46':
                case 46:
                    $message = __('تراکنش Settle نشده است .', 'wpuf');
                    break;

                case '47':
                case 47:
                    $message = __('تراکنش Settle یافت نشد .', 'wpuf');
                    break;

                case '48':
                case 48:
                    $message = __('تراکنش قبلا Reverse شده است .', 'wpuf');
                    break;

                case '49':
                case 49:
                    $message = __('تراکنش Refund یافت نشد .', 'wpuf');
                    break;

                case '412':
                case 412:
                    $message = __('شناسه قبض نادرست است .', 'wpuf');
                    break;

                case '413':
                case 413:
                    $message = __('شناسه پرداخت نادرست است .', 'wpuf');
                    break;

                case '414':
                case 414:
                    $message = __('سازمان صادر کننده قبض معتبر نیست .', 'wpuf');
                    break;

                case '415':
                case 415:
                    $message = __('زمان جلسه کاری به پایان رسیده است .', 'wpuf');
                    break;

                case '416':
                case 416:
                    $message = __('خطا در ثبت اطلاعات رخ داده است .', 'wpuf');
                    break;

                case '417':
                case 417:
                    $message = __('شناسه پرداخت کننده نامعتبر است .', 'wpuf');
                    break;

                case '418':
                case 418:
                    $message = __('اشکال در تعریف اطلاعات مشتری رخ داده است .', 'wpuf');
                    break;

                case '419':
                case 419:
                    $message = __('تعداد دفعات ورود اطلاعات بیش از حد مجاز است .', 'wpuf');
                    break;

                case '421':
                case 421:
                    $message = __('IP معتبر نیست .', 'wpuf');
                    break;

                case '51':
                case 51:
                    $message = __('تراکنش تکراری است .', 'wpuf');
                    break;

                case '54':
                case 54:
                    $message = __('تراکنش مرجع موجود نیست .', 'wpuf');
                    break;

                case '55':
                case 55:
                    $message = __('تراکنش نامعتبر است .', 'wpuf');
                    break;

                case '61':
                case 61:
                    $message = __('خطا در واریز رخ داده است .', 'wpuf');
                    break;

                default:
                    $message = __('خطای تعریف نشده رخ داده است .', 'wpuf');
            }

            return $message;
        }
    }
}


add_action('plugins_loaded', 'wpuf_PayPing_plugin');
function wpuf_PayPing_plugin()
{
    global $wpuf_PayPing;
    $wpuf_PayPing = new WPUF_PayPing();
}
