
jQuery(function(){                    
    var actionurl='<?php echo admin_url()."admin.php?page=edit-subscription&action=delete"; ?>';                     
    jQuery(".dellink").click(function(){                                                                       

        if(confirm("Are You sure want to delete this record"))
        {    
            var uid=jQuery(this).attr('delid');
            window.location=actionurl+"&uid="+uid;
        }
    });

    jQuery("#pagilimit").change(function(){
        jQuery("#sies_settings_form").submit();
    });

    jQuery("#sies_deleteall_form").submit(function(){                                                        
        if(confirm("Deleting all subscribers will not be restored. Are You sure?")==false) 
        {
            return false;
        }    
    });


});