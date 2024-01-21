<?php
/*
Plugin Name: Blockonomics and PMPro Integration
Description: Integrates Blockonomics payment with PMPro and adds users as members.
*/

add_action('rest_api_init', function () {
    register_rest_route('blockonomics', '/webhook/', array(
        'methods' => 'GET',
        'callback' => 'blockonomics_webhook_handler',
    ));
});

function blockonomics_webhook_handler(WP_REST_Request $request) {
    try {
        
        $addr = $request['addr'];
        $status = $request['status'];
        $uuid = $request['uuid'];
        $headers = array(
            'Authorization' => 'Bearer GfICmDTEuNjg9qDetQzp8XqSHGRhSRZOyKPDv2pxam8',
        );

        $response = wp_remote_get('https://www.blockonomics.co/api/merchant_order/09bfe5cd11c248cda211', array(
           'headers' => $headers,    
        ));

        if (is_wp_error($response)) {
            // Handle error
            echo 'Error: ' . $response->get_error_message();
        } else {
            // Process the response
            $body = wp_remote_retrieve_body($response);
            $decoded_response = json_decode($body, true);
            $user_email =  $decoded_response['data']['emailid'];
            $membership_level_name = $decoded_response['name'];
            add_user_to_membership_level($user_email, $membership_level_name);
        }
        
       
    } catch (Exception $e) {
        // Send an error response
        wp_send_json_error(array('status' => 'error', 'message' => $e->getMessage()));
    }
}

function add_user_to_membership_level($user_email, $membership_level_name) {
    // Load PMPro
    require_once(plugin_dir_path(__FILE__) . 'paid-memberships-pro/paid-memberships-pro.php');

    // Get user ID by email
    $user = get_user_by('email', $user_email);
    $user_id = $user->ID;

    // Set the membership level ID you want to assign to the user
    $startdate = date('Y-m-d');
    if($membership_level_name == "Diamond Plan"){
        $membership_level_id = 2;
        $initial_payment = 39;
        $enddate = date('Y-m-d', strtotime('+6 months'));
    } else {
        $membership_level_id = 3;
        $initial_payment = 69;
        $enddate = date('Y-m-d', strtotime('+12 months'));
    }
	$custom_level = array(
		'user_id'         => $user_id,
		'membership_id'   => $membership_level_id,
		'code_id'         => 0,
		'initial_payment' => $initial_payment,
		'billing_amount'  => 0,
		'cycle_number'    => 0,
		'cycle_period'    => 0,
		'billing_limit'   => 0,
		'trial_amount'    => 0,
		'trial_limit'     => 0,
		'startdate'       => $startdate,
		'enddate'         => $enddate
	);
     
    // Add the user to the membership level
    if(pmpro_changeMembershipLevel($custom_level, $user_id)!== false) {
        wp_send_json_success(array('status' => 'success'));
    }
}
?>