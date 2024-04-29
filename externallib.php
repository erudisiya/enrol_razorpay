<?php
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/enrollib.php");
require_once("$CFG->dirroot/enrol/razorpaypayment/razorpay-php/Razorpay.php");
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
class moodle_enrol_razorpaypayment_external extends external_api {
	public static function razorpaypayment_free_enrolsettings_parameters() {
        return new external_function_parameters(
            array(
                'user_id' => new external_value(PARAM_RAW, 'Update data user id'),
                'instance_id' => new external_value(PARAM_RAW, 'Update data instance id')
            )
        );
    }
    public static function razorpaypayment_free_enrolsettings_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_RAW, 'status: true if success')
            )
        );
    }
    public static function razorpaypayment_free_enrolsettings($user_id, $instance_id ) {
        global $DB, $CFG, $PAGE;
        $data = new stdClass();
        $data->userid           = (int)$user_id;
        $data->instanceid       = (int)$instance_id;
        $data->timeupdated      = time();
        if (! $user = $DB->get_record("user", array("id" => $data->userid))) {
            self::message_razorpaypayment_error_to_admin(get_string('invaliduserid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        //$data->receiver_email = $user->email;
        if (! $plugininstance = $DB->get_record("enrol", array("id" => $data->instanceid, "status" => 0))) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidinstance', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        $data->courseid         = (int)$plugininstance->courseid;
        if (! $course = $DB->get_record("course", array("id" => $data->courseid))) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidcourseid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        if (! $context = context_course::instance($course->id, IGNORE_MISSING)) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidcontextid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        $data->item_name = format_string($course->fullname, true, array('context' => $context));
        $PAGE->set_context($context);
        // If currency is incorrectly set then someone maybe trying to cheat the system.
        if ($data->courseid != $plugininstance->courseid) {
            self::message_razorpaypayment_error_to_admin(get_string('unmatchedcourse', 'enrol_razorpaypayment').$data->courseid, $data);
            redirect($CFG->wwwroot);
        }
        $plugin = enrol_get_plugin('razorpaypayment');
        $checkcustomer = $DB->get_records('enrol_razorpaypayment',
        array('receiver_email' => $data->razorpayEmail));
        foreach ($checkcustomer as $keydata => $valuedata) {
            $checkcustomer = $valuedata;
        }
        $data->receiver_email = $user->email;
        $data->payment_status = 'succeeded';
        // print_r($data);die;
        $DB->insert_record("enrol_razorpaypayment", $data);
        if ($plugininstance->enrolperiod) {
            $timestart = time();
            $timeend   = $timestart + $plugininstance->enrolperiod;
        } else {
            $timestart = 0;
            $timeend   = 0;
        }
        // Enrol user.
        $plugin->enrol_user($plugininstance, $user->id, $plugininstance->roleid, $timestart, $timeend);
        // Pass $view=true to filter hidden caps if the user cannot see them.
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
            '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
        $teacher = array_shift($users);
        } else {
            $teacher = false;
        }
        $mailstudents = $plugin->get_config('mailstudents');
        $mailteachers = $plugin->get_config('mailteachers');
        $mailadmins   = $plugin->get_config('mailadmins');
        $shortname = format_string($course->shortname, true, array('context' => $context));
        $coursecontext = context_course::instance($course->id);
        $orderdetails = new stdClass();
        if (!empty($mailstudents)) {
            $orderdetails->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
            $orderdetails->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";
            $userfrom = empty($teacher) ? core_user::get_support_user() : $teacher;
            $subject = get_string("enrolmentnew", 'enrol', $shortname);
            $fullmessage = get_string('welcometocoursetext', '', $orderdetails);
            $fullmessagehtml = html_to_text('<p>'.get_string('welcometocoursetext', '', $orderdetails).'</p>');
            // Send test email.
            ob_start();
            $success = email_to_user($user, $userfrom, $subject, $fullmessage, $fullmessagehtml);
            $smtplog = ob_get_contents();
            ob_end_clean();
        }
        if (!empty($mailteachers) && !empty($teacher)) {
            $orderdetails->course = format_string($course->fullname, true, array('context' => $coursecontext));
            $orderdetails->user = fullname($user);
            $subject = get_string("enrolmentnew", 'enrol', $shortname);
            $fullmessage = get_string('enrolmentnewuser', 'enrol', $orderdetails);
            $fullmessagehtml = html_to_text('<p>'.get_string('enrolmentnewuser', 'enrol', $orderdetails).'</p>');
            // Send test email.
            ob_start();
            $success = email_to_user($teacher, $user, $subject, $fullmessage, $fullmessagehtml);
            $smtplog = ob_get_contents();
            ob_end_clean();
        }
        if (!empty($mailadmins)) {
            $orderdetails->course = format_string($course->fullname, true, array('context' => $coursecontext));
            $orderdetails->user = fullname($user);
            $admins = get_admins();
            foreach ($admins as $admin) {
                $subject = get_string("enrolmentnew", 'enrol', $shortname);
                $fullmessage = get_string('enrolmentnewuser', 'enrol', $orderdetails);
                $fullmessagehtml = html_to_text('<p>'.get_string('enrolmentnewuser', 'enrol', $orderdetails).'</p>');
                // Send test email.
                ob_start();
                $success = email_to_user($admin, $user, $subject, $fullmessage, $fullmessagehtml);
                $smtplog = ob_get_contents();
                ob_end_clean();
            }
        }
        $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
        $fullname = format_string($course->fullname, true, array('context' => $context));
        if (is_enrolled($context, null, '', true)) { // TODO: use real razorpay check.
            redirect($destination, get_string('enrollsuccess', 'enrol_razorpaypayment') .$fullname);
        } else {   
            // Somehow they aren't enrolled yet!
            $PAGE->set_url($destination);
            echo $OUTPUT->header();
            $orderdetails = new stdClass();
            $orderdetails->teacher = get_string('defaultcourseteacher');
            $orderdetails->fullname = $fullname;
            notice(get_string('paymentsorry', '', $orderdetails), $destination);
        }
        $result = array();
        $result['status'] = 'working';
        return $result;
        die;
    }
    /**
     * Send payment error message to the admin.
     *
     * @param string $subject
     * @param stdClass $data
     */
    public static function message_razorpaypayment_error_to_admin($subject, $data) {
        $admin = get_admin();
        $site = get_site();
        $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";
        foreach ($data as $key => $value) {
            $message .= s($key) ." => ". s($value)."\n";
        }
        $subject = "RAZORPAY PAYMENT ERROR: ".$subject;
        $fullmessage = $message;
        $fullmessagehtml = html_to_text('<p>'.$message.'</p>');
        // Send test email.
        ob_start();
        $success = email_to_user($admin, $admin, $subject, $fullmessage, $fullmessagehtml);
        $smtplog = ob_get_contents();
        ob_end_clean();
    }
    public static function razorpay_js_method_parameters() {
        return new external_function_parameters(
            array(
                'user_id' => new external_value(PARAM_RAW, 'Update data user id'),
                'instance_id' => new external_value(PARAM_RAW, 'Update instance id')
            )    
        );
    }
    public static function razorpay_js_method_returns() {
        $data = new external_multiple_structure(
            new external_single_structure(
                array(
                    'key' => new external_value(PARAM_RAW, 'status: true if success'),
                    'amount' => new external_value(PARAM_RAW, 'status: true if success'),
                    'name' => new external_value(PARAM_RAW, 'status: true if success'),
                    'description' => new external_value(PARAM_RAW, 'status: true if success'),
                    'order_id' => new external_value(PARAM_RAW, 'status: true if success'),
                    'currency' => new external_value(PARAM_RAW, 'status: true if success'),
                    'callback_url' => new external_value(PARAM_RAW, 'status: true if success'),
                    'prefill' => new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'status: true if success'),
                            'email' => new external_value(PARAM_RAW, 'status: true if success'),
                            'contact' => new external_value(PARAM_RAW, 'status: true if success')
                        )
                    ),
                    'notes' => new external_single_structure(
                        array(
                            'customerid' => new external_value(PARAM_RAW, 'status: true if success')
                        )
                    ),
                    'theme' => new external_single_structure(
                        array(
                            'color' => new external_value(PARAM_RAW, 'status: true if success')
                        )
                    ),
                    'modal' => new external_single_structure(
                        array(
                            'confirm_close' => new external_value(PARAM_RAW, 'status: true if success'),
                            'escape' => new external_value(PARAM_RAW, 'status: true if success')
                        )
                    )
                )
            )
        );
        //print_r($data->content);die;
        return $data->content;
    }
    public static function razorpay_js_method($user_id, $instance_id) {
        global $CFG, $DB;
        $plugin = enrol_get_plugin('razorpaypayment');
        $keyId = $plugin->get_config('razorpaykeyid');
        $keySecret = $plugin->get_config('razorpaykeysecret');
        $user_token = $plugin->get_config('webservice_token');
        if (! $plugininstance = $DB->get_record("enrol", array("id" => $instance_id, "status" => 0))) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidinstance', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        $amount = $plugin->get_razorpay_amount($plugininstance->cost, $plugininstance->currency, false);
        //print_r($amount);die;
        $courseid = $plugininstance->courseid;
        $currency = $plugininstance->currency;
        if (! $course = $DB->get_record("course", array("id" => $courseid))) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidcourseid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        if (! $context = context_course::instance($course->id, IGNORE_MISSING)) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidcontextid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        //print_r($courseid);die;
        $description  = format_string($course->fullname, true, array('context' => $context));
        //print_r($description);die;
        $shortname = format_string($course->shortname, true, array('context' => $context));
        if (! $user = $DB->get_record("user", array("id" => $user_id))) {
            self::message_razorpaypayment_error_to_admin("Not orderdetails valid user id", $data);
            redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
        }
        if (empty($keyId) || empty($keySecret) || empty($courseid) || empty($amount) || empty($currency) || empty($description)) {
            redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
        } else {
            // Set API key 
            $api = new Api($keyId, $keySecret);
            $response = array( 
                'status' => 0, 
                'error' => array( 
                    'message' => get_string('invalidrequest', 'enrol_razorpaypayment')  
                ) 
            );
            // retrieve Razorpay customer_id if previously set
            $checkcustomer = $DB->get_records('enrol_razorpaypayment',
            array('userid' => $user->id));
            if($user->phone1){
                $contactnumber = $user->phone1;
            } elseif($user->phone2){
                $contactnumber = $user->phone2;
            } else {
                $contactnumber = '';
            }
            foreach ($checkcustomer as $keydata => $valuedata) {
                $checkcustomer = $valuedata;
            }
            if($checkcustomer){
                // echo 'bbbb';
                $customerid = $checkcustomer->receiver_id;
                //die;
            } else {
                // echo 'aaaa';
                $customerarray = array("name" => fullname($user),"email" => $user->email, "contact" => $contactnumber);
                $customer = $api->customer->create($customerarray);
                $customerid = $customer->id;
            }
            
            // Create new Checkout Session for the order 
            $orderData = [
                'receipt'         => 'purcahse course : '.$description,
                'amount'          => $amount, 
                'currency'        => $currency,
                'payment_capture' => 1 // auto capture
            ];
            //$razorpayOrder = $api->order->create($orderData);
            //$data->orderid    =  $razorpayOrder->id;
            //$DB->insert_record("enrol_razorpaypayment", $data);
            //print_r($razorpayOrder);die;
            $data = array();
            $data = [
                "key"               => $keyId,
                "amount"            => $amount,
                "currency"          => $currency,
                "name"              => $description,
                "description"       => 'buy course : '.$description,
                "prefill"           => [
                "name"              => fullname($user),
                "email"             => $user->email,
                "contact"           => $contactnumber,
                ],
                "notes"             => [
                "customerid"           => $customerid,
                ],
                "theme"             => [
                "color"             => "#0DBD9D"
                ],
                "order_id"          => "order_O2mgMvXY1Q94uO"/*$razorpayOrder->id*/,
                "callback_url"      => $CFG->wwwroot.'/webservice/rest/server.php?wstoken=' .$user_token. '&wsfunction=moodle_razorpaypayment_success_razorpay_url&moodlewsrestformat=json&user_id=' .$user_id. '&instance_id=' .$instance_id. '',
                "modal"             => [
                "confirm_close"     => "true",
                "escape"            => "true"
                ]
            ];
            return $data;
            die;
        }
    }
    public static function success_razorpay_url_parameters() {
        return new external_function_parameters(
            array(
                'razorpay_payment_id' => new external_value(PARAM_RAW, 'Update data payment id'),
                'razorpay_order_id' => new external_value(PARAM_RAW, 'Update data order id'),
                'razorpay_signature' => new external_value(PARAM_RAW, 'Update data signature id'),
                'user_id' => new external_value(PARAM_RAW, 'Update data user id'),
                'instance_id'  => new external_value(PARAM_RAW, 'The item id to operate instance id')
            )    
        );
    }
    public static function success_razorpay_url_returns() {
        /*return*/ $data = new external_single_structure(
            array(
                'status' => new external_value(PARAM_RAW, 'status: true if success')
            )
        );
        //print_r($data);die;
        return $data;
    }
    public static function success_razorpay_url($razorpay_payment_id,$razorpay_order_id,$razorpay_signature,$user_id, $instance_id) {//echo '<pre>';
        global $DB, $CFG, $PAGE, $OUTPUT;
        $data = new stdClass();
        $plugin = enrol_get_plugin('razorpaypayment');
        $keyId = $plugin->get_config('razorpaykeyid');
        $keySecret = $plugin->get_config('razorpaykeysecret');
        $api = new Api($keyId, $keySecret);
        $paymentdetail = $api->payment->fetch($razorpay_payment_id);
        // print_r($user_id);die;
        $data->receiver_id = $paymentdetail['notes']['customerid'];
        if (! $plugininstance = $DB->get_record("enrol", array("id" => $instance_id, "status" => 0))) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidinstance', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        $courseid = $plugininstance->courseid;
        $data->courseid = $courseid;
        $data->instanceid = $instance_id;
        $data->userid = (int)$user_id;
        $data->timeupdated = time();
        if (! $user = $DB->get_record("user", array("id" => $data->userid))) {
            self::message_razorpaypayment_error_to_admin(get_string('invaliduserid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        if (! $course = $DB->get_record("course", array("id" => $data->courseid))) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidcourseid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        if (! $context = context_course::instance($course->id, IGNORE_MISSING)) {
            self::message_razorpaypayment_error_to_admin(get_string('invalidcontextid', 'enrol_razorpaypayment'), $data);
            redirect($CFG->wwwroot);
        }
        $PAGE->set_context($context);
        // Check that amount paid is the correct amount.
        if ( (float) $plugininstance->cost <= 0 ) {
            $cost = (float) $plugin->get_config('cost');
        } else {
            $cost = (float) $plugininstance->cost;
        }
        // Use the same rounding of floats as on the enrol form.
        $cost = format_float($cost, 2, false);
        $data->receiver_email = $paymentdetail->email;
        $data->txn_id = $paymentdetail->id;
        $data->tax = $paymentdetail->amount / 100;
        $data->memo = $paymentdetail->method;
        $data->payment_status = $paymentdetail->status;
        $data->pending_reason = $paymentdetail->error_reason;
        $data->reason_code = $paymentdetail->error_code;
        $data->item_name = $course->fullname;
        $success = true;
        $error = "Payment Failed";
        if (empty($razorpay_payment_id) === false) {
            
            try {
                // Please note that the razorpay order ID must
                // come from a trusted source (session here, but
                // could be database or something else)
                $attributes = array(
                    'razorpay_order_id' => $razorpay_order_id,
                    'razorpay_payment_id' => $razorpay_payment_id,
                    'razorpay_signature' => $razorpay_signature
                );
                $api->utility->verifyPaymentSignature($attributes);
            }
            catch(SignatureVerificationError $e) {
                $success = false;
                $error = 'Razorpay Error : ' . $e->getMessage();
            }
        }
        if ($success === true) {
            $html = "<p>Your payment was successful</p>
             <p>Payment ID: {$_POST['razorpay_payment_id']}</p>";
             //print_r($data);die;
            // ALL CLEAR !
            $DB->insert_record("enrol_razorpaypayment", $data);
            if ($plugininstance->enrolperiod) {
                $timestart = time();
                $timeend   = $timestart + $plugininstance->enrolperiod;
            } else {
                $timestart = 0;
                $timeend   = 0;
            }
            // Enrol user.
            $plugin->enrol_user($plugininstance, $user->id, $plugininstance->roleid, $timestart, $timeend);
            // Pass $view=true to filter hidden caps if the user cannot see them.
            if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                                     '', '', '', '', false, true)) {
                $users = sort_by_roleassignment_authority($users, $context);
                $teacher = array_shift($users);
            } else {
                $teacher = false;
            }
            $mailstudents = $plugin->get_config('mailstudents');
            $mailteachers = $plugin->get_config('mailteachers');
            $mailadmins   = $plugin->get_config('mailadmins');
            $shortname = format_string($course->shortname, true, array('context' => $context));
            $coursecontext = context_course::instance($course->id);
            $orderdetails = new stdClass();
            if (!empty($mailstudents)) {
                $orderdetails->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
                $orderdetails->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";
                $userfrom = empty($teacher) ? core_user::get_support_user() : $teacher;
                $subject = get_string("enrolmentnew", 'enrol', $shortname);
                $fullmessage = get_string('welcometocoursetext', '', $orderdetails);
                $fullmessagehtml = html_to_text('<p>'.get_string('welcometocoursetext', '', $orderdetails).'</p>');
                // Send test email.
                ob_start();
                $success = email_to_user($user, $userfrom, $subject, $fullmessage, $fullmessagehtml);
                $smtplog = ob_get_contents();
                ob_end_clean();
            }
            if (!empty($mailteachers) && !empty($teacher)) {
                $orderdetails->course = format_string($course->fullname, true, array('context' => $coursecontext));
                $orderdetails->user = fullname($user);
                $subject = get_string("enrolmentnew", 'enrol', $shortname);
                $fullmessage = get_string('enrolmentnewuser', 'enrol', $orderdetails);
                $fullmessagehtml = html_to_text('<p>'.get_string('enrolmentnewuser', 'enrol', $orderdetails).'</p>');
                // Send test email.
                ob_start();
                $success = email_to_user($teacher, $user, $subject, $fullmessage, $fullmessagehtml);
                $smtplog = ob_get_contents();
                ob_end_clean();
            }
            if (!empty($mailadmins)) {
                $orderdetails->course = format_string($course->fullname, true, array('context' => $coursecontext));
                $orderdetails->user = fullname($user);
                $admins = get_admins();
                foreach ($admins as $admin) {
                    $subject = get_string("enrolmentnew", 'enrol', $shortname);
                    $fullmessage = get_string('enrolmentnewuser', 'enrol', $orderdetails);
                    $fullmessagehtml = html_to_text('<p>'.get_string('enrolmentnewuser', 'enrol', $orderdetails).'</p>');
                    // Send test email.
                    ob_start();
                    $success = email_to_user($admin, $user, $subject, $fullmessage, $fullmessagehtml);
                    $smtplog = ob_get_contents();
                    ob_end_clean();
                }
            }
            $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
            $fullname = format_string($course->fullname, true, array('context' => $context));
            if (is_enrolled($context, $user, '', true)) { // TODO: use real razorpay check.
                redirect($destination, get_string('paymentthanks', '', $fullname));
            } else {
                // Somehow they aren't enrolled yet!
                $PAGE->set_url($destination);
                echo $OUTPUT->header();
                $orderdetails = new stdClass();
                $orderdetails->teacher = get_string('defaultcourseteacher');
                $orderdetails->fullname = $fullname;
                notice(get_string('paymentsorry', '', $orderdetails), $destination);
            }
        } else {
            $html = "<p>Your payment failed</p>
                     <p>{$error}</p>";
        }

        //echo $html;die;
    }
}