/**
 * PayPal return page for pending payment
 */

define(['jquery', 'enrol_payment/spin', 'core/str', 'core/config'], function($, Spinner, MoodleStrings, MoodleCfg) {
    var PayPalReturn = {

        checkEnrol: function(ajaxurl, courseid, mdlstr, dest, paymentid) {
            $.ajax({
                url : ajaxurl,
                method : "POST",
                data : {
                    'courseid' : courseid,
                    'paymentid' : paymentid
                },
                success : function(r) {
                    var res = JSON.parse(r);
                    if (res["result"] === true) {
                        window.location.href = dest;
                    } else if (res["result"] === false && res["reason"]) {
                        window.location.href = MoodleCfg.wwwroot +
                                              "/enrol/payment/paypalPending.php?id=" +
                                              courseid.toString() + "&reason=" + res["reason"];
                    } else {
                        return;
                    }
                },
                error : function() {
                    alert(mdlstr[0]);
                }
            });
        },

        init: function(dest, ajaxurl, courseid, paymentid) {
            var self = this;
            var str_promise = MoodleStrings.get_strings([
                { key : "errorcheckingenrolment", component : "enrol_payment" },
                { key : "errorpaymentpending", component : "enrol_payment" }
            ]);

            str_promise.done(function(strs) {

                var spinopts = {
                  lines: 13, // The number of lines to draw
                  length: 38, // The length of each line
                  width: 17, // The line thickness
                  radius: 45, // The radius of the inner circle
                  scale: 0.45, // Scales overall size of the spinner
                  corners: 1, // Corner roundness (0..1)
                  color: '#000000', // CSS color or array of colors
                  fadeColor: 'transparent', // CSS color or array of colors
                  speed: 1, // Rounds per second
                  rotate: 0, // The rotation offset
                  animation: 'spinner-line-fade-quick', // The CSS animation name for the lines
                  direction: 1, // 1: clockwise, -1: counterclockwise
                  zIndex: 2e9, // The z-index (defaults to 2000000000)
                  className: 'spinner', // The CSS class to assign to the spinner
                  shadow: '0 0 1px transparent', // Box-shadow for the lines
                  position: 'relative' // Element positioning
                };

                var target = document.getElementById('spin-container');
                var spinner = new Spinner(spinopts);
                spinner.spin(target);

                self.checkEnrol(ajaxurl, courseid, strs, dest, paymentid);
                setInterval(function() { self.checkEnrol(ajaxurl, courseid, strs, dest, paymentid); }, 5000);
            });
        }
    };

    return PayPalReturn;
});
