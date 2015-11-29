<?php

/*
=====================================================
 Threaded Comments
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2016 Yuri Salimovskiy
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'threadedcomments/config.php';

class Threadedcomments_upd {

    var $version = THREADEDCOMMENTS_ADDON_VERSION;
    
    function __construct() { 

    } 
    
    function install() { 
        
        ee()->load->dbforge(); 
        
        //----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if (ee()->db->field_exists('settings', 'modules') == FALSE)
		{
			ee()->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}
        
 	 	
		if (ee()->db->field_exists('parent_id', 'comments') == FALSE)
		{
			ee()->dbforge->add_column('comments', array('parent_id' => array('type' => 'INT', 'default' => '0') ) );
		}
        if (ee()->db->field_exists('root_id', 'comments') == FALSE)
		{
			ee()->dbforge->add_column('comments', array('root_id' => array('type' => 'INT', 'default' => '0') ) );
		}
        if (ee()->db->field_exists('level', 'comments') == FALSE)
		{
			ee()->dbforge->add_column('comments', array('level' => array('type' => 'INT', 'default' => '0') ) );
		}
        
        $fields = array(
			'subscription_id'	=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'entry_id'			=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE),
			'member_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
            'thread_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
			'email'				=> array('type' => 'varchar', 'constraint' => '75'),
			'subscription_date'	=> array('type' => 'varchar', 'constraint' => '10'),
			'notification_sent'	=> array('type' => 'char'	, 'constraint' => '1', 'default' => 'n'),
			'hash'				=> array('type' => 'varchar', 'constraint' => '15')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('subscription_id', TRUE);
		ee()->dbforge->add_key(array('entry_id', 'member_id', 'thread_id'));
		ee()->dbforge->create_table('threadedcomments_subscriptions');
        
        
        $settings = array();
        $data = array( 'module_name' => 'Threadedcomments' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'settings'=> serialize($settings) ); 
        ee()->db->insert('modules', $data); 
        
        $data = array(
			'class'		=> 'Threadedcomments' ,
			'method'	=> 'delete_thread_notification'
		);

		ee()->db->insert('actions', $data);
        
        return TRUE; 
        
    } 
    
    function uninstall() 
    { 
        
        ee()->load->dbforge(); 
        
        ee()->db->select('module_id'); 
        $query = ee()->db->get_where('modules', array('module_name' => 'Threadedcomments')); 
        
        ee()->db->where('module_id', $query->row('module_id')); 
        ee()->db->delete('module_member_groups'); 
        
        ee()->db->where('module_name', 'Threadedcomments'); 
        ee()->db->delete('modules'); 
        
        ee()->db->where('class', 'Threadedcomments'); 
        ee()->db->delete('actions'); 
        
        ee()->dbforge->drop_table('threadedcomments_subscriptions');
        
        return TRUE; 
    } 
    
    function update($current='') 
    { 
		if (version_compare($current, '3.0.0', '<'))
        {
            $data = array('has_cp_backend' => 'y'); 
            ee()->db->where('module_name', 'Threadedcomments'); 
            ee()->db->update('modules', $data);
            
            
            $fields = array(
    			'subscription_id'	=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
    			'entry_id'			=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE),
    			'member_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
                'thread_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
    			'email'				=> array('type' => 'varchar', 'constraint' => '75'),
    			'subscription_date'	=> array('type' => 'varchar', 'constraint' => '10'),
    			'notification_sent'	=> array('type' => 'char'	, 'constraint' => '1', 'default' => 'n'),
    			'hash'				=> array('type' => 'varchar', 'constraint' => '15')
    		);
    
    		ee()->dbforge->add_field($fields);
    		ee()->dbforge->add_key('subscription_id', TRUE);
    		ee()->dbforge->add_key(array('entry_id', 'member_id', 'thread_id'));
    		ee()->dbforge->create_table('threadedcomments_subscriptions');
            
            $data = array(
    			'class'		=> 'Threadedcomments' ,
    			'method'	=> 'delete_thread_notification'
    		);
    
    		ee()->db->insert('actions', $data);
        } 
        return TRUE; 
    } 
	

}
/* END */
?>