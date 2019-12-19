<?php
/**
 * Strings for component 'enrol_payment', language 'en'.
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assignrole'] = 'Assign role';
$string['businessemail'] = 'PayPal business email';
$string['businessemail_desc'] = 'The email address of your business PayPal account';
$string['cost'] = 'Enrol cost';
$string['cost_desc'] = 'Note that the cost specified in the individual course setting overrides the site-wide cost.';
$string['costerror'] = 'The enrolment cost is not numeric';
$string['costorkey'] = 'Please choose one of the following methods of enrolment.';
$string['currency'] = 'Currency';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during enrolments';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can be enrolled until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can be enrolled from this date onward only.';
$string['enrolgroup'] = 'Group enrolment';
$string['enrolnogroup'] = 'No group';
$string['errcommunicating'] = 'There was an error communicating with the server. Please refresh the page and try again. If the problem persists, please contact the site administrator.';
$string['errdisabled'] = 'The Payment enrolment plugin is disabled and does not handle payment notifications.';
$string['erripninvalid'] = 'Instant payment notification has not been verified by PayPal.';
$string['errpaypalconnect'] = 'Could not connect to {$a->url} to verify the instant payment notification: {$a->result}';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['mailadmins'] = 'Notify admin';
$string['mailstudents'] = 'Notify students';
$string['mailteachers'] = 'Notify teachers';
$string['messageprovider:payment_enrolment'] = 'Payment enrolment messages';
$string['nocost'] = 'There is no cost associated with enrolling in this course!';
$string['payment:config'] = 'Configure Payment enrol instances';
$string['payment:manage'] = 'Manage enrolled users';
$string['payment:unenrol'] = 'Unenrol users from course';
$string['payment:unenrolself'] = 'Unenrol self from the course';
$string['paypalaccepted'] = 'PayPal payments accepted';
$string['pluginname'] = 'Payment';
$string['pluginname_desc'] = 'The Payment module allows you to set up paid courses.';
$string['privacy:metadata:enrol_payment:enrol_payment'] = 'Information about the Payment transactions for Payment enrolments.';
$string['privacy:metadata:enrol_payment:enrol_payment:business'] = 'Email address or PayPal account ID of the payment recipient (that is, the merchant).';
$string['privacy:metadata:enrol_payment:enrol_payment:courseid'] = 'The ID of the course that is sold.';
$string['privacy:metadata:enrol_payment:enrol_payment:instanceid'] = 'The ID of the enrolment instance in the course.';
$string['privacy:metadata:enrol_payment:enrol_payment:item_name'] = 'The full name of the course that its enrolment has been sold.';
$string['privacy:metadata:enrol_payment:enrol_payment:memo'] = 'A note that was entered by the buyer in PayPal website payments note field.';
$string['privacy:metadata:enrol_payment:enrol_payment:option_selection1_x'] = 'Full name of the buyer.';
$string['privacy:metadata:enrol_payment:enrol_payment:parent_txn_id'] = 'In the case of a refund, reversal, or canceled reversal, this would be the transaction ID of the original transaction.';
$string['privacy:metadata:enrol_payment:enrol_payment:payment_status'] = 'The status of the payment.';
$string['privacy:metadata:enrol_payment:enrol_payment:payment_type'] = 'Holds whether the payment was funded with an eCheck (echeck), or was funded with PayPal balance, credit card, or instant transfer (instant).';
$string['privacy:metadata:enrol_payment:enrol_payment:pending_reason'] = 'The reason why payment status is pending (if that is).';
$string['privacy:metadata:enrol_payment:enrol_payment:reason_code'] = 'The reason why payment status is Reversed, Refunded, Canceled_Reversal, or Denied (if the status is one of them).';
$string['privacy:metadata:enrol_payment:enrol_payment:receiver_email'] = 'Primary email address of the payment recipient (that is, the merchant).';
$string['privacy:metadata:enrol_payment:enrol_payment:receiver_id'] = 'Unique PayPal account ID of the payment recipient (i.e., the merchant).';
$string['privacy:metadata:enrol_payment:enrol_payment:tax'] = 'Amount of tax charged on payment.';
$string['privacy:metadata:enrol_payment:enrol_payment:timeupdated'] = 'The time of Moodle being notified by PayPal about the payment.';
$string['privacy:metadata:enrol_payment:enrol_payment:txn_id'] = 'The merchant\'s original transaction identification number for the payment from the buyer, against which the case was registered';
$string['privacy:metadata:enrol_payment:enrol_payment:userid'] = 'The ID of the user who bought the course enrolment.';
$string['privacy:metadata:enrol_payment:paypal_com'] = 'The Payment enrolment plugin transmits user data from Moodle to the PayPal website.';
$string['privacy:metadata:enrol_payment:paypal_com:address'] = 'Address of the user who is buying the course.';
$string['privacy:metadata:enrol_payment:paypal_com:city'] = 'City of the user who is buying the course.';
$string['privacy:metadata:enrol_payment:paypal_com:country'] = 'Country of the user who is buying the course.';
$string['privacy:metadata:enrol_payment:paypal_com:custom'] = 'A hyphen-separated string that contains ID of the user (the buyer), ID of the course, ID of the enrolment instance.';
$string['privacy:metadata:enrol_payment:paypal_com:email'] = 'Email address of the user who is buying the course.';
$string['privacy:metadata:enrol_payment:paypal_com:first_name'] = 'First name of the user who is buying the course.';
$string['privacy:metadata:enrol_payment:paypal_com:last_name'] = 'Last name of the user who is buying the course.';
$string['privacy:metadata:enrol_payment:paypal_com:os0'] = 'Full name of the buyer.';
$string['processexpirationstask'] = 'Payment enrolment send expiry notifications task';
$string['sendpaymentbutton_paypal'] = 'Send payment via PayPal';
$string['sendpaymentbutton_stripe'] = 'Send payment via Stripe';
$string['status'] = 'Allow Payment enrolments';
$string['status_desc'] = 'Allow users to use PayPal and Stripe to enrol';
$string['transactions'] = 'PayPal transactions';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = '';
$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to user\'s profile page {$a->profileurl}
* User email {$a->email}
* User fullname {$a->fullname}';

$string['discounttype'] = 'Discount type';
$string['discounttype_help'] = 'Select type of discount. If "Value discount" is selected and multiple enrolments are purchased, the discount will be applied per-item.';
$string['nodiscount'] = 'No discount &nbsp;&nbsp;';
$string['percentdiscount'] = 'Percentage discount &nbsp;&nbsp;';
$string['valuediscount'] = 'Value discount &nbsp;&nbsp;';
$string['applydiscount'] = 'Apply discount';
$string['discountthreshold'] = 'Discount threshold';
$string['discountthreshold_help'] = 'Minimum number of seats that a user must purchase in order for a discount to be applied. This allows course creators to set up a discount for bulk purchases. The value "1" means there is no discount threshold.';
$string['discountthresholdtoolow'] = 'Discount threshold must be greater than 0.';
$string['discountthresholdbutnomultipleenrol'] = 'If multiple enrolment is disabled, the discount threshold must not be greater than 1.';
$string['requirediscountcode'] = 'Require discount code';
$string['requirediscountcode_help'] = 'If checked, the user will need to enter the discount code in order to be eligible for a discount. If unchecked, the discount will automatically be applied to purchases that meet the discount threshold.';
$string['discountcode'] = 'Discount code';
$string['discountamount'] = 'Discount amount';
$string['discountamount_help'] = 'If a Percentage discount is used, values under 1.00 will be treated as a percentage (out of 1.00). For example, you may set a 25% discount by entering either "25", "25.00", or "0.25".';
$string['discounttypeerror'] = 'Invalid discount type.';
$string['discountamounterror'] = 'The discount amount is not numeric.';
$string['discountdigitserror'] = 'The discount amount must have fewer than 12 digits.';
$string['negativediscounterror'] = 'The discount amount cannot be negative.';
$string['percentdiscountover100error'] = 'A percentage discount cannot be set above 100.';
$string['allowdiscounts'] = 'Allow course enrolment to include a discount';
$string['allowdiscounts_help'] = 'Allow enrollment instances to include a discount code.';
$string['nogatewayenabled'] = 'PayPal and Stripe are not configured for this site. Please contact the site administrator.';
$string['invalidgateway'] = 'Unrecognized payment gateway. Please contact the site administrator.';
$string['notenoughunits'] = 'Attempting to make a purchase for fewer than 1 users.';
$string['billingaddress'] = 'Require users to enter their billing address';
$string['billingaddress_desc'] = 'This sets the Stripe payment option for whether the user should be asked to input their billing address. It is off by default, but it is a good idea to turn it on.';
$string['validatezipcode'] = 'Validate the billing postal code (Stripe)';
$string['validatezipcode_desc'] = 'This sets the Stripe payment option for whether the billing address should be verified as part of processing the payment. They strongly recommend that this option should be on, to reduce fraud.';
$string['requireshipping'] = 'Require shipping address';
$string['multipleregistration'] = 'Multiple Registration';

//TODO add description - ensure registrants have an account on (long name)
//(Registrant 1 email, instead of Email 1)
$string['multipleregistration_help'] = 'Purchase enrolment for other(s). To register yourself as well as others, enter your email address and click on the "Add a registrant" icon.<br>';
$string['allowmultipleenrol'] = 'Allow multiple registration';
$string['allowmultipleenrol_help'] = 'Allow a user to enrol other users by entering their email address.';
$string['sameemailaccountsallowed'] = "Error: Accounts sharing the same email address are allowed on this Moodle site. Because of this, the Multiple Registration cannot be used. Please contact your site administrator.";
$string['duplicateemail'] = "Error: Duplicate emails were entered in the multiple registration form.";

$string['paypalaccountnotneeded'] = "<b>A PayPal account is not needed to pay by credit card.</b> <br>".
"At the PayPal site, there is a <i>\"Pay with a credit or Visa Debit card\"</i> <br>".
"button. Please note that the name and address on the form <b>must</b><br>".
"<b>match</b> the name and address associated with the credit card.";

$string['or'] = "OR";

//TODO use site long name
$string['usersnotfoundwithemail'] = "Either the registrant(s) have not yet created an account or their account is associated with a different email address:<p></p><ul><li>";
$string['totalcost'] = 'If your intention is to register <b>yourself as well</b> and your name is not in the list, click <b>Cancel</b>. On the Enrolment page, enter your email address as one of the \"others\" to enrol.</p><p>The <b>{$a->symbol}{$a->discountAmount}{$a->percentSymbol}{$a->perSeat}</b> discount has been applied.</p></p>Total cost:&nbsp;';
$string['multipleregistrationconfirmuserlist'] = "You are purchasing a registration for each of the following: <p></p><ul><li>";
$string['enabletaxcalculation'] = "Enable tax calculation";
$string['enabletaxcalculation_help'] = "If the \"msn\" user profile field is overloaded to store a canadian province abbreviation, calculate the tax and factor into the cost.";
$string['defaultcoursewelcomemessage'] = "Default course welcome message";
$string['stripesecretkey'] = "Stripe Secret Key";
$string['stripesecretkey_desc'] = "The API secret key of your Stripe account";
$string['stripepublishablekey'] = "Stripe Publishable Key";
$string['stripepublishablekey_desc'] = "The API Publishable Key of your Stripe account";
$string['stripelogo'] = "Stripe logo";
$string['stripelogo_desc'] = "128x128 store logo used for Stripe checkout";
$string['charge_description1'] = "create customer for email receipt";
$string['charge_description2'] = 'Charge for Course Enrolment';
$string['addaregistrant'] = 'Add a registrant';
$string['removearegistrant'] = 'Remove the registrant';

$string['taxdefinitions'] = 'Custom tax definitions';
$string['taxdefinitions_help'] = "The format for each entry is Province/State Code : 0.## for tax rate. For instance, assume there are only two taxable provinces: Ontario (code ON and rate 13%) and Quebec (code QC and rate 5%), the entries would be:<br>".
"ON : 0.13<br>".
"QC : 0.05<br>".
"Enter each tax definition on a <b>separate</b> line. The script will pull the province/state code from the MSN field in the user profile (setup instructions are detailed in the ReadMe.txt file) to determine how much tax to add to the course fee.";
$string['definetaxes'] = 'Allow custom tax definitions';
$string['feestring_taxed'] = 'The fee for <b>{$a->coursefullname}</b><br>is <b>{$a->symbol}<span class="localisedcost-untaxed">{$a->localisedcost_untaxed}</span></b> + {$a->symbol}<span class="taxamountstring">{$a->tax_amount_string}</span> <span class="taxstring">{$a->tax_string}</span> = <b>{$a->symbol}<span class="localisedcost">{$a->localisedcost}</span></b> {$a->currency}.<br>';
$string['feestring_notax'] = 'The fee for <b>{$a->coursefullname}</b><br>is <b>{$a->symbol}<span class="localisedcost">{$a->localisedcost}</span></b> {$a->currency}.<br>';
$string['discountwillbeapplied'] = 'A <b>{$a->symbol}{$a->discountAmount}{$a->percentSymbol}{$a->perSeat}</b> discount will be applied to a purchase of <b>{$a->discountThreshold}</b> or more registrations.';

$string['allowbanktransfer'] = 'Allow Bank/Email transfer payment';
$string['transferinstructions'] = 'Bank/Email transfer payment instructions';
$string['transferinstructions_help'] = "This text will appear on the course enrolment page. Bank or email money transfer instructions are customizable, but it is advisable to not alter {{AMOUNT}}, {{COURSEFULLNAME}} or {{COURSESHORTNAME}}. These variables are replaced by the plugin code. Note that {{AMOUNT}} is replaced with the course fee + tax (if applicable).";
$string['transferinstructions_default'] = "<h4>Prefer to pay directly from your bank account?</h4><br>".
"<p>Email money transfer (known as <a href=\"https://www.youtube.com/watch?time_continue=4&v=zL9yoZZXyOE\" target=\"_blank\"><em>Interac</em> e-Transfer</a>) is an option if you:</p>".
"<ul>".
"<li>have an email address or a mobile number, and</li>".
"<li>are registered for <em>Interac</em> e-Transfer service with your financial institution.</li>".
"</ul>".
"<p>To send an <em>Interac</em> e-Transfer payment:</p>".
"<ol>".
"<li><p>Log in to your financial institution's online or mobile banking and navigate to <em>Interac</em> e-Transfer menu.</p></li>".
"<li><p>Select the account from which to withdraw the funds.</p></li>" .
"<li><p>Add a new recipient using <strong>ENTER RECIPIENT NAME</strong> as the name, <strong>ENTER EMAIL ADDRESS TO RECEIVE THE FUNDS</strong> as the email address, <strong>Who is offering {{COURSESHORTNAME}}?</strong> as the security question, and <strong>ENTER THE ANSWER WITH A MINIMUM OF 5 CHARACTERS</strong> as the answer.</p></li>".
"<li><p>Select <strong>ENTER RECIPIENT NAME</strong> as the recipient.</p></li>".
"<li><p>Fill in the amount of <strong>{{AMOUNT}}</strong> and enter <strong>your name</strong> in the message area.</p></li>".
"<li><p>Follow the on-screen instructions to confirm the information and complete the transfer.</p></li>".
"</ol>".
"<p>Once payment is received, you will be notified of your enrolment in <strong>{{COURSEFULLNAME}}</strong>.</p>";

$string['paypalwait'] = 'Please wait while PayPal confirms your payment. You will be given access to <i>{$a}</i> when the payment has completed.';
$string['errorcheckingenrolment'] = "Failure checking user enrolment. Please contact your server administrator. In the meantime, you should navigate to the course manually.";
$string['thanksforpaypal'] = 'Thank you for your multiple enrolment purchase. The registrant(s) have been successfully enrolled in <i>{$a}</i>.';
$string['correctdiscountcode'] = 'Valid discount code';
$string['correctdiscountcode_desc'] = 'The discount code you have entered is valid.';
$string['incorrectdiscountcode'] = 'Invalid discount code';
$string['incorrectdiscountcode_desc'] = 'The discount code you have entered is incorrect.';
$string['errorpaymentpending'] = 'Your PayPal payment is stuck in "{$a->reason}" status. This can be due to currency mismatch or other PayPal configuration issues. Please {$a->supportemaillink} the site administrator.';
$string['needdiscountcode'] = 'Please provide a discount code.';
$string['needdiscountamount'] = 'Please provide a discount amount.';
$string['enrolothers'] = 'Enrol other(s)';
$string['cancelenrolothers'] = 'Cancel multiple enrolment';
$string['confirmpurchase'] = 'Confirm Purchase';
$string['continue'] = 'Continue';
$string['invalidpaymentprovider'] = 'Invalid payment provider.';
$string['dismiss'] = 'Dismiss';
$string['novalidemailsentered_desc'] = 'No valid email address(es) have been entered. Either enter at least one email address or click on the <i>Cancel multiple enrolment</i> button.';
$string['novalidemailsentered'] = 'No registrant(s) specified';
$string['totalenrolmentfee'] = 'Total enrolment fee:';
$string['charge_enrolment'] = 'Enrolment in: ';
$string['error'] = 'Error'; 

$string['prerequisite'] = 'Prerequisite courses';
$string['prerequisite_help'] = 'Optional. Allow access only to users who have completed all/any selected courses';
$string['corequisite'] = 'Corequisite courses';
$string['corequisite_help'] = 'Optional. Allow access only to users with current enrolments in all/any selected courses';
$string['conflicting'] = 'Conflicting courses';
$string['conflicting_help'] = 'Optional. Disallow access to users who have either completed or have current enrolments in all/any selected courses';
$string['cohortmember'] = 'Cohort member';
$string['cohortmember_help'] = 'Optional. Allow access only users who are members of all/any selected cohorts';
$string['cohortnonmember'] = 'Cohort non-member';
$string['cohortnonmember_help'] = 'Optional. Disallow access to users who are members of all/any selected cohorts';
$string['prerequisite_corequisite_overlap'] = 'Courses cannot be both a prerequisite and corequisite';
$string['prerequisite_conflicting_overlap'] = 'Courses cannot be both a prerequisite and conflicting';
$string['corequisite_conflicting_overlap'] = 'Courses cannot be both a corequisite and conflicting';
$string['cohort_overlap'] = 'Cohorts cannot appear in both cohort restrictions';
