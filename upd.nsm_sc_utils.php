<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Install / Uninstall and update NSM Simple Commerce Utils module
 *
 * @package NSMSCUtils
 * @author Leevi Graham
 **/
class Nsm_sc_utils_upd
{
	/**
	 * The module version
	 *
	 * @var string
	 */
	public $version = '0.0.1';
	
	/**
	 * The name of the module
	 *
	 * @var string
	 */
	public $module_name = 'NSM Simple Commerce Utils';
	
	/**
	 * Does this module have a control panel?
	 *
	 * @var boolean
	 */
	public $has_cp_backend = FALSE;

	/**
	 * Does this module have publish fields?
	 *
	 * @var boolean
	 */
	public $has_publish_fields = FALSE;
	
	/**
	 * Does this module have tabs?
	 *
	 * @var boolean
	 */
	public $has_tabs = FALSE;

	/**
	 * Actions
	 *
	 * @var array key = method, value = class
	 */
	private $actions = array(
		"Nsm_sc_utils::update_purchase_notes"
	);

	/**
	 * Constructor
	 */
	public function __construct() 
	{ 
	}    

	/**
	 * Installs the module
	 * 
	 * Installs the module, adding a record to the exp_modules table, creates and populates and necessary database tables, adds any necessary records to the exp_actions table, and if custom tabs are to be used, adds those fields to any saved publish layouts
	 *
	 * @return boolean
	 * @author Leevi Graham
	 **/
	public function install()
	{
		$this->EE =& get_instance();
		$data = array(
			'module_name' => substr(__CLASS__, 0, -4),
			'module_version' => $this->version,
			'has_cp_backend' => ($this->has_cp_backend) ? "y" : "n",
			'has_publish_fields' => ($this->has_publish_fields) ? "y" : "n"
		);
		$this->EE->db->insert('modules', $data);

		if(isset($this->actions) && is_array($this->actions))
		{
			foreach ($this->actions as $action)
			{
				$parts = explode("::", $action);
				$this->EE->db->insert('actions', array(
					"class" => $parts[0],
					"method" => $parts[1]
				));
			}
		}

		if(isset($this->has_publish_fields) &&  $this->has_publish_fields)
			$this->EE->cp->add_layout_tabs($this->tabs, $module_name);

		// add a note field
		$query	= $this->EE->db->query( "DESCRIBE `exp_simple_commerce_purchases` `note`" );
		if ( $query->num_rows == 0 )
		{
			$this->EE->load->dbforge();
			$fields = array(
				'note' => array('type' => 'VARCHAR', 'constraint' => '255')
			);
			$this->EE->dbforge->add_column('simple_commerce_purchases', $fields);
		}

		return TRUE;
	}

	/**
	 * Updates the module
	 * 
	 * This function is checked on any visit to the module's control panel, and compares the current version number in the file to the recorded version in the database. This allows you to easily make database or other changes as new versions of the module come out.
	 *
	 * @return Boolean FALSE if no update is nessecary, TRUE if it is.
	 * @author Leevi Graham
	 **/
	public function update($current = FALSE)
	{
		return FALSE;
	}

	/**
	 * Uninstalls the module
	 *
	 * @return Boolean FALSE if uninstall failed, TRUE if it was successful
	 * @author Leevi Graham
	 **/
	public function uninstall()
	{
		$this->EE =& get_instance();
		$module_name = substr(__CLASS__, 0, -4);
		$module_id_query = $this->EE->db->from('modules')
										->select('module_id')
										->where(array('module_name' => $module_name))
										->get();

		if($module_id_query->num_rows() > 0)
		{
			$this->EE->db->delete('module_member_groups', array('module_id' => $module_id_query->row('module_id')));
			$this->EE->db->delete('modules', array('module_name' => $module_name));
		}

		if(isset($this->actions) && is_array($this->actions))
			$this->EE->db
				->where('class', $module_name)
				->or_where('class', $module_name . "_mcp")
				->delete('actions');

		if(isset($this->has_publish_fields) && $this->has_publish_fields)
			$this->EE->cp->delete_layout_tabs($this->tabs(), $module_name);

		// $this->EE->load->dbforge();
		// $this->EE->dbforge->drop_column('simple_commerce_purchases', 'notes');

		return TRUE;
	}
}