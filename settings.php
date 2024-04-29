<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Razorpay enrolments plugin settings and presets.
 *
 * @package    enrol_razorpaypayment
 * @copyright  2024 Erudisiya Team(https://erudisiya.com)
 * @author     Erudisiya <contact.erudisiya@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/enrol/razorpaypayment/lib.php');
if (is_siteadmin()) {
    $settings->add(new admin_setting_heading(
        'enrol_razorpaypayment_settings',
        '',
        get_string('pluginname_desc', 'enrol_razorpaypayment')
    ));
    $settings->add(new admin_setting_configtext('enrol_razorpaypayment/razorpaymerchant', get_string('merchantid', 'enrol_razorpaypayment'), get_string('merchantid_desc', 'enrol_razorpaypayment'), '', PARAM_RAW));
    $settings->add(new admin_setting_configtext('enrol_razorpaypayment/razorpaykeyid', get_string('keyid', 'enrol_razorpaypayment'), get_string('keyid_desc', 'enrol_razorpaypayment'), '', PARAM_RAW));
    $settings->add(new admin_setting_configtext('enrol_razorpaypayment/razorpaykeysecret', get_string('keysecret', 'enrol_razorpaypayment'), get_string('keysecret_desc', 'enrol_razorpaypayment'), '', PARAM_RAW));
    $settings->add(new admin_setting_configcheckbox('enrol_razorpaypayment/mailstudents', get_string('mailstudents', 'enrol_razorpaypayment'), '', 0));
    $settings->add(new admin_setting_configcheckbox('enrol_razorpaypayment/mailteachers', get_string('mailteachers', 'enrol_razorpaypayment'), '', 0));
    $settings->add(new admin_setting_configcheckbox('enrol_razorpaypayment/mailadmins', get_string('mailadmins', 'enrol_razorpaypayment'), '', 0));
    // Variable $enroll button color.
    $settings->add( new admin_setting_configcolourpicker(
        'enrol_razorpaypayment/enrolbtncolor', 
        get_string('enrol_btn_color', 'enrol_razorpaypayment'), 
        get_string('enrol_btn_color_des', 'enrol_razorpaypayment'), 
        '#1177d1'
    ));
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    // it describes what should happen when users are not supposed to be enrolled any more.
    $options = array(
        ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
    );
    $settings->add(new admin_setting_configselect('enrol_razorpaypayment/expiredaction', get_string('expiredaction', 'enrol_razorpaypayment'), get_string('expiredaction_help', 'enrol_razorpaypayment'), ENROL_EXT_REMOVED_SUSPENDNOROLES, $options));
    // webservice token
    $rest_web_link = $CFG->wwwroot . '/admin/settings.php?section=webserviceprotocols';
    $create_token = $CFG->wwwroot . '/admin/webservice/tokens.php';
    $settings->add(new admin_enrol_razorpaypayment_configtext(
        'enrol_razorpaypayment/webservice_token',
        get_string('webservice_token_string', 'enrol_razorpaypayment'),
        get_string('create_user_token', 'enrol_razorpaypayment') . '<a href="' . $rest_web_link . '" target="_blank"> ' . get_string('from_here', 'enrol_razorpaypayment') . '</a> . ' . get_string('enabled_rest_protocol', 'enrol_razorpaypayment') . '<a href="' . $create_token . '" target="_blank"> ' . get_string('from_here', 'enrol_razorpaypayment') . '</a>
        ',
        ''
    ));
    // Enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_razorpaypayment_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_razorpaypayment/status',
        get_string('status', 'enrol_razorpaypayment'), get_string('status_desc', 'enrol_razorpaypayment'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_razorpaypayment/cost', get_string('cost', 'enrol_razorpaypayment'), '', 0, PARAM_FLOAT, 4));

    $razorpaycurrencies = enrol_get_plugin('razorpaypayment')->get_currencies();
    $settings->add(new admin_setting_configselect('enrol_razorpaypayment/currency', get_string('currency', 'enrol_razorpaypayment'), '', 'USD', $razorpaycurrencies));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_razorpaypayment/roleid',
            get_string('defaultrole', 'enrol_razorpaypayment'),
            get_string('defaultrole_desc', 'enrol_razorpaypayment'),
            $student->id ?? null,
            $options));
    }

    $settings->add(new admin_setting_configduration('enrol_razorpaypayment/enrolperiod',
        get_string('enrolperiod', 'enrol_razorpaypayment'), get_string('enrolperiod_desc', 'enrol_razorpaypayment'), 0));

}
