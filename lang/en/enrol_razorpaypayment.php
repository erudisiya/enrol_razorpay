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
 * Strings for component 'enrol_razorpaypayment', language 'en'.
 *
 * @package    enrol_razorpaypayment
 * @author     Erudisiya <contact.erudisiya@gmail.com>
 * @copyright  2024 Erudisiya Team(https://erudisiya.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Razorpay Payment';
$string['pluginname_desc'] = 'The razorpay module allows you to set up paid courses.  If the cost for any course is zero, then students are not asked to pay for entry.  There is a site-wide cost that you set here as a default for the whole site and then a course setting that you can set for each course individually. The course cost overrides the site cost.';
$string['merchantid'] = 'Razorpay Merchant ID';
$string['merchantid_desc'] = 'The Merchant ID of your business Razorpay account';
$string['keyid'] = 'Razorpay Key ID';
$string['keyid_desc'] = 'The Key ID of your business Razorpay account';
$string['keysecret'] = 'Razorpay Key secret';
$string['keysecret_desc'] = 'The Key secret of your business Razorpay account';
$string['mailadmins'] = 'Notify admin';
$string['mailstudents'] = 'Notify students';
$string['mailteachers'] = 'Notify teachers';
$string['enrol_btn_color'] = 'Choose Enroll button Color';
$string['enrol_btn_color_des'] = 'Choose your own custom Color scheme for the Enroll Button.';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['status'] = 'Allow Razorpay enrolments';
$string['status_desc'] = 'Allow users to use Razorpay to enrol into a course by default.';
$string['cost'] = 'Enrol cost';
$string['costerror'] = 'The enrolment cost is not numeric';
$string['currency'] = 'Currency';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during Razorpay enrolments';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited.';
// instance page
$string['assignrole'] = 'Assign role';
$string['maxenrolled'] = 'Max enrolled users';
$string['maxenrolled_help'] = 'Specifies the maximum number of users that can razorpaypayment enrol. 0 means no limit.';
$string['maxenrolledreached'] = 'Maximum number of users allowed to razorpaypayment-enrol was already reached.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can be enrolled from this date onward only.';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can be enrolled until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';


$string['enrollsuccess'] = 'Thankyou! Now you are enrolled into the course ';
$string['unmatchedcourse'] = 'Course Id does not match to the course settings, received: ';
$string['invalidcontextid'] = 'Not a valid context id! ';
$string['invalidcourseid'] = 'Not a valid course id!';
$string['invalidinstance'] = 'Not a valid instance id!';
$string['invaliduserid'] = 'Not a valid user id! ';
$string['invalidrequest'] = 'Invalid Request!';
$string['enrol_now'] = 'Enrol Now';
$string['buy_now'] = 'Buy Now';
$string['please_wait'] = 'Please wait...';
$string['webservice_token_string'] = 'User Token';
$string['create_user_token'] = 'REQUIRED: To make Stripe callback work, you must enable Moodle REST protocol on your site';
$string['from_here'] = 'from here';
$string['enabled_rest_protocol'] = ' You must also create a token of moodle_enrol_stripepayment service with Administrator privilege ';
$string['nocost'] = 'There is no cost associated with enrolling in this course!';