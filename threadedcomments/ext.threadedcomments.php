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

class Threadedcomments_ext {

	var $name	     	= THREADEDCOMMENTS_ADDON_NAME;
	var $version 		= THREADEDCOMMENTS_ADDON_VERSION;
	var $description	= '';
	var $settings_exist	= 'n';
    
    var $settings 		= array();
    var $site_id		= 1;
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->settings = $settings;
	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        ee()->load->dbforge(); 
        
        $hooks = array(

            array(
    			'hook'		=> 'comment_form_hidden_fields',
    			'method'	=> 'modify_hidden_fields',
    			'priority'	=> 10
    		),
            array(
    			'hook'		=> 'comment_form_tagdata',
    			'method'	=> 'modify_form_tagdata',
    			'priority'	=> 10
    		),
            array(
    			'hook'		=> 'insert_comment_end',
    			'method'	=> 'insert_thread_variables',
    			'priority'	=> 10
    		),
            /*
            //this one is called from within other hook
            array(
    			'hook'		=> 'insert_comment_end',
    			'method'	=> 'subscribe_and_notify',
    			'priority'	=> 20 //lowest priority
    		),
            */
            
            array(
    			'hook'		=> 'comment_entries_comment_ids_query',
    			'method'	=> 'modify_initial_comments_query',
    			'priority'	=> 10
    		),
            array(
    			'hook'		=> 'comment_entries_query_result',
    			'method'	=> 'modify_comment_results',
    			'priority'	=> 10
    		),
            array(
    			'hook'		=> 'comment_entries_tagdata',
    			'method'	=> 'build_threads',
    			'priority'	=> 8
    		),
            
            
            
            array(
    			'hook'		=> 'insert_comment_end',
    			'method'	=> 'insert_comment_data',
    			'priority'	=> 8
    		),
            /*
    		array(
    			'hook'		=> 'comment_entries_tagdata',
    			'method'	=> 'parse_custom_fields',
    			'priority'	=> 10
    		),
            */
    		array(
    			'hook'		=> 'comment_form_end',
    			'method'	=> 'set_enctype',
    			'priority'	=> 10
    		),
    		array(
    			'hook'		=> 'comment_form_tagdata',
    			'method'	=> 'display_form_fields',
    			'priority'	=> 10
    		)
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            ee()->db->insert('extensions', $data);
    	}	
    	
    	//exp_comment_fields
        $fields = array(
			'field_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'site_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'field_type'		=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => ''),
			'field_name'		=> array('type' => 'VARCHAR',	'constraint'=> 250,	'default' => ''),
			'field_label'		=> array('type' => 'VARCHAR',	'constraint'=> 250,	'default' => ''),
			
			'field_related_to'	=> array('type' => 'VARCHAR',	'constraint'=> 12,	'default' => 'channel'),
			'field_related_id'	=> array('type' => 'INT',	'unsigned' => TRUE),
			'field_related_orderby'	=> array('type' => 'VARCHAR',	'constraint'=> 12,	'default' => 'date'),
			'field_related_sort'	=> array('type' => 'VARCHAR',	'constraint'=> 4,	'default' => 'desc'),
			'field_related_max'		=> array('type' => 'SMALLINT',	'constraint'=> 4),		
			
			'field_ta_rows'		=> array('type' => 'TINYINT',	'constraint'=> 2,	'default' => '8'),
			'field_maxl'		=> array('type' => 'SMALLINT',	'constraint'=> 3,	'null' => TRUE),
			'field_required'	=> array('type' => 'CHAR',		'constraint'=> 1,	'default' => 'n'),
			
			'field_text_direction'	=> array('type' => 'CHAR',		'constraint'=> 3,	'default' => 'ltr'),
			'field_fmt'			=> array('type' => 'VARCHAR',	'constraint'=> 40,	'default' => 'xhtml'),
			'field_show_fmt'	=> array('type' => 'CHAR',		'constraint'=> 1,	'default' => 'y'),
			
			'field_order'		=> array('type' => 'INT',		'unsigned' => TRUE,	'constraint'=> 3),
			'field_content_type'=> array('type' => 'VARCHAR',	'constraint'=> 20,	'default' => 'any'),
			
			'field_list_items'  => array('type' => 'TEXT'),
			'field_settings'    => array('type' => 'TEXT')
		);


		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('field_id', TRUE);
		ee()->dbforge->create_table('comment_fields', TRUE);
		
		//exp_comment_data
        $fields = array(
			'comment_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'entry_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'site_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('comment_id', TRUE);
		ee()->dbforge->create_table('comment_data', TRUE);
        
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	ee()->db->where('class', __CLASS__);
    	ee()->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	ee()->load->dbforge(); 
		
		ee()->db->where('class', __CLASS__);
    	ee()->db->delete('extensions');
    	
    	ee()->dbforge->drop_table('comment_fields');
        ee()->dbforge->drop_table('comment_data');
    }
    
    
    
    function settings()
    {
		$settings = array();
        
        
        return $settings;
    }
    
    
    public function modify_initial_comments_query($db)
    {
        //fetch only root comments
        $db->where('level', '0');
    }
    
    
    
    
    public function modify_comment_results($results_orig)
    {
        if (ee()->extensions->last_call !== FALSE && ee()->extensions->last_call !== NULL)
        {
              $results_orig = ee()->extensions->last_call;
        }
        
        //  Set sorting and limiting
        $sort = (ee()->TMPL->fetch_param('dynamic') == 'no')
			? ee()->TMPL->fetch_param('sort', 'desc')
			: ee()->TMPL->fetch_param('sort', 'asc');

		$allowed_sorts = array('date', 'email', 'location', 'name', 'url');

		$order_by = ee()->TMPL->fetch_param('orderby');
		$order_by = ($order_by == 'date' OR ! in_array($order_by, $allowed_sorts))  ? 'comment_date' : $order_by;
        $sort = strtolower($sort);
        
        $result_ids = array();

        foreach ($results_orig as $i => $row)
        {
            $result_ids[] = $row['comment_id'];
		}
		
        $select = 'comments.comment_id, comments.entry_id, comments.channel_id, comments.author_id, comments.name, comments.email, comments.url, comments.location AS c_location, comments.ip_address, comments.comment_date, comments.edit_date, comments.comment, comments.site_id AS comment_site_id,
            parent_id, root_id, level,
			members.username, members.group_id, ';
        if (version_compare(APP_VER, '4.0.0', '<'))
        {
            $select .= 'members.location, members.occupation, members.interests, members.aol_im, members.yahoo_im, members.msn_im, members.icq, ';
        }
        $select .= 'members.group_id, members.member_id, members.signature, members.sig_img_filename, members.sig_img_width, members.sig_img_height, members.avatar_filename, members.avatar_width, members.avatar_height, members.photo_filename, members.photo_width, members.photo_height,
			member_data.*,
			channel_titles.title, channel_titles.url_title, channel_titles.author_id AS entry_author_id, channel_titles.allow_comments, channel_titles.comment_expiration_date,
			channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls, channels.channel_url, channels.comment_url, channels.channel_title, channels.channel_name AS channel_short_name, channels.comment_system_enabled';
        ee()->db->select($select);

		ee()->db->join('channels',			'comments.channel_id = channels.channel_id',	'left');
		ee()->db->join('channel_titles',	'comments.entry_id = channel_titles.entry_id',	'left');
		ee()->db->join('members',			'members.member_id = comments.author_id',		'left');
		ee()->db->join('member_data',		'member_data.member_id = members.member_id',	'left');

		ee()->db->where_in('comments.root_id', $result_ids);
		ee()->db->order_by($order_by, $sort);

		$query = ee()->db->get('comments');
        if ($query->num_rows() > 0)
        {
			
			$results_orig = array_merge($results_orig, $query->result_array());
        }
        
        //for each of the root comments, fetch children
        $results = array();
        $parents = array();
        $max_levels = 1;
        foreach ($results_orig as $i => $row)
        {
            if (!isset($row['parent_id']))
            {
                $thread_data_q = ee()->db->select('parent_id, root_id, level')
                    ->from('comments')
                    ->where('comment_id', $row['comment_id'])
                    ->get();
                $row = array_merge($row, $thread_data_q->row_array());
            }
            
            $parents[$row['comment_id']] = $row['parent_id'];
            
            if ($row['level']>$max_levels)
            {
                $max_levels = $row['level'];
			}
			
			if (!isset($row['url_as_author']))
			{
				$row['url_as_author'] = $this->getAuthorUrl($row, $row['url']);
				$row['url_or_email'] = ($row['url']) ?: $row['email'];
				$row['url_or_email_as_author'] = $this->getAuthorUrl($row, $row['url'], TRUE);
				$row['url_or_email_as_link'] = $this->getAuthorUrl($row, $row['url'], TRUE, FALSE);
			}
            
            $results_orig[$i] = $row;
         }
         
         foreach ($results_orig as $i => $row)
         {  
            $idx = 'c';
            //give string indexation to array in order to perform custom sorting
            $current_parent = $parents[$row['comment_id']];
            $traverse = array();
            $traverse[] = $row['comment_id'];
            while ($current_parent!=0)
            {
                $traverse[] = $current_parent;
                $current_parent = $parents[$current_parent];
            }
            
            
            $traverse = array_reverse($traverse);
            for ($t=0; $t<=$max_levels; $t++)
            {
                if (!isset($traverse[$t])) $traverse[$t] = "0";
                $idx .= '_'.str_pad($traverse[$t], 8, "0", STR_PAD_LEFT);
            }
            
            $row['idx'] = $idx;
            
            $results[$idx] = $row;
        }

        //in case of random ordering, just return original array with extra data
        //(do not build threads)
        if (ee()->TMPL->fetch_param('orderby') == 'random')
        {
            return $results;
        }
        
        //do the sorting
        ksort($results, SORT_STRING);
        
        //define threads starts and ends
        $prev_comment = array(
            'idx'   => '',
            'level' => 0
        );
        $count = 0;
        $total_comments = count($results);
        
        //prepare array of custom fields
        $custom_fields_query = ee()->db->select('field_id, field_name')
								->from('comment_fields')
								->where('site_id', ee()->config->item('site_id'))
								->get();

        $fields = array();
        $empty_custom_fields_row = array();

		$custom_fields_data_sql_what = "comment_id";
		foreach ($custom_fields_query->result_array() as $custom_field)
        {
			$empty_custom_fields_row[$custom_field['field_name']] = '';
            $fields['field_id_'.$custom_field['field_id']] = $custom_field['field_name'];
			$custom_fields_data_sql_what .= ', field_id_'.$custom_field['field_id'].' AS '.$custom_field['field_name'];
        }
		

        foreach ($results as $idx => $row)
        {
            $count++;
            
            $row['comment_total'] = $total_comments;
            
      		ee()->db->select($custom_fields_data_sql_what);
    		ee()->db->from('comment_data');
    		ee()->db->where("comment_id", $row['comment_id']);
    		
            $custom_fields_data_query = ee()->db->get();
            
            if ($custom_fields_data_query->num_rows()==0)
            {
            	$row = array_merge($row, $empty_custom_fields_row);
                /*
                foreach ($fields as $field_tech=>$field_human)
                {
                	$row[$field_human] = '';
                }*/
            }
            else
            {
    	        
                $row = array_merge($row, $custom_fields_data_query->row_array());

                /*
                foreach ($fields as $field_tech=>$field_human)
                {
                	$vars[$field_human] = $row[$field_human];
                	if (strpos($vars[$field_human], '{filedir_') !== FALSE)
    				{
    					ee()->load->library('file_field');
    					$vars[$field_human] = ee()->file_field->parse_string($vars[$field_human]);
    				}
                }*/
    		}
            
            $row['thread_start'] = FALSE;
            $row['thread_end'] = FALSE;
            $row['closures_count'] = 0;
            
            if ($row['level'] > $prev_comment['level'])
            {
                $row['thread_start'] = TRUE;
            }
            
            if ($row['level'] < $prev_comment['level'])
            {
                $prev_comment['thread_end'] = TRUE;
                $prev_comment['closures_count'] = $prev_comment['level'] - $row['level'];
            }
            
            if ($prev_comment['idx']!='')
            {
                $prev_idx = $prev_comment['idx'];
                $results[$prev_idx] = $prev_comment;
            }
            
            $results[$idx] = $row;
            
            if ($count!=$total_comments)
            {
                $prev_comment = $row;
            }

        }
        
        //figure out closures for the last comment
        if ($row['level']!=0)
        {
            $results[$idx]['thread_end'] = TRUE;
            $results[$idx]['closures_count'] = $row['level'];
        }
        
        return $results;
        
    }
    
    
    public function build_threads($tagdata, $row)
    {
        
        if (ee()->extensions->last_call !== FALSE && ee()->extensions->last_call !== NULL)
        {
              $tagdata = ee()->extensions->last_call;
        }
        
        //add more closing tags if necessary
        if ($row['closures_count'] > 0)
        {
            if (strpos($tagdata, LD."if thread_end".RD) !== FALSE)
    		{
                preg_match('/'.LD.'if thread_end'.RD.'(.*)'.LD.'\/if'.RD.'/uis', $tagdata, $matches);
                $closure = $matches[1];
                for ($i=1; $i<$row['closures_count']; $i++)
                {
                    $closure .= $matches[1];
                }
    			$tagdata = preg_replace('/'.LD.'if thread_end'.RD.'(.*)'.LD.'\/if'.RD.'/uis', LD.'if thread_end'.RD.$closure.LD.'/if'.RD, $tagdata);
    		}
        }
        
        //parse new vars
        $tagdata = ee()->TMPL->parse_variables_row($tagdata, $row);

        return $tagdata;
    }
    
    
    public function modify_hidden_fields($hidden_fields)
    {
        if (ee()->extensions->last_call !== FALSE && ee()->extensions->last_call !== NULL)
        {
              $hidden_fields = ee()->extensions->last_call;
        }
        
        $hidden_fields['parent_id'] = (ee()->TMPL->fetch_param('parent_id')!='')?ee()->TMPL->fetch_param('parent_id'):'0';

        return $hidden_fields;
    }

    
    public function modify_form_tagdata($tagdata)
    {
        
        if (ee()->extensions->last_call !== FALSE)
        {
              $tagdata = ee()->extensions->last_call;
        }
        
		/** ----------------------------------------
		/**  parse {notify_thread}
		/** ----------------------------------------*/

		$checked = '';

		if ( ! isset($_POST['PRV']))
		{
			if (ee()->input->cookie('notify_me'))
			{
				$checked = ee()->input->cookie('notify_me');
			}

			if (isset(ee()->session->userdata['notify_by_default']))
			{
				$checked = (ee()->session->userdata['notify_by_default'] == 'y') ? 'yes' : '';
			}
		}

		if (isset($_POST['notify_thread']))
		{
			$checked = $_POST['notify_thread'];
		}

		$tagdata = ee()->TMPL->swap_var_single('notify_thread', ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
        
        return $tagdata;
    }
    
    
    public function insert_thread_variables($data, $comment_moderate, $comment_id)
    {
        if (ee()->input->post('parent_id')!='' && ee()->input->post('parent_id')!=0)
        {
            $parent_id = ee()->input->post('parent_id');
            $root_id = $parent_id;
            $level = 0;
            do {
                $level++;
                $q = ee()->db->select("parent_id")->from('comments')->where('comment_id', $root_id)->get();
                if ($q->row('parent_id')==0) break;
                $root_id = $q->row('parent_id');
            } while ($root_id!=0);
        }
        else
        {
            $parent_id = 0;
            $root_id = 0;
            $level = 0;
        }        
        
        $upd = array(
            'parent_id' => $parent_id,
            'root_id' => $root_id,
            'level' => $level
        );
        ee()->db->where('comment_id', $comment_id);
        ee()->db->update('comments', $upd);
        
        $data = array_merge($data, $upd);
        
        $this->subscribe_and_notify($data, $comment_moderate, $comment_id);
    }
    
    
    public function subscribe_and_notify($data, $comment_moderate, $comment_id)
    {
        
        if ($data['root_id']==0)
        { 
            return;
        }
        
        ee()->load->library('subscription');

        
        //subscribe the user to notifications
        if (ee()->input->post('notify_thread') == 'y' || ee()->input->post('notify_thread') == 'yes')
		{
			//... unless already subscribed on entry-level
            if ($data['email']!='')
            {
                ee()->subscription->init('comment', array('entry_id' => $data['entry_id'], 'email'  => $data['email']), TRUE);
    			$entry_subscription = ee()->subscription->get_subscriptions();
                if (!empty($entry_subscription))
                {
                    return;
                }
            }
            
            if ($data['author_id']!=0)
            {
                ee()->subscription->init('comment', array('entry_id' => $data['entry_id'], 'member_id'  => $data['author_id']), TRUE);
    			$entry_subscription = ee()->subscription->get_subscriptions();
                if (!empty($entry_subscription))
                {
                    return;
                }
            }
            
            
            ee()->subscription->init('threadedcomments', array('entry_id' => $data['entry_id'], 'thread_id' => $data['root_id']), TRUE);

			if ($data['author_id']!=0)
			{
				ee()->subscription->subscribe($data['author_id']);
			}
			else
			{
				ee()->subscription->subscribe($data['email']);
			}

		}

        if ($comment_moderate == 'n')
		{

			/** ----------------------------------------
			/**  Fetch email notification addresses
			/** ----------------------------------------*/

			$ignore = (ee()->session->userdata('member_id') != 0) ? ee()->session->userdata('member_id') : ee()->input->post('email');
            
			ee()->subscription->init('threadedcomments', array('entry_id' => $data['entry_id'], 'thread_id' => $data['root_id']), TRUE);
			$thread_subscriptions = ee()->subscription->get_subscriptions($ignore);
			$thread_recipients = ee()->comment_model->fetch_email_recipients($data['entry_id'], $thread_subscriptions);
            
            // remove those that already have received entry-level notification
            ee()->subscription->init('comment', array('entry_id' => $data['entry_id']), TRUE);
			$entry_subscriptions = ee()->subscription->get_subscriptions($ignore);
			$entry_recipients = ee()->comment_model->fetch_email_recipients($data['entry_id'], $entry_subscriptions);
            $entry_emails = array();
            foreach ($entry_recipients as $entry_recipient)
            {
                $entry_emails[] = $entry_recipient[0];
            }
            
            $email_msg = '';

			if (count($thread_recipients) > 0)
			{
				$action_id  = ee()->functions->fetch_action_id('Threadedcomments', 'delete_thread_notification');
                
                $query = ee()->db->select('channel_titles.title, channel_titles.url_title,
        						channels.channel_title, channels.comment_url, channels.channel_url'
  		            )
                    ->from('channel_titles')
                    ->join('channels', 'channel_titles.channel_id = channels.channel_id', 'left')
                    ->where('channel_titles.entry_id', $data['entry_id'])
                    ->get();
                
                $entry_title			= $query->row('title') ;
        		$url_title				= $query->row('url_title') ;
        		$channel_title		 	= $query->row('channel_title') ;
        		$comment_url			= $query->row('comment_url');
        		$channel_url			= $query->row('channel_url');
                
                $comment = ee()->typography->parse_type(
        			$data['comment'],
        			array(
        				'text_format'	=> 'none',
        				'html_format'	=> 'none',
        				'auto_links'	=> 'n',
        				'allow_img_url' => 'n'
        			)
        		);
                $path = ($comment_url == '') ? $channel_url : $comment_url;
                $comment_url_title_auto_path = reduce_double_slashes($path.'/'.$url_title);

				$swap = array(
					'name_of_commenter'	=> $data['name'],
					'channel_name'		=> $channel_title,
					'entry_title'		=> $entry_title,
					'site_name'			=> stripslashes(ee()->config->item('site_name')),
					'site_url'			=> ee()->config->item('site_url'),
					'comment_url'		=> reduce_double_slashes(ee()->input->remove_session_id(ee()->functions->fetch_site_index().'/'.$_POST['URI'])),
					'comment_id'		=> $comment_id,
					'comment'			=> $comment,
					'channel_id'		=> $data['channel_id'],
					'entry_id'			=> $data['entry_id'],
					'url_title'			=> $url_title,
					'comment_url_title_auto_path' => $comment_url_title_auto_path
				);


				$template = ee()->functions->fetch_email_template('comment_notification');
				$email_tit = ee()->functions->var_swap($template['title'], $swap);
				$email_msg = ee()->functions->var_swap($template['data'], $swap);

				/** ----------------------------
				/**  Send email
				/** ----------------------------*/

				ee()->load->library('email');
				ee()->email->wordwrap = true;

				$sent = array(
                    '0' => $data['email']
                );

				// Load the text helper
				ee()->load->helper('text');

				foreach ($thread_recipients as $val)
				{
					// We don't notify the person currently commenting.  That would be silly.

					if ( ! in_array($val['0'], $sent) AND  ! in_array($val['0'], $entry_emails))
					{
						$title	 = $email_tit;
						$message = $email_msg;

						$sub	= $thread_subscriptions[$val['1']];
						$sub_qs	= 'id='.$sub['subscription_id'].'&hash='.$sub['hash'];

						// Deprecate the {name} variable at some point
						$title	 = str_replace('{name}', $val['2'], $title);
						$message = str_replace('{name}', $val['2'], $message);

						$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
						$message = str_replace('{name_of_recipient}', $val['2'], $message);

						$title	 = str_replace('{notification_removal_url}', ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $title);
						$message = str_replace('{notification_removal_url}', ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $message);

						ee()->email->EE_initialize();
						ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
						ee()->email->to($val['0']);
						ee()->email->subject($title);
						ee()->email->message(entities_to_ascii($message));
						ee()->email->send();

						$sent[] = $val['0'];
					}
				}
			}
		}
        
    }

    //Legacy!!!
    function insert_comment_data($data, $comment_moderate, $comment_id)
    {
        if (ee()->extensions->last_call !== FALSE)
        {
              $data = ee()->extensions->last_call;
        }
        
        //process custom fields, if any of them were submitted
		$custom_fields_query = ee()->db->select()
								->from('comment_fields')
								->where('site_id', ee()->config->item('site_id'))
								->get();	
		if ($custom_fields_query->num_rows()>0)
		{
			ee()->load->library('api');
			ee()->legacy_api->instantiate('channel_fields');
			ee()->legacy_api->instantiate('channel_entries');
			$cust_data = array();
			foreach ($custom_fields_query->result_array() as $custom_field)
			{
				if (isset($_POST['field_id_'.$custom_field['field_id']]) || isset($_POST[$custom_field['field_name']]))
				{
					if (isset($_POST[$custom_field['field_name']]))
					{
						$_POST['field_id_'.$custom_field['field_id']] = $_POST[$custom_field['field_name']];
					}
					if (isset($_POST[$custom_field['field_name'].'_existing']))
					{
						$_POST['field_id_'.$custom_field['field_id'].'_existing'] = $_POST[$custom_field['field_name'].'_existing'];
					}
					
					if (isset($_FILES[$custom_field['field_name']]))
					{
						$_FILES['field_id_'.$custom_field['field_id']] = $_FILES[$custom_field['field_name']];
					}
					
					if ($custom_field['field_type'] == 'multi_select' OR $custom_field['field_type'] == 'checkboxes')
					{
						ee()->api_channel_entries->_prep_multi_field($_POST, $custom_field);
					}
					
					if ($_POST['field_id_'.$custom_field['field_id']]=='') continue;

					ee()->api_channel_fields->include_handler($custom_field['field_type']);
					ee()->api_channel_fields->setup_handler($custom_field['field_type']);

					ee()->api_channel_fields->field_type = $custom_field['field_type'];
		
					ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type]->field_name = $custom_field['field_name'];
					
					ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type]->field_id = $custom_field['field_id'];
					
					ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type]->settings = array_merge(unserialize(base64_decode($custom_field['field_settings'])), $custom_field, ee()->api_channel_fields->get_global_settings(ee()->api_channel_fields->field_type));
				
					$valid = ee()->api_channel_fields->apply('validate', array('field_id_'.$custom_field['field_id'] => $_POST['field_id_'.$custom_field['field_id']]));
					$val = ee()->api_channel_fields->apply('save', array('field_id_'.$custom_field['field_id'] => $_POST['field_id_'.$custom_field['field_id']]));

					$cust_data['field_id_'.$custom_field['field_id']] = $val;
    
				}
			}
			$cust_data['comment_id'] = $comment_id;
			$cust_data['entry_id'] = $data['entry_id'];
			$cust_data['site_id'] = $data['site_id'];

			ee()->db->insert('comment_data', $cust_data);
            $data = array_merge($data, $cust_data);
		}
    	
        return $data;
        
    }
    
    
    function set_enctype($html)
    {
    	if (ee()->extensions->last_call !== FALSE)
        {
              $html = ee()->extensions->last_call;
        }
        
        $custom_fields_query = ee()->db->select('field_id')
								->from('comment_fields')
								->where_in('field_type', array('file', 'safecracker_file'))
								->where('site_id', ee()->config->item('site_id'))
								->get();
		if ($custom_fields_query->num_rows() > 0)
		{
			$html = str_replace('<form', '<form enctype="multipart/form-data"', $html);
		}
		return $html;
    }
    
    
    
    function display_form_fields($tagdata)
    {
    	if (ee()->extensions->last_call !== FALSE)
        {
              $tagdata = ee()->extensions->last_call;
        }
        
        $custom_fields_query = ee()->db->select()
								->from('comment_fields')
								->where('site_id', ee()->config->item('site_id'))
								->get();	
		if ($custom_fields_query->num_rows()>0)
		{
			ee()->load->library('api');
			ee()->load->helper('form');
			ee()->router->set_class('cp');
			ee()->load->library('cp');
			ee()->router->set_class('ee');
			ee()->load->library('javascript');
			ee()->legacy_api->instantiate('channel_fields');
			$vars = array();
			foreach ($custom_fields_query->result_array() as $custom_field)
			{
				ee()->api_channel_fields->include_handler($custom_field['field_type']);
				ee()->api_channel_fields->setup_handler($custom_field['field_type']);
				
				ee()->api_channel_fields->field_type = $custom_field['field_type'];
		
				ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type]->field_name = $custom_field['field_name'];
				
				ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type]->field_id = $custom_field['field_id'];
				
				ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type]->settings = array_merge(unserialize(base64_decode($custom_field['field_settings'])), $custom_field, ee()->api_channel_fields->get_global_settings(ee()->api_channel_fields->field_type));
		
				//ee()->api_channel_fields->get_settings($custom_field['field_id']);
				$vars[$custom_field['field_name']] = ee()->api_channel_fields->apply('display_field', array('data' => ''));
                
                if ($custom_field['field_type']=='file')
                {
                    $tagdata .= "			<style type=\"text/css\">
			.file_set {
				color: #5F6C74;
				font-family: Helvetica, Arial, sans-serif;
				font-size: 12px;
				position: relative;
                display: none;
			}
			.filename {
				border: 1px solid #B6C0C2;
				position: relative;
				padding: 5px;
				text-align: center;
				float: left;
				margin: 0 0 5px;
			}
			.undo_remove {
				color: #5F6C74;
				font-family: Helvetica, Arial, sans-serif;
				font-size: 12px;
				text-decoration: underline;
				display: block;
				padding: 0;
				margin: 0 0 8px;
			}
			.filename img {
				display: block;
			}
			.filename p {
				padding: 0;
				margin: 4px 0 0;
			}
			.remove_file {
				position: absolute;
				top: -6px;
				left: -6px;
				z-index: 5;
			}
			.clear {
				clear: both;
			}
            .file_upload>.sub_filename
            {
                display: none;
            }
			</style>";
                    $tagdata .= "<script type=\"text/javascript\">
			$(document).ready(function() {
				function setupFileField(container) {
					var last_value = [],
						fileselector = container.find('.no_file'),
						hidden_name = container.find('input[name*=\"_hidden_file\"]').prop('name'),
						placeholder;

					if ( ! hidden_name) {
						return;
					}

					remove = $('<input/>', {
						'type': 'hidden',
						'value': '',
						'name': hidden_name.replace('_hidden_file', '')
					});

					container.find(\".remove_file\").click(function() {
						container.find(\"input[type=hidden][name*='hidden']\").val(function(i, current_value) {
							last_value[i] = current_value;
							return '';
						});
						container.find(\".file_set\").hide();
						container.find('.sub_filename a').show();
						fileselector.show();
						container.append(remove);

						return false;
					});

					container.find('.undo_remove').click(function() {
						container.find(\"input[type=hidden]\").val(function(i) {
							return last_value.length ? last_value[i] : '';
						});
						container.find(\".file_set\").show();
						container.find('.sub_filename a').hide();
						fileselector.hide();
						remove.remove();

						return false;
					});
				}
				// most of them
				$('.file_field').not('.grid_field .file_field').each(function() {
					setupFileField($(this));
				});
			});                    
                    </script>";
                }
                
			}
			$tagdata = ee()->TMPL->parse_variables_row($tagdata, $vars);
		}
		
		return $tagdata;
		
    }

	/**
	 * Get Author URLs
	 *
	 * @param  string  $url The URL to use
	 * @param  boolean $fallback_to_email Whether to fallback to email if the URL is empty
	 * @param  boolean $use_name_in_link  Whether to use the user's name as the visible part of the link or just the URL/Email
	 * @return string parsed author URL variable
	 */
	private function getAuthorUrl($comment, $url, $fallback_to_email = FALSE, $use_name_in_link = TRUE)
	{
		if ($url)
		{
			$label = ($use_name_in_link) ? $comment['name'] : $url;
			return '<a href="'.$url.'">'.$label.'</a>';
		}
		elseif ($fallback_to_email && $comment['email'])
		{
			$label = ($use_name_in_link) ? $comment['name'] : $comment['email'];
			return ee()->typography->encode_email($comment['email'], $label);
		}

		return $comment['name'];
	}

}
// END CLASS
