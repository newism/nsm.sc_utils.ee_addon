<?php

error_reporting(E_ALL);

if (! defined('BASEPATH')) die('No direct script access allowed');


/**
 * NSM Files Module Class
 */
class Nsm_sc_utils {

	public $EE;
	protected $cache = array();

	private $form_data = FALSE;
	private $form_params = array();

	function __construct(){
	}

	public function update_notes_form(){

		$this->EE =& get_instance();

		if(!class_exists("NSM_Form"))
			include("libraries/NSM/Form.php");

		$form = new NSM_Form();

		return $form->build(
			$this->EE->TMPL->tagdata,
			"Nsm_sc_utils::update_purchase_notes",
			array(
				"secure" => array("entry_id" => 10),
				"hidden" => array()
			)
		);
	}

	public function update_purchase_notes(){

		$this->EE =& get_instance();

		if(!class_exists("NSM_Form"))
			include("libraries/NSM/Form.php");

		$form = new NSM_Form();
		$form->processSubmitStart();

		if($notes = $this->EE->input->post("nsm_sc_utils_note"))
			foreach ($notes as $purchase_id => $note)
				$this->EE->db->update('simple_commerce_purchases', array("note" => $note), array("purchase_id" => $purchase_id));

		$form->processSubmitEnd();
	}

	/**
	 * Render purchase data for current user
	*/
	
	public function purchases()
	{
		$EE =& get_instance();

		if(!$member_id = $EE->TMPL->fetch_param("member_id"))
			$member_id = $EE->session->userdata['member_id'];

		$query = $EE->db->query("SELECT p.purchase_id as purchase_id,
			p.member_id as member_id,
			p.purchase_id as purchase_id,
			p.item_id as item_id,
			p.txn_id as txn_id,
			p.purchase_date as purchase_date,
			p.note as purchase_note,
			p.item_cost as item_cost,
			t.title as title,
			t.url_title as url_title,
			t.entry_id as entry_id
		FROM `exp_simple_commerce_purchases` as p
		LEFT JOIN `exp_simple_commerce_items` as i ON p.item_id = i.item_id
		LEFT JOIN `exp_channel_titles` as t ON i.entry_id = t.entry_id
		LEFT JOIN `exp_members` as m ON p.member_id = m.member_id
		LEFT JOIN `exp_channel_data` as d ON i.entry_id = d.entry_id
		WHERE p.member_id = '{$member_id}'
		ORDER BY p.purchase_id DESC");

		if($query->num_rows == 0)
		{
			return $EE->TMPL->parse_variables_row($EE->TMPL->tagdata, array("no_purchases" => TRUE));
		}
		else
		{
			$result = $query->result_array();
			foreach ($result as $key => $value)
			{
				$result[$key]["license_key"] = sprintf('%03d', $value["item_id"])
					. sprintf('%05d', $value["member_id"])
					. sprintf('%05d', $value["purchase_id"])
					. sprintf('%02d', 1)
					. $value["purchase_date"];
			}
			return $EE->TMPL->parse_variables($EE->TMPL->tagdata, $result);
		}
	}

	/**
	 * Parse a PDT response from PayPal
	 */
	public function pdt_response()
	{
		$this->EE =& get_instance();

		if(!isset($this->EE->session->cache[__CLASS__]))
			$this->EE->session->cache[__CLASS__] = array("PDT_responses"=>array());

		// No auth token, get out
		if(!$auth_token = $this->EE->TMPL->fetch_param("auth_token"))
			return;

		// no transaction token, get out
		if(!$tx_token = $this->EE->input->get("tx"))
			return;

		// No data in the cache?
		if(!isset($this->EE->session->cache[__CLASS__]["PDT_responses"][$tx_token]))
		{
			// set the environment
			if($environment = $this->EE->TMPL->fetch_param("environment"))
				$environment = "live";

			// build the url
			$url = ($environment == "sandbox") ? "www.sandbox.paypal.com" : "www.sandbox.paypal.com";
			$req = "cmd=_notify-synch&tx={$tx_token}&at={$auth_token}";

			// set the headers
			$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

			// GO!
			if (!$fp = fsockopen ($url, 80, $errno, $errstr, 30))
				return;

			fputs ($fp, $header . $req);
			$res = '';
			$row = array();
			$header_parsed = FALSE;

			// Parse the response
			while (!feof($fp))
			{
				$line = fgets ($fp);
				if (strcmp($line, "\r\n") == 0)
				{
					$header_parsed = TRUE;
				}
				else if ($header_parsed)
				{
					$res .= $line;
				}
			}

			// Build the array of data
			$lines = explode("\n", $res);
			$row["response"] = trim($lines[0]);

			if (strcmp ($lines[0], "SUCCESS") == 0)
			{
				for ($i=0; $i < count($lines); $i++)
				{ 
					$parts = explode("=", $lines[$i]);
					$row[urldecode($parts[0])] = ((isset($parts[1])) ? urldecode($parts[1]) : FALSE);
				}
			
			}
			else
			{
				$row["error_status"] = $lines[1];
			}

			$this->EE->session->cache[__CLASS__]["PDT_responses"][$tx_token] = $row;
		}

		// Load the data from the cache
		$row = $this->EE->session->cache[__CLASS__]["PDT_responses"][$tx_token];

		// Now return the template data
		if(!$this->EE->TMPL->fetch_param("return_GA_purchase_script"))
			return $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $row);

		// Or return the GA code
		return ($row["response"] == "SUCCESS") ? $this->EE->load->view('../third_party/nsm_sc_utils/views/pdt_ga_script', $row, TRUE) : FALSE;

	}
}