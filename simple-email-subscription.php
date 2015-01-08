<?php

/*
 *  Plugin Name: Simple Email Subscription
 *  Version: 1.0
 *  Author: Yudiz Solutions
 *  Author URI: http://www.yudiz.com/  
 */

    
global $sies_db_version;
$sies_db_version = '1.0';

function sies_install() {
	global $wpdb;
	global $sies_db_version;

	$table_name = $wpdb->prefix . 'sies_emails';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,		
		fullname varchar(55) DEFAULT '' NOT NULL,		
                email varchar(55) DEFAULT '' NOT NULL,
                reg_date date DEFAULT '0000-00-00' NOT NULL,
                ipaddress varchar(55) DEFAULT ''
	)";        

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'sies_db_version', $sies_db_version );        
        add_option('sies_per_page','10');
}

register_activation_hook( __FILE__, 'sies_install' );

/* including front-end form generating file */
require_once(plugin_dir_path( __FILE__ ).'subscriber-form.php');

add_filter('widget_text', 'do_shortcode');


/* SIES Plugin Admin pages */

add_action( 'admin_menu', 'register_sies_mainpage' );

function register_sies_mainpage(){    
    add_menu_page("SIES Listing", "Email Subscribers", 'manage_options','sies', 'sies_mainpage_show');            
}

function sies_mainpage_show(){
    if( !session_id() )
        session_start();    
    global $wpdb;
    $table=$wpdb->prefix."sies_emails";
    
    if(isset($_REQUEST['delete_subs']) && $_REQUEST['delete_subs'] !='')
    {
        $wpdb->query("truncate table ".$table);
    }  
    
    $email_count=$wpdb->get_row('select count(*) as count from '.$table,ARRAY_A);
    $total_subscribers=$email_count['count'];        
    
    if(isset($_REQUEST['pagilimit']) && $_REQUEST['pagilimit'] !='')
    {
        update_option('sies_per_page',$_REQUEST['pagilimit']);            
    } 
    
    if($total_subscribers==0)
    {
        echo "<h3>No subscribers found in Database</h3>";
        goto notfound;
    }    
    
    if(isset($_REQUEST['pg']) && $_REQUEST['pg']!='')
    {
        $pg=$_REQUEST['pg'];
    }
    else
    {
        $pg=1;
    }        
        
    $limit=get_option('sies_per_page');    
    $offset=$limit*($pg-1);    
    $total_pages=ceil($total_subscribers/$limit);            
    
    if($pg<1 or $pg>$total_pages)
    {
        echo "<h3>Invalid Page Request</h3>";
        goto notfound;
    }    
    
    $sies_email_list=$wpdb->get_results('select * from '.$table."  limit  $offset,$limit",ARRAY_A);        
    
    $export_xls_URL = add_query_arg(array(
        'action' => 'sies_export_xls',
        'nc' => time(),     // cache buster
    ), admin_url('admin-ajax.php'));    
        
    
    $export_csv_URL = add_query_arg(array(
        'action' => 'sies_export_csv',
        'nc' => time(),     // cache buster
    ), admin_url('admin-ajax.php'));  
        
    ?>
    <div class="wrap">
        <h2>Subscribers</h2>
        <div class="msggreen">
            <?php            
                echo $_SESSION['mymsg']; 
                $_SESSION['mymsg']='';
            ?>
        </div>
        
        <div class="exportlinks">
            <div style="float:left;">
                <form name="sies_settings_form" action="" method="POST" id="sies_settings_form">
                    <select name="pagilimit" id="pagilimit">                        
                        <option value="10" <?php if($limit=='10') echo " selected ";  ?> >10</option>
                        <option value="20" <?php if($limit=='20') echo " selected ";  ?> >20</option>
                        <option value="50" <?php if($limit=='50') echo " selected ";  ?> >50</option>
                        <option value="100" <?php if($limit=='100') echo " selected ";  ?> >100</option>
                    </select>
                </form>                                                                
            </div>
            <div style="float:left;margin: 0px 0px 0px 10px;">
                <form name="sies_deleteall_form" id="sies_deleteall_form" action="" method="POST">                    
                    <input type="submit" name="delete_subs" value="Delete All" class="sies_updatebtn button">
                </form>
            </div>                                    
            <?php printf('<a href="%s">Export to csv</a>', $export_csv_URL); ?><br>
            <?php printf('<a href="%s">Export to Excel</a>', $export_xls_URL); ?>            
        </div>

        <table border="1" class="tbl_maillist" cellpadding="2">
            <tr>
                <th>&nbsp;</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Date Subscribed <small>(DD-MM-YYYY)</small></th>
                <th>IP Address</th>
                <th colspan="2">Action</th>
            </tr>    
            <?php       
                for($i=0;$i<count($sies_email_list);$i++)
                {
                    echo "<tr>";
                    echo "<td align='center' >".($offset+$i+1)."</td>";
                    echo "<td align='center' >".$sies_email_list[$i]['fullname']."</td>";
                    echo "<td align='center' >".$sies_email_list[$i]['email']."</td>";
                    echo "<td align='center' >".date('d-m-Y',strtotime($sies_email_list[$i]['reg_date']))."</td>";
                    echo "<td align='center' >".$sies_email_list[$i]['ipaddress']."</td>";
                    echo "<td align='center' >"
                    . "<a rid='".$sies_email_list[$i]['id']."' title='Edit' href='".admin_url()."admin.php?page=edit-subscription&uid=".$sies_email_list[$i]['id']."&action=edit'><img width='16' height='16' src='".plugin_dir_url( __FILE__ )."images/editicon.png'></a>"
                    . "</td>";                   
                    echo "<td align='center' ><a class='dellink' delid='".$sies_email_list[$i]['id']."' title='Delete' href='javascript:void(0);'><img width='16' height='16' src='".plugin_dir_url( __FILE__ )."images/deleteicon.png'></a></td>";
                    echo "</tr>";
                }
                ?>
        </table> 
        
        <div class="sies_pagi">
        <?php             
            if($pg==1)
            {    
                echo " <a href='#' class='sies_pagilinks actpg' >First</a> ";
                echo " <a href='#' class='sies_pagilinks actpg' >Previous</a> ";
            }    
            else
            {    
                echo " <a href='".admin_url()."admin.php?page=sies&pg=1"."' class='sies_pagilinks' >First</a> ";
                echo " <a href='".admin_url()."admin.php?page=sies&pg=".($pg-1)."' class='sies_pagilinks' >Previous</a> ";
            }    
                                                          
            
            for($i=1;$i<=$total_pages;$i++)
            {                                                
                if($i==$pg)
                {    
                    echo " <a href='#' class='sies_pagilinks actpg' >$i</a> ";
                }    
                else
                {    
                    echo " <a href='".admin_url()."admin.php?page=sies&pg=$i"."'  class='sies_pagilinks' >$i</a> ";
                }    
            }
            
                        
            if($pg==$total_pages)
            {    
                echo " <a href='#' class='sies_pagilinks actpg' >Next</a> ";
                echo " <a href='#' class='sies_pagilinks actpg' >Last</a> ";
            }    
            else                
            {    
                echo " <a href='".admin_url()."admin.php?page=sies&pg=".($pg+1)."' class='sies_pagilinks' >Next</a> ";
                echo " <a href='".admin_url()."admin.php?page=sies&pg=$total_pages"."' class='sies_pagilinks' >Last</a> ";
            }    
                                                                                                
        ?>
        </div>        
        <?php notfound: ?>
        <div style="clear:both;"></div>
    </div>                
        <?php                                                
        }
    /* -------------------------------------------- */    
        
                   
        
    /* Subpage where admin can edit the  subscription */    
    add_action( 'admin_menu', 'register_sies_subpage' );

    function register_sies_subpage(){
        add_submenu_page(null,'Edit Subscription','Edit Selected Subscription','manage_options', 'edit-subscription','show_editsub_page');
    }

    function show_editsub_page(){
        if( !session_id() )
            session_start();
        
        global $wpdb;
        $table_email=$wpdb->prefix."sies_emails";
        $location=admin_url()."admin.php?page=sies"; 
        $err="";
        $uid="";
        
        
        if(isset($_REQUEST['submit'])==false && isset($_REQUEST['uid'])==false)
            echo "<script>window.location='".$location."'</script>";
        
        if(isset($_REQUEST['uid']))
            $uid=$_REQUEST['uid'];
        
        if($_REQUEST['action']=='delete')
        {
            $delete_sub_qeury="delete from $table_email WHERE id =".$_REQUEST['uid'];            
            $wpdb->query($delete_sub_qeury);
            $_SESSION['mymsg']="Subscription Deleted Successfully !...";                        
            echo "<script>window.location='".$location."'</script>";  
        }    
        
        if(isset($_REQUEST['submit']) && $_REQUEST['submit']!='')
        {   
            $sies_emailchk=$wpdb->get_results('select * from '.$table_email.' where email="'.$_REQUEST['email'].'" and  id!="'.$_REQUEST['userid'].'" ',ARRAY_A); 
            if(empty($sies_emailchk))
            {
                $wpdb->query("UPDATE $table_email 
                    SET fullname = '".$_REQUEST['fullname']."',email='".$_REQUEST['email']."',reg_date='".date_format(date_create($_REQUEST['reg_date']),'Y-m-d')."' 
                    WHERE id =".$_REQUEST['userid']);
                $_SESSION['mymsg']="Subscription Edited Successfully !...";                                      
                echo "<script>window.location='".$location."'</script>";
            }
            else
            {
                $err="Email Already registered";
                $uid=$_REQUEST['userid'];
            }                            
        } 
                
        $sies_email=$wpdb->get_results("select * from ".$table_email." where id=".$uid,ARRAY_A);                       
        
        ?>
        <div class="wrap">
            <h2>Edit Subscriber</h2>
            
            <span class="errormsg">
                <?php  echo $err; ?>
            </span>
            
            <div class="common">
                <form name="editsubscriptionfrm" action="<?php echo admin_url(); ?>admin.php?page=edit-subscription" method="POST">
                    <table>
                        <tr>
                            <td>Full Name</td>
                            <td>
                                <input type="hidden" name="userid" value="<?Php echo $sies_email[0]['id']; ?>">
                                <input type="text" name="fullname" value="<?Php echo $sies_email[0]['fullname']; ?>" size="30">
                            </td>
                        </tr>                    
                        <tr>
                            <td>Email</td>
                            <td><input type="text" name="email" value="<?Php echo $sies_email[0]['email']; ?>" size="30"></td>
                        </tr>                    
                        <tr>
                            <td>Subscription Date</td>
                            <td><input type="text" name="reg_date" id="reg_date" value="<?php echo date('d-m-Y',  strtotime($sies_email[0]['reg_date'])); ?>" size="30" readonly="readonly"></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td><input type="submit" name="submit" value="Update" class="sies_updatebtn button"></td>
                        </tr>

                    </table>
                </form>
            </div>                            
        </div>
        <?php        
    }
    /* ----------------------- */    
    
                          
    /* Initializing session on wordpress loading event */
    add_action('init', 'custom_init_session', 1);
    function custom_init_session() {
        if (!session_id())
            session_start();
    }
    /* -------------------------------- */
    
    
    /* Widget create (first test widget) */
    
    /**
 * Adds Foo_Widget widget.
 */
    class SIES_Widget extends WP_Widget {

            /**
             * Register widget with WordPress.
             */
            function __construct() {
                    parent::__construct(
                            'sieswidget', // Base ID
                            __( 'SIES Subscription Form', 'text_domain' ), // Name
                            array( 'description' => __( 'A Foo Widget', 'text_domain' ), ) // Args
                    );
            }

            /**
             * Front-end display of widget.
             *
             * @see WP_Widget::widget()
             *
             * @param array $args     Widget arguments.
             * @param array $instance Saved values from database.
             */
            public function widget( $args, $instance ) {

                    echo $args['before_widget'];
                    if ( ! empty( $instance['title'] ) ) {
                            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
                    }
                    echo __( do_shortcode('[sies_subc_form]'), 'text_domain' );
                    echo $args['after_widget'];
            }

            /**
             * Back-end widget form.
             *
             * @see WP_Widget::form()
             *
             * @param array $instance Previously saved values from database.
             */
            public function form( $instance ) {
                    $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
                    ?>
                    <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
                    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
                    </p>
                    <?php 
            }

            /**
             * Sanitize widget form values as they are saved.
             *
             * @see WP_Widget::update()
             *
             * @param array $new_instance Values just sent to be saved.
             * @param array $old_instance Previously saved values from database.
             *
             * @return array Updated safe values to be saved.
             */
            public function update( $new_instance, $old_instance ) {
                    $instance = array();
                    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

                    return $instance;
            }

    } // class Foo_Widget
    
    
    // register sies_Widget widget
    function register_sies_widget() {
        register_widget( 'SIES_Widget' );
    }
    add_action( 'widgets_init', 'register_sies_widget' );
    
    /* ---------------------------------------- */
    
    
    
    /*  Export to Excel & CSV  */
    function sies_export_xls() {
    
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=emailsubscribers.xls');
        header("Pragma: no-cache");
        header("Expires: 0");


        global $wpdb;
        $table=$wpdb->prefix."sies_emails"; 
        //echo $table;
        $sies_email_list=$wpdb->get_results('select * from '.$table,ARRAY_A);    
        //echo "yap: <pre>";print_r($sies_email_list);exit;

        echo "FullName \t Email \t Date Subscribed \n\n";
        // loop over the rows, outputting them
        for($i=0;$i<count($sies_email_list);$i++)
        {
            echo $sies_email_list[$i]['fullname']." \t ".$sies_email_list[$i]['email']." \t ".$sies_email_list[$i]['reg_date']."\n";        
        } 
        exit;
    }

    add_action('wp_ajax_sies_export_xls', 'sies_export_xls');
    
    
    function sies_export_csv() {
    
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=emailsubscribers.csv');    
        header("Pragma: no-cache");
        header("Expires: 0");


        global $wpdb;
        $table=$wpdb->prefix."sies_emails"; 
        //echo $table;
        $sies_email_list=$wpdb->get_results('select * from '.$table,ARRAY_A);    
        //echo "yap: <pre>";print_r($sies_email_list);exit;

        echo "FullName,Email,Date Subscribed \n\n";
        // loop over the rows, outputting them
        for($i=0;$i<count($sies_email_list);$i++)
        {
            echo $sies_email_list[$i]['fullname'].",".$sies_email_list[$i]['email'].",".$sies_email_list[$i]['reg_date']."\n";        
        } 
        exit;
    }

    add_action('wp_ajax_sies_export_csv', 'sies_export_csv');
    /* ----------------------------------- */
    
   
    
    /* including js and css files to a particular admin page */
    function enqueue_admin_js_css($hook) {
        
        if ($hook=='toplevel_page_sies') {            
            wp_enqueue_style("siesstyle",plugins_url('sies-admin-style.css',__FILE__));
            wp_enqueue_script("sies_edit_subscriber", plugins_url('list-subscriber.js',__FILE__));               
        }  
        
        if ($hook=='admin_page_edit-subscription') {
            wp_enqueue_style('jquery-style', plugins_url('jquery-ui/jquery-ui.css',__FILE__));
            wp_enqueue_style("siesstyle",plugins_url('sies-admin-style.css',__FILE__));
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script("sies_edit_subscriber", plugins_url('edit-subscriber.js',__FILE__));            
        }                           
        
    }
    add_action( 'admin_enqueue_scripts', 'enqueue_admin_js_css' );
                