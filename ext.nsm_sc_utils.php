<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NSM Simple Commerce Utilities Addon Extension
 *
 * @package NsmSCUtils
 * @version 0.0.1
 * @author Leevi Graham <http://newism.com.au>
 * @copyright Copyright (c) 2007-2009 Newism
 * @license Commercial - please see LICENSE file included with this distribution
 * @see http://expressionengine.com/public_beta/docs/development/extensions.html
 *
 **/
class Nsm_sc_utils_ext
{
	var $settings			= array();
	var $name				= 'NSM Simple Commerce Utilities';
	var $version			= '0.0.1';
	var $description		= 'Extension for NSM Simple Commerce Utilities';
	var $settings_exist		= 'n';
	var $docs_url			= '';

	var $hooks = array('channel_entries_tagdata', 'channel_entries_query_result');

	var $default_site_settings = array();
	var $default_channel_settings = array();
	var $default_member_group_settings = array();

	// ====================================
	// = Delegate & Constructor Functions =
	// ====================================

	/**
	 * PHP5 constructor function.
	 * @since		Version 0.0.0
	 * @access		public
	 * @param		array	$settings	an array of settings used to construct a new instance of this class.
	 * @return		void
	 **/
	function __construct($settings=FALSE)
	{
		// define a constant for the current site_id rather than calling $PREFS->ini() all the time
		if (defined('SITE_ID') == FALSE)
			define('SITE_ID', get_instance()->config->item('site_id'));
		
	}

	function activate_extension(){$this->_createHooks();}
	function disable_extension(){$this->_deleteHooks();}
	function update_extension(){}
	function settings_form(){}

	// ===============================
	// = Hook Functions =
	// ===============================

	/**
	 * Load simple commerce item data into channel entires
	 * 
	 * If the row is also a SCM item a new array key "nsm_sc_utils:items" 
	 * will contain an array of items and their unmodified attributes pulled from the db.
	 * 
	 * [nsm_sc_utils:items] => Array
	 *    (
	 *      [0] => Array
	 *        (
	 *          [item_id] => 1
	 *          [entry_id] => 120
	 *          [item_enabled] => y
	 *          [item_regular_price] => 35.00
	 *          [item_sale_price] => 25.00
	 *          [item_use_sale] => y
	 *          [recurring] => n
	 *          [subscription_frequency] => 
	 *          [subscription_frequency_unit] => day
	 *          [item_purchases] => 0
	 *          [current_subscriptions] => 0
	 *          [new_member_group] => 0
	 *          [member_group_unsubscribe] => 0
	 *          [admin_email_address] => sales@expressionengine-addons.com
	 *          [admin_email_template] => 0
	 *          [customer_email_template] => 0
	 *          [admin_email_template_unsubscribe] => 0
	 *          [customer_email_template_unsubscribe] => 0
	 *        )
	 *    )
	 * )
	 * 
	 * @access public
	 * @param $Channel The current Channel object including all data relating to categories and custom fields
	 * @param $query_result array Entries for the current tag
	 * 
	 * @return array The modified query result
	 * @see http://expressionengine.com/public_beta/docs/development/extension_hooks/module/channel/index.html
	 */
	public function channel_entries_query_result($Channel, $query_result){

		$this->EE =& get_instance();

		if($this->EE->extensions->last_call != FALSE)
			$query_result = $this->EE->extensions->last_call;

		$entry_ids = FALSE;
		foreach ($query_result as $entry) {
			$entry_ids[] = $entry["entry_id"];
		}

		// LOAD SIMPLE COMMERCE ITEM DATA
		if($this->EE->TMPL->fetch_param("nsm_sc_utils:load_item_data") == "yes")
		{
			$item_query = $this->EE->db
					->from("exp_simple_commerce_items")
					->where_in('exp_simple_commerce_items.entry_id', $entry_ids)
					->get();

			foreach ($item_query->result_array() as $item)
			{
				foreach ($query_result as &$entry)
				{
					if($item["entry_id"] == $entry["entry_id"])
					{
						$entry["nsm_sc_utils:items"][] = $item;
					}
				}
			}
		}
		return $query_result;
	}

	/**
	 * Modify the tagdata for the channel entries before anything else is parsed
	 * 
	 * Called for each entry loop in the exp:channel:entries tag. Implements the
	 * following tags if the entry is an SCM item:
	 * 
	 *  {nsm_sc_utils:item:item_id}
	 *  {nsm_sc_utils:item:entry_id}
	 *  {nsm_sc_utils:item:item_enabled}
	 *  {nsm_sc_utils:item:item_regular_price}
	 *  {nsm_sc_utils:item:item_sale_price}
	 *  {nsm_sc_utils:item:item_use_sale}
	 *  {nsm_sc_utils:item:recurring}
	 *  {nsm_sc_utils:item:subscription_frequency}
	 *  {nsm_sc_utils:item:subscription_frequency_unit}
	 *  {nsm_sc_utils:item:item_purchases}
	 *  {nsm_sc_utils:item:current_subscriptions}
	 *  {nsm_sc_utils:item:new_member_group}
	 *  {nsm_sc_utils:item:member_group_unsubscribe}
	 *  {nsm_sc_utils:item:admin_email_address}
	 *  {nsm_sc_utils:item:admin_email_template}
	 *  {nsm_sc_utils:item:customer_email_template}
	 *  {nsm_sc_utils:item:admin_email_template_unsubscribe}
	 *  {nsm_sc_utils:item:customer_email_template_unsubscribe}
	 * 
	 * @access public
	 * @param $tagdata string The Channel Entries tag data
	 * @param $row array Array of data for the current entry
	 * @param Channel The current Channel object including all data relating to categories and custom fields
	 * @return string The modified tagdata
	 * @see http://expressionengine.com/public_beta/docs/development/extension_hooks/module/channel/index.html
	 */
	public function channel_entries_tagdata($tagdata, $row, $Channel){

		$this->EE =& get_instance();

		if($this->EE->extensions->last_call != FALSE)
			$tagdata = $this->EE->extensions->last_call;

		if($this->EE->TMPL->fetch_param("nsm_sc_utils:load_item_data") == "yes")
		{
			// The simple commerce item
			$vars = (isset($row["nsm_sc_utils:items"])) ? $row["nsm_sc_utils:items"] : array($this->_scItemCols());
			$vars = $this->_prefixArrayKeys($vars, "nsm_sc_utils:item");
			$tagdata = $this->EE->TMPL->parse_variables($tagdata, $vars);
		}

		return $tagdata;
	}

	// ===============================
	// = Class and Private Functions =
	// ===============================

	/**
	 * Load the Simple Commerce columns and save them in the cache
	 */
	private function _scItemCols(){
		$this->EE =& get_instance();
		$col_query = $this->EE->db->query("SHOW COLUMNS FROM exp_simple_commerce_items;");
		$cols = array();
		foreach ($col_query->result_array() as $row)
		{
			$cols[$row["Field"]] = FALSE;
		}
		return $cols;
	}

	/**
	 * Prefix array keys
	 * 
	 * @access public
	 * @param $data array The data array
	 * @param $prefix string The string to use as the prefix
	 * @param $divider string The prefix / key glue
	 * @return array The modified array
	 */
	private function _prefixArrayKeys($data, $prefix, $glue = ":")
	{
		if(!$prefix) return $data;
		
		foreach ($data as $key => $value)
		{
			if(is_array($value))
			{
				if(!is_numeric($key))
				{
					$data[$prefix.$glue.$key] = $this->_prefixArrayKeys($value, $prefix, $divider);
					unset($data[$key]);
				}
				else
				{
					$data[$key] = $this->_prefixArrayKeys($value, $prefix);
				}
			}
			else
			{
				$data[$prefix.$glue.$key] = $value;
				unset($data[$key]);
			}
		}
		return $data;
	}

	/**
	 * Sets up and subscribes to the hooks specified by the $hooks array.
	 * @since		Version 0.0.0
	 * @access		private
	 * @param		array	$hooks	a flat array containing the names of any hooks that this extension subscribes to. By default, this parameter is set to FALSE.
	 * @return		void
	 * @see 		http://codeigniter.com/user_guide/general/hooks.html
	 **/
	private function _createHooks($hooks = FALSE)
	{
		$EE =& get_instance();

		if (!$hooks)
			$hooks = $this->hooks;

		$hook_template = array(
			'class'    => __CLASS__,
			'settings' => FALSE,
			'version'  => $this->version,
		);

		foreach ($hooks as $key => $hook)
		{
			if (is_array($hook))
			{
				$data['hook'] = $key;
				$data['method'] = (isset($hook['method']) === TRUE) ? $hook['method'] : $key;
				$data = array_merge($data, $hook);
			}
			else
			{
				$data['hook'] = $data['method'] = $hook;
			}

			$hook = array_merge($hook_template, $data);
			$hook['settings'] = serialize($hook['settings']);
			$EE->db->insert('exp_extensions', $hook);
		}
	}

	/**
	 * Removes all subscribed hooks for the current extension.
	 * 
	 * @since		Version 0.0.0
	 * @access		private
	 * @return		void
	 * @see 		http://codeigniter.com/user_guide/general/hooks.html
	 **/
	private function _deleteHooks()
	{
		$EE =& get_instance();
		$EE->db->where('class', __CLASS__);
		$EE->db->delete('exp_extensions'); 
	}
}