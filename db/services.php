<?php
$services = array(
    'moodle_enrol_razorpaypayment' => array(                      //the name of the web service
        'functions' => array('moodle_razorpaypayment_free_enrolsettings', 'moodle_razorpaypayment_razorpay_js_settings', 'moodle_razorpaypayment_success_razorpay_url'), //web service functions of this service
        'requiredcapability' => '',                //if set, the web service user need this capability to access 
        //any function of this service. For example: 'some/capability:specified'                 
        'restrictedusers' => 0,                      //if enabled, the Moodle administrator must link some user to this service
        //into the administration
        'enabled' => 1,                               //if enabled, the service can be reachable on a default installation
        'shortname' => 'enrolrazorpaypayment' //the short name used to refer to this service from elsewhere including when fetching a token
    )
);
$functions = array(
    'moodle_razorpaypayment_free_enrolsettings' => array(
        'classname' => 'moodle_enrol_razorpaypayment_external',
        'methodname' => 'razorpaypayment_free_enrolsettings',
        'classpath' => 'enrol/razorpaypayment/externallib.php',
        'description' => 'Update information after Successful Free Enrol',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ),
    'moodle_razorpaypayment_razorpay_js_settings' => array(
        'classname' => 'moodle_enrol_razorpaypayment_external',
        'methodname' => 'razorpay_js_method',
        'classpath' => 'enrol/razorpaypayment/externallib.php',
        'description' => 'Update information after Razorpay Successful Connect',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ),
    'moodle_razorpaypayment_success_razorpay_url' => array(
        'classname' => 'moodle_enrol_razorpaypayment_external',
        'methodname' => 'success_razorpay_url',
        'classpath' => 'enrol/razorpaypayment/externallib.php',
        'description' => 'Update information after Razorpay Successful Payment',
        'type' => 'write',
        'ajax' => true,
    )
);
