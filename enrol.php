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
 * Listens for Instant Payment Notification from Razorpay
 *
 * This script waits for Payment notification from Razorpay,
 * then double checks that data by sending it back to Razorpay.
 * If Razorpay verifies this then it sets up the enrolment for that
 * user.
 *
 * @package    enrol_razorpaypayment
 * @author     Erudisiya <contact.erudisiya@gmail.com>
 * @copyright  2024 Erudisiya Team(https://erudisiya.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/enrollib.php');
// get currency symbal
$currency_symbol = enrol_get_plugin('razorpaypayment')->show_currency_symbol(strtolower($instance->currency));
$plugin = enrol_get_plugin('razorpaypayment');
$dataa = optional_param('data', null, PARAM_RAW);
$enrolbtncolor = $plugin->get_config('enrolbtncolor');
if(abs($cost) < 0.01) {
    $paymentstring = print_string('nocost', 'enrol_razorpaypayment');
} else {
    $paymentstring = print_string("paymentrequired");
}
//print_r($currency_symbol);die;
?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<div class="razorpay-wrap">
    <div class="razorpay-right">
        <p class='razorpay-dclr'><?php $paymentstring ?></p>
        <div class="razorpay-img">
            <img src="<?php echo $CFG->wwwroot; ?>/enrol/razorpaypayment/pix/razorpay-payment.png">
        </div>
        <div class="paydetail">
            <div class="razorpay-line-row">
                <div class="razorpay-line-left"><?php echo get_string("cost") . ":<span> {$currency_symbol}{$cost}</span>"; ?></div>
            </div>
           
            <div id="reload">
                <?php
                $amount = enrol_get_plugin('razorpaypayment')->get_razorpay_amount($cost, $instance->currency, false);
                //print_r($amount);die;
                $costvalue = str_replace(".", "", $cost);
                if ($costvalue == 000) {  ?>
                    <div id="amountequalzero" class="razorpay-buy-btn">
                        <button id="card-button-zero">
                            <?php echo get_string("enrol_now", "enrol_razorpaypayment"); ?>
                        </button>
                    </div>
                <?php } else { ?>
                    <div id="paymentResponse" class="razorpay-buy-btn">
                        <div id="buynow">
                            <button class="razorpay-button" id="payButton"><?php echo get_string("buy_now", "enrol_razorpaypayment"); ?></button>
                        </div>
                    <?php } ?>
                    </div>
                    <?php $PAGE->requires->js_call_amd('enrol_razorpaypayment/razorpay_payment', 'razorpay_payment', array('razorpaypayment', $USER->id, $plugin->get_config('razorpaykeyid'), $plugin->get_config('razorpaykeysecret'), $instance->id, get_string("please_wait", "enrol_razorpaypayment"), get_string("buy_now", "enrol_razorpaypayment"))); ?>
            </div>
        </div>
    </div>
</div>
