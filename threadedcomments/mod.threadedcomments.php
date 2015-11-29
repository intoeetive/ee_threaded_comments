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


class Threadedcomments {

    var $return_data	= ''; 		
    
    var $settings 		= array();

    
    

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
        ee()->lang->loadfile('comment');  
        ee()->lang->loadfile('threadedcomments');
    }
    
    
   	public function delete_thread_notification()
	{
		if ( ! $id = ee()->input->get_post('id') OR
			 ! $hash = ee()->input->get_post('hash'))
		{
			return FALSE;
		}

		if ( ! is_numeric($id))
		{
			return FALSE;
		}

		ee()->lang->loadfile('comment');

		ee()->load->library('subscription');
		ee()->subscription->init('threadedcomments', array('subscription_id' => $id), TRUE);
		ee()->subscription->unsubscribe('', $hash);

		$data = array(
			'title' 	=> lang('cmt_notification_removal'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('cmt_you_have_been_removed'),
			'redirect'	=> '',
			'link'		=> array(
				ee()->config->item('site_url'),
				stripslashes(ee()->config->item('site_name'))
			)
		);

		ee()->output->show_message($data);
	}


}
/* END */
?>