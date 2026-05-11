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
    $(document).on('click', '.wbblink-product-admin .wbcustom-data-table .delete', function(){
        if (confirm('Are you want to delete this Record?'))
            return true;

        return false;
    });
    $('.make-custom-select-box').select2();
});
