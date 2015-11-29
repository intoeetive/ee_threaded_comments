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
use EllisLab\ExpressionEngine\Library\CP\Table;

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'threadedcomments/config.php';


class Threadedcomments_mcp {

    var $version = THREADEDCOMMENTS_ADDON_VERSION;

    
    function __construct() { 
        ee()->lang->loadfile('admin_content');  
        ee()->lang->loadfile('comment');  
        ee()->lang->loadfile('threadedcomments'); 
        
        $sidebar = ee('CP/Sidebar')->make();
        $fields_menu = $sidebar->addHeader(lang('custom_fields'), ee('CP/URL', 'addons/settings/threadedcomments/comment_fields'))->withButton(lang('new'), ee('CP/URL', 'addons/settings/threadedcomments/edit_comment_field'));
        $fields_menu->isActive();
        
        ee()->view->header = array(
			'title' => lang('threadedcomments_module_name')
		);   
    } 
    
    
    function index()
    {
    	return $this->comment_fields();
    }

    
    function comment_fields()
    {
        if ( ! ee()->cp->allowed_group(
			'can_access_admin',
			'can_admin_channels',
			'can_access_content_prefs'
		))
		{
			show_error(lang('unauthorized_access'));
		}

    	$data = array();
        
        $query = ee()->db->from('comment_fields')->where('site_id', ee()->config->item('site_id'))->get();
        
        $table = ee('CP/Table');
        
        $table->setColumns(
          array(
            'id',
            'field_label',
            'field_name',
            'field_type',
            'manage' => array(
              'type'  => Table::COL_TOOLBAR
            )
          )
        );
        
        $table->setNoResultsText('no_custom_fields', 'create', ee('CP/URL', 'addons/settings/threadedcomments/edit_comment_field'));
					
		   
		$i = 0;
		
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');
		$fts = ee()->api_channel_fields->fetch_installed_fieldtypes();
		
        foreach ($query->result_array() as $row)
        {
           $data[$i]['id'] = $row['field_id'];
           $data[$i]['label'] = $row['field_label'];
           $data[$i]['name'] = $row['field_name'];
           $data[$i]['type'] = $fts[$row['field_type']]['name']; 
           $data[$i]['manage']['toolbar_items']['edit'] = array(
                'href' => ee('CP/URL', 'addons/settings/threadedcomments/edit_comment_field/'.$row['field_id']),
                'title' => lang('edit')
              );
           $data[$i]['manage']['toolbar_items']['remove'] = array(
                'href' => ee('CP/URL', 'addons/settings/threadedcomments/delete_comment_field/'.$row['field_id']),
                'title' => lang('delete'),
                'class' => 'm-link',
                'rel'   => 'modal-confirm-remove-'.$row['field_id']
              );
              
              
           $modal_vars = array(
            	'name'      => 'modal-confirm-remove-'.$row['field_id'],
            	'form_url'	=> ee('CP/URL')->make('addons/settings/threadedcomments/delete_comment_field'),
                'checklist' => array(
            		array(
            			'kind' => lang('field'),
            			'desc' => $row['field_name'],
            		)
            	),
            	'hidden'	=> array(
                    'field_id'     => $row['field_id']
            	)
            );
            
            $modal = ee('View')->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
            ee('CP/Modal')->addModal('remove-'.$row['field_id'], $modal);
              
           $i++;
 			
        }
        
        $vars = array(
            'base_url'      => ee('CP/URL', 'addons/settings/threadedcomments/edit_comment_field'),
            'cp_page_title' => lang('comment_fields'),
            'save_btn_text' => lang('save'),
            'save_btn_text_working' => lang('btn_saving')
        );
        
        $table->setData($data);
        
        $vars['table'] = $table->viewData();
        
        return array(
          'body'       => ee('View')->make('threadedcomments:comment_fields')->render($vars),
          'breadcrumb' => array(
            ee('CP/URL', 'addons/settings/threadedcomments/comment_fields')->compile() => lang('threadedcomments_module_name')
          ),
          'heading'  => lang('comment_fields'),
        );
	
    }
    
    
    function edit_comment_field()
    {
    	
        if ( ! ee()->cp->allowed_group(
			'can_access_admin',
			'can_admin_channels',
			'can_access_content_prefs'
		))
		{
			show_error(lang('unauthorized_access'));
		}
        
        if (ee()->input->post('field_type')!==FALSE)
        {
    		if (strlen(ee()->input->post('field_name')) > 32)
    		{
                ee('CP/Alert')->makeStandard('threadedcomments')
                          ->asWarning()
                          ->withTitle(lang('error'))
                          ->addToBody(lang('field_name_too_lrg'))
                          ->defer();
                          
                ee()->functions->redirect(ee('CP/URL', 'addons/settings/threadedcomments/comment_fields')->compile());
    		}
            
            ee()->load->library('api');
    		ee()->legacy_api->instantiate('channel_fields');
    
    		// If the $field_id variable has data we are editing an
    		// existing group, otherwise we are creating a new one
    
    		$edit = ( ! isset($_POST['field_id']) OR $_POST['field_id'] == '') ? FALSE : TRUE;
    
    		// We need this as a variable as we'll unset the array index
    
    		$group_id = ee()->input->post('group_id');
    
    		//perform the field update
    		$this->_update_field($_POST);
    
    		// Are there errors to display?
    
    		if (!empty($this->errors))
    		{
    			$str = '';
    
    			foreach ($this->errors as $msg)
    			{
    				$str .= $msg.BR;
    			}
                
                ee('CP/Alert')->makeStandard('threadedcomments')
                          ->asWarning()
                          ->withTitle(lang('error'))
                          ->addToBody($str)
                          ->defer();
    		}
            else
            {
                $cp_message = ($edit) ? lang('custom_field_edited') : lang('custom_field_created');

                ee('CP/Alert')->makeStandard('threadedcomments')
                              ->asSuccess()
                              ->withTitle(lang('success'))
                              ->addToBody($cp_message)
                              ->defer();
            }

            ee()->functions->redirect(ee('CP/URL', 'addons/settings/threadedcomments/comment_fields')->compile());

        }
		
		ee()->load->helper('form');
    	ee()->load->library('table');
		
		$field_id = (int)ee()->uri->segment(6);
		
		$field_edit_vars = $this->_field_edit_vars($field_id);
		
		foreach ($field_edit_vars['field_type_options'] as $ft_option=>$ft_name)
		{
			if (!in_array($ft_option, array('text', 'textarea')))//, 'safecracker_file', 'file')))
			{
				unset($field_edit_vars['field_type_options'][$ft_option]);
			}
		}
		//unset($vars['field_type_options']['rel']);
		
		if ($field_edit_vars === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}
		
    	$current_data = array(
			'field_id'		=> '',
			'field_type'	=> 'text',
			'field_name'	=> '',
			'field_label'	=> '',
			'field_list_items'=>'',
			'field_settings'=>''
		);

    	if ($field_id!=0)
    	{
    		$q = ee()->db->from('comment_fields')->where('field_id', $field_id)->get();
    		$current_data['field_id'] 		= $q->row('field_id');
    		$current_data['field_type'] 	= $q->row('field_type');
    		$current_data['field_name'] 	= $q->row('field_name');
    		$current_data['field_label']	= $q->row('field_label');
    		$current_data['field_list_items']= $q->row('field_list_items');
    		$current_data['field_settings'] = $q->row('field_settings');
    	}
		
		
        $vars['sections'] = array(
          array(
            array(
              'title' => '',
              'fields' => array(
                'site_id' => array(
                  'type' => 'hidden',
                  'value' => ee()->config->item('site_id')
                ),
                'field_id' => array(
                  'type' => 'hidden',
                  'value' => $current_data['field_id']
                )
              )
            ),
            array(
              'title' => 'field_type',
              'fields' => array(
                'field_type' => array(
                  'type'    => 'select',
                  'choices' => $field_edit_vars['field_type_options'],
                  'value'   => $current_data['field_type'],
                  'required' => TRUE
                )
              )
            ),
            array(
              'title' => 'field_label',
              'fields' => array(
                'field_label' => array(
                  'type'    => 'text',
                  'value'   => $current_data['field_label'],
                  'required' => TRUE
                )
              )
            ),
            array(
              'title' => 'field_name',
              'fields' => array(
                'field_name' => array(
                  'type'    => 'text',
                  'value'   => $current_data['field_name'],
                  'required' => TRUE
                )
              )
            ),
            
          
      

          )
        );
        
        // Final view variables we need to render the form
        $vars += array(
          'base_url' => ee('CP/URL', 'addons/settings/threadedcomments/edit_comment_field'),
          'cp_page_title' => lang('comment_fields'),
          'save_btn_text' => sprintf(lang('btn_save'), lang('field')),
          'save_btn_text_working' => lang('btn_saving')
        );
        
		
		if ($field_id==0) 
		{
			ee()->cp->add_js_script('plugin', 'ee_url_title');

			ee()->javascript->output('
				$("#field_label").bind("keyup keydown", function() {
					$(this).ee_url_title("#field_name");
				});
			');
		}
		
		$ft_selector = "#ft_".implode(", #ft_", array_keys($field_edit_vars['field_type_options']));

		ee()->javascript->output('
			var ft_divs = $("'.$ft_selector.'"),
				ft_dropdown = $("#field_type");
		
			ft_dropdown.change(function() {
				ft_divs.hide();
				$("#ft_"+this.value)
					.show()
					.trigger("activate")
					.find("table").trigger("applyWidgets");

			});
			
			ft_dropdown.trigger("change");
		');

		ee()->javascript->compile();
		
		return array(
          'body'       => ee('View')->make('threadedcomments:edit_comment_field')->render($vars),
          'breadcrumb' => array(
            ee('CP/URL', 'addons/settings/threadedcomments/comment_fields')->compile() => lang('threadedcomments_module_name')
          ),
          'heading'  => lang('comment_fields'),
        );

    }
    
    

    
    
    
    
    
    
    
    function delete_comment_field()
    {
    	$field_id = ee()->input->post('field_id');
		
		if ($field_id == '' OR ! is_numeric($field_id))
		{
			show_error(lang('not_authorized'));
		}

		ee()->db->where('field_id', $field_id);
		ee()->db->from('comment_fields');
		ee()->db->delete();
		
		ee()->load->dbforge(); 
		ee()->dbforge->drop_column('comment_data', 'field_id_'.$field_id);
        
        ee('CP/Alert')->makeStandard('threadedcomments')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(lang('field_deleted'))
                    ->defer();

        ee()->functions->redirect(ee('CP/URL', 'addons/settings/threadedcomments/comment_fields')->compile());
    }
    
    
    
    
    
    function _field_edit_vars($field_id=FALSE)
    {
		$this->errors = array();
		
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');
		
		ee()->load->library('table');

		ee()->load->model('field_model');
		
		$vars = array(
			'field_id' => $field_id,
		);
		
		ee()->db->select('*');
		ee()->db->from('comment_fields');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->where('field_id', $vars['field_id']);
		
		$field_query = ee()->db->get();
		
		if ($field_id == '')
		{
			$type = 'new';

			foreach ($field_query->list_fields() as $f)
			{
				if ( ! isset($vars[$f]))
				{
					$vars[$f] = '';
				}
			}

			$vars['field_order'] = ee()->db->count_all('comment_fields') + 1;

		}
		else
		{
			$type = 'edit';
			
			// No valid edit id?  No access
			if ($field_query->num_rows() == 0)
			{
				show_error(lang('unauthorized_access'));
				
				return FALSE;
			}

			foreach ($field_query->row_array() as $key => $val)
			{
				if ($key == 'field_settings' && $val)
				{
					$ft_settings = unserialize(base64_decode($val));
					$vars = array_merge($vars, $ft_settings);
				}
				else
				{
					$vars[$key] = $val;
				}
			}
			
			$vars['update_formatting']	= FALSE;
		}
		
		extract($vars);
		
		$vars['submit_lang_key']	= ($type == 'new') ? 'submit' : 'update';


		$vars['field_pre_populate_id_options'] = array();

		$vars['field_pre_populate_id_select'] = '';

		// build list of formatting options
		if ($type == 'new')
		{
			$vars['edit_format_link'] = '';
			
			ee()->load->model('addons_model');
			
			$vars['field_fmt_options'] = ee()->addons_model->get_plugin_formatting(TRUE);
            /*
		}
		else
		{
			$confirm = "onclick=\"if( !confirm('".lang('list_edit_warning')."')) return false;\"";
			$vars['edit_format_link'] = '<strong><a '.$confirm.' href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_formatting_options'.AMP.'id='.$field_id.'" title="'.lang('edit_list').'">'.lang('edit_list').'</a></strong>';

			ee()->db->select('field_fmt');
			ee()->db->where('field_id', $field_id);
			ee()->db->order_by('field_fmt');
			$query = ee()->db->get('field_formatting');
            
            $vars['field_fmt_options'] = array();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$name = ucwords(str_replace('_', ' ', $row['field_fmt']));
				
					if ($name == 'Br')
					{
						$name = lang('auto_br');
					}
					elseif ($name == 'Xhtml')
					{
						$name = lang('xhtml');
					}
					$vars['field_fmt_options'][$row['field_fmt']] = $name;
				}
			}
            */
		}

		$vars['field_fmt'] = (isset($field_fmt) && $field_fmt != '') ? $field_fmt : 'none';

		// Prep our own fields
		
		$fts = ee()->api_channel_fields->fetch_installed_fieldtypes();
		
		$default_values = array(
			'field_type'					=> isset($fts['text']) ? 'text' : key($fts),
			'field_show_fmt'				=> 'n',
			'field_required'				=> 'n',
			'field_search'					=> 'n',
			'field_is_hidden'				=> 'n',
			'field_pre_populate'			=> 'n',
			'field_show_spellcheck'			=> 'n',
			'field_show_smileys'			=> 'n',
			'field_show_glossary'			=> 'n',
			'field_show_formatting_btns'	=> 'n',
			'field_show_writemode'			=> 'n',
			'field_show_file_selector'		=> 'n',
			'field_text_direction'			=> 'ltr'
		);

		foreach($default_values as $key => $val)
		{
			$vars[$key] = ( ! isset($vars[$key]) OR $vars[$key] == '') ? $val : $vars[$key];
		}
		
		foreach(array('field_pre_populate', 'field_required', 'field_search', 'field_show_fmt') as $key)
		{
			$current = ($vars[$key] == 'y') ? 'y' : 'n';
			$other = ($current == 'y') ? 'n' : 'y';
			
			$vars[$key.'_'.$current] = TRUE;
			$vars[$key.'_'.$other] = FALSE;
		}
		
		// Text Direction
		$current = $vars['field_text_direction'];
		$other = ($current == 'rtl') ? 'ltr' : 'rtl';
		
		$vars['field_text_direction_'.$current] = TRUE;
		$vars['field_text_direction_'.$other] = FALSE;
		
		// Grab Field Type Settings
		
		$vars['field_type_table']	= array();
		$vars['field_type_options']	= array();

		$created = FALSE;

		foreach($fts as $key => $attr)
		{
			// Global settings
			$settings = unserialize(base64_decode($fts[$key]['settings']));
			
			$settings['field_type'] = $key;
			
			ee()->table->clear();
			
			ee()->api_channel_fields->set_settings($key, $settings);
			ee()->api_channel_fields->setup_handler($key);
			
			$str = ee()->api_channel_fields->apply('display_settings', array($vars));

			$vars['field_type_tables'][$key]	= $str;
			$vars['field_type_options'][$key]	= $attr['name'];
			
			if (count(ee()->table->rows))
			{
				$vars['field_type_tables'][$key] = ee()->table->rows;
			}
		}

		asort($vars['field_type_options']);	// sort by title

		$vars['form_hidden'] = array(
			'field_id'		=> $field_id,
			'site_id'		=> ee()->config->item('site_id')
		);

		$vars['ft_selector'] = "#ft_".implode(", #ft_", array_keys($fts));
		
		return $vars;
	}
	
	
	
	function _update_field(array $field_data)
	{
		$this->errors = array();
		
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');
		
		ee()->load->helper('array');

		// If the $field_id variable has data we are editing an
		// existing group, otherwise we are creating a new one

		$edit = ( ! isset($field_data['field_id']) OR $field_data['field_id'] == '') ? FALSE : TRUE;

		// Check for required fields

		$error = array();
		ee()->load->model('field_model');

		// little check in case they switched sites in MSM after leaving a window open.
		// otherwise the landing page will be extremely confusing
		if ( ! isset($field_data['site_id']) OR $field_data['site_id'] != ee()->config->item('site_id'))
		{
			show_error(lang('site_id_mismatch'));
		}

		// Was a field name supplied?
		if ($field_data['field_name'] == '')
		{
			show_error(lang('no_field_name'));
		}
		// Is the field one of the reserved words?
		else if (in_array($field_data['field_name'], ee()->cp->invalid_custom_field_names()))
		{
			show_error(lang('reserved_word'));
		}

		// Was a field label supplied?
		if ($field_data['field_label'] == '')
		{
			show_error(lang('no_field_label'));
		}

		// Does field name contain invalid characters?
		if (preg_match('/[^a-z0-9\_\-]/i', $field_data['field_name']))
		{
			$this->errors[] = lang('invalid_characters').': '.$field_data['field_name'];
		}

		// Is the field name taken?
		ee()->db->where(array(
			'site_id' => ee()->config->item('site_id'),
			'field_name' => element('field_name', $field_data),
		));

		if ($edit == TRUE)
		{
			ee()->db->where('field_id !=', element('field_id', $field_data));
		}

		if (ee()->db->count_all_results('channel_fields') > 0)
		{
			show_error(lang('duplicate_field_name'));
		}

		$field_type = $field_data['field_type'];

		// If they are setting a file type, ensure there is at least one upload directory available
		if ($field_type == 'file')
		{
			ee()->load->model('file_upload_preferences_model');
			$upload_dir_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences();
			
			// count upload dirs
			if (count($upload_dir_prefs) === 0)
			{
				ee()->lang->loadfile('filemanager');
				show_error(lang('please_add_upload'));
			}
		}

		// Are there errors to display?

		if (count($this->errors) > 0)
		{
			return FALSE;
		}
		
		$native = array(
			'field_id', 'site_id',
			'field_name', 'field_label',
			'field_type', 'field_list_items',
			'field_related_id', 'field_related_orderby', 'field_related_sort', 'field_related_max',
			'field_ta_rows', 'field_maxl', 'field_required',
			'field_order',
			'field_text_direction', 'field_show_fmt', 'field_fmt'
		);
		
		
		
		$_posted = array();
		$_field_posted = preg_grep('/^'.$field_type.'_.*/', array_keys($field_data));
		$_keys = array_merge($native,  $_field_posted);

		foreach($_keys as $key)
		{
			if (isset($field_data[$key]))
			{
				$_posted[$key] = $field_data[$key];
			}
		}

		// Get the field type settings
		ee()->api_channel_fields->fetch_all_fieldtypes();
		ee()->api_channel_fields->setup_handler($field_type);
		$ft_settings = ee()->api_channel_fields->apply('save_settings', array($_posted));
		
		// Default display options
		foreach(array('smileys', 'glossary', 'spellcheck', 'formatting_btns', 'file_selector', 'writemode') as $key)
		{
			$tmp = $this->_get_ft_data($field_type, 'field_show_'.$key, $field_data);
			$ft_settings['field_show_'.$key] = $tmp ? $tmp : 'n';
		}
		
		// Now that they've had a chance to mess with the POST array,
		// grab post values for the native fields (and check namespaced fields)
		foreach($native as $key)
		{
			$native_settings[$key] = $this->_get_ft_data($field_type, $key, $field_data);
		}
		
		// Set some defaults
		$native_settings['field_related_id']		= ($tmp = $this->_get_ft_data($field_type, 'field_related_channel_id', $field_data)) ? $tmp : '0';
		$native_settings['field_list_items']		= ($tmp = $this->_get_ft_data($field_type, 'field_list_items', $field_data)) ? $tmp : '';
				
		$native_settings['field_text_direction']	= ($native_settings['field_text_direction'] !== FALSE) ? $native_settings['field_text_direction'] : 'ltr';
		$native_settings['field_show_fmt']			= ($native_settings['field_show_fmt'] !== FALSE) ? $native_settings['field_show_fmt'] : 'n';
		$native_settings['field_fmt']				= ($native_settings['field_fmt'] !== FALSE) ? $native_settings['field_fmt'] : 'xhtml';

		
		// If they returned a native field value as part of their settings instead of changing the post array,
		// we'll merge those changes into our native settings
		
		foreach($ft_settings as $key => $val)
		{
			if (in_array($key, $native))
			{
				unset($ft_settings[$key]);
				$native_settings[$key] = $val;
			}
		}

		if (!isset($field_data['field_order']) OR $field_data['field_order'] == 0 OR $field_data['field_order'] == '')
		{
			$query = ee()->db->select('MAX(field_order) as max')
					      ->where('site_id', ee()->config->item('site_id'))
					      ->get('comment_fields');
				
			$native_settings['field_order'] = (int) $query->row('max') + 1;
		}
		
		$native_settings['field_settings'] = base64_encode(serialize($ft_settings));
		
		// Construct the query based on whether we are updating or inserting
		if ($edit === TRUE)
		{
			if ( ! is_numeric($native_settings['field_id']))
			{
				return FALSE;
			}

			// Update the formatting for all existing entries
			/*if ($this->_get_ft_data($field_type, 'update_formatting', $field_data) == 'y')
			{
				ee()->db->update(
					'comment_data',
					array('field_ft_'.$native_settings['field_id'] => $native_settings['field_fmt'])
				);
			}*/

				
			// Send it over to drop old fields, add new ones, and modify as needed
			$this->edit_datatype(
				$native_settings['field_id'],
				$field_type,
				$native_settings
			);

			ee()->db->where('field_id', $native_settings['field_id']);
			ee()->db->update('comment_fields', $native_settings);

			// Update saved layouts if necessary
			//$collapse = ($native_settings['field_is_hidden'] == 'y') ? TRUE : FALSE;
			$buttons = ($ft_settings['field_show_formatting_btns'] == 'y') ? TRUE : FALSE;

		}
		else
		{
			if ( ! $native_settings['field_ta_rows'])
			{
				$native_settings['field_ta_rows'] = 0;
			}

			// as its new, there will be no field id, unset it to prevent an empty string from attempting to pass
			unset($native_settings['field_id']);

			ee()->db->insert('comment_fields', $native_settings);

			$insert_id = ee()->db->insert_id();
			$native_settings['field_id'] = $insert_id;

			$this->add_datatype(
				$insert_id, 
				$native_settings
			);
/*
			ee()->db->update('comment_data', array('field_ft_'.$insert_id => $native_settings['field_fmt'])); 

			$field_formatting = array('none', 'br', 'xhtml');
			
			//if the selected field formatting is not one of the native formats, make sure it gets added to exp_field_formatting for this field
			if ( ! in_array($native_settings['field_fmt'], $field_formatting))
			{
				$field_formatting[] = $native_settings['field_fmt'];
			}

			foreach ($field_formatting as $val)
			{
				$f_data = array('field_id' => $insert_id, 'field_fmt' => $val);
				ee()->db->insert('field_formatting', $f_data); 
			}
			
			$collapse = ($native_settings['field_is_hidden'] == 'y') ? TRUE : FALSE;*/
			$buttons = ($ft_settings['field_show_formatting_btns'] == 'y') ? TRUE : FALSE;
			
			$field_info['publish'][$insert_id] = array(
								'visible'		=> 'true',
								'htmlbuttons'	=> $buttons,
								'width'			=> '100%'
			);
			
		}
		
		$_final_settings = array_merge($native_settings, $ft_settings);
		unset($_final_settings['field_settings']);
		
		ee()->api_channel_fields->set_settings($native_settings['field_id'], $_final_settings);
		ee()->api_channel_fields->setup_handler($native_settings['field_id']);
		ee()->api_channel_fields->apply('post_save_settings', array($_posted));

		ee()->functions->clear_caching('all', '', TRUE);
		
		return $native_settings['field_id'];
	}
	
	
	
	function edit_datatype($field_id, $field_type, $data)
	{
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');
		
		$old_fields = array();
		
		// First we get the data
		$query = ee()->db->get_where('comment_fields', array('field_id' => $field_id));
		
		ee()->api_channel_fields->setup_handler($query->row('field_type'));
		
		// Field type changed ?
		$type = ($query->row('field_type') == $field_type) ? 'get_data' : 'delete';

		$old_data = $query->row_array();
		
		// merge in a few variables to the data array
		$old_data['field_id'] = $field_id;
		$old_data['ee_action'] = $type;

		$old_fields = ee()->api_channel_fields->apply('settings_modify_column', array($old_data));

		// Switch handler back to the new field type
		ee()->api_channel_fields->setup_handler($field_type);

		if ( ! isset($old_fields['field_id_'.$field_id]))
		{
			$old_fields['field_id_'.$field_id]['type'] = 'text';
			$old_fields['field_id_'.$field_id]['null'] = TRUE;
		}
		/*
		if ( ! isset($old_fields['field_ft_'.$field_id]))
		{
			$old_fields['field_ft_'.$field_id]['type'] = 'tinytext';
			$old_fields['field_ft_'.$field_id]['null'] = TRUE;
		}
		*/
		// Delete extra fields
		if ($type == 'delete')
		{
			ee()->load->dbforge();
			$delete_fields = array_keys($old_fields);
				
			foreach ($delete_fields as $col)
			{
				if ($col == 'field_id_'.$field_id OR $col == 'field_ft_'.$field_id)
				{
					continue;
				}

				ee()->dbforge->drop_column('comment_data', $col);
			}
			
		}
		
		$type_change = ($type == 'delete') ? TRUE : FALSE;
		
		$this->set_datatype($field_id, $data, $old_fields, FALSE, $type_change);
	}	
	
	
	
	function set_datatype($field_id, $data, $old_fields = array(), $new = TRUE, $type_change = FALSE)
	{		
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');
		
		ee()->load->dbforge();
		
		// merge in a few variables to the data array
		$data['field_id'] = $field_id;
		$data['ee_action'] = 'add';
		
		// We have to get the new fields regardless to check whether they were modified
		$fields = ee()->api_channel_fields->apply('settings_modify_column', array($data));
		
		if ( ! isset($fields['field_id_'.$field_id]))
		{
			$fields['field_id_'.$field_id]['type'] = 'text';
			$fields['field_id_'.$field_id]['null'] = TRUE;
		}
		/*
		if ( ! isset($fields['field_ft_'.$field_id]))
		{
			$fields['field_ft_'.$field_id]['type'] = 'tinytext';
			$fields['field_ft_'.$field_id]['null'] = TRUE;
		}
		*/
		// Do we need to modify the field_id
		$modify = FALSE;

		if ( ! $new)
		{
			$diff1 = array_diff_assoc($old_fields['field_id_'.$field_id], $fields['field_id_'.$field_id]);
			$diff2 = array_diff_assoc($fields['field_id_'.$field_id], $old_fields['field_id_'.$field_id]);
		
			if ( ! empty($diff1) OR ! empty($diff2))
			{
				$modify = TRUE;
			}
		}

		// Add any new fields
		if ($type_change == TRUE OR $new == TRUE)
		{
			foreach ($fields as $field => $prefs)
			{
				if ( ! $new)
				{
					if ($field == 'field_id_'.$field_id OR $field == 'field_ft_'.$field_id)
					{
						continue;
					}
				}
				
				ee()->dbforge->add_column('comment_data', array($field => $prefs));
				
				// Make sure the value is an empty string
				ee()->db->update(
					'comment_data',
					array(
						$field => (isset($prefs['default'])) ? $prefs['default'] : ''
					)
				);
			}
		}
		
		// And modify any necessary fields
		if ($modify == TRUE)
		{
			$mod['field_id_'.$field_id] = $fields['field_id_'.$field_id];
			$mod['field_id_'.$field_id]['name'] = 'field_id_'.$field_id;
			
			ee()->dbforge->modify_column('comment_data', $mod);
		}
	}
	
	
	
	protected function _get_ft_data($field_type, $key, $field_data)
	{
		if (isset($field_data[$key]))
		{
			return $field_data[$key];
		}
		
		$key = $field_type.'_'.$key;
		
		return (isset($field_data[$key])) ? $field_data[$key] : FALSE;
	}
	
	
	
	
	function add_datatype($field_id, $data)
	{
		$this->set_datatype($field_id, $data, array(), TRUE);
	}
	


}
/* END */
?>