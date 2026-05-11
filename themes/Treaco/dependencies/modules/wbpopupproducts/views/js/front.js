/*!
 * @update 23.12.2020
 * @author Webibazaar Template https://www.webibazaar.com
 * @contact info@webibazaar.com 
 * @license
 * Copyright  2016-2020 Winter Infotech Team
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 */

$(document).ready(function(){
    $('.wbpopup-popducts .wbpopup-popducts-item').hide();
    var i = 0;

    setIntervalFun();
    var totalTimer = parseInt(WBPOPUPPRODUCTS_DISPLAY_TIME) + parseInt(WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION);
    setInterval( setIntervalFun, totalTimer);
    function setIntervalFun()
    {
        $('.wbpopup-popducts .wbpopup-popducts-item').eq(i).fadeIn(400);
        setTimeout(function(){
            $('.wbpopup-popducts .wbpopup-popducts-item').eq(i).fadeOut(400);
            i++;
            if ($('.wbpopup-popducts .wbpopup-popducts-item').length <= i) {
                i = 0;
            }
        },parseInt(WBPOPUPPRODUCTS_DISPLAY_TIME));
    }

    $(document).on('click', '.wbpopup-popducts .wbpopup-popducts-close-icon', function(){
        $('.wbpopup-popducts .wbpopup-popducts-item').fadeOut(400).remove();
        $.ajax({
            async: true,
            method: "POST",
            url: popup_close_link,
            success: function (data) {
                
            }
        });
    });

});
