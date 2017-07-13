<?php
namespace FreePBX\modules\Filestore\drivers\Email;
class Email{
	public function __construct($freepbx=null){
		$this->FreePBX = $freepbx;
		if(!empty($freepbx)){
			$this->db =  $freepbx->Database;
		}
		$this->connections = array();
		$this->dbcols = array(
			"id" => '',
			"name" => '',
			"desc" => '',
			"addr" => array(),
			"maxsize" => '',
			"maxtype" => '',
			"from" => '',
			"body" => ''
		);
	}
	//Base actions
	/**
	 * Run on install/update
	 * @return null
	 */
	public function install(){
	}

	/**
	 * Run on module uninstall
	 * @return null
	 */
	 public function uninstall(){
	}

	public function generateId(){
		return \Ramsey\Uuid\Uuid::uuid4()->toString();
	}

	/**
	 * The display view for non setting items.
	 * @return string html
	 */
	public function displayView(){
		if(empty($_GET['view'])){
			return load_view(__DIR__.'/views/grid.php');
		}else{
			$vars = array();
			if(isset($_GET['id'])){
				$vars = $this->getItemById($_GET['id']);
			}
			return load_view(__DIR__.'/views/form.php',$vars);
		}
	}
	public function getActionBar($request) {
		if(!isset($_GET['view']) || $_GET['view'] != 'form'){
			return array();
		}
		switch($request['driver']) {
			case 'Email':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['id'])) {
					unset($buttons['delete']);
				}
			break;
		}
		return $buttons;
	}
	public function ajaxHandler(){
		$req = isset($_REQUEST['command'])?$_REQUEST['command']:'';
		switch ($req) {
			case 'getJSON':
				 return $this->listItems();
			default:
				return false;
		}
	}
	public function ajaxRequest($req){
		switch ($req) {
			case 'getJSON':
				return true;
		}
		return false;
	}

	/**
	 * Process post data
	 * @param  [type] $page [description]
	 * @return [type]       [description]
	 */
	public function doConfigPageInit($page){
		$req = $_REQUEST;
		if($req['driver'] == 'Email'){
			$action = isset($req['action'])?$req['action']:'';
			$id = isset($req['id'])?$req['id']:false;
			switch ($action) {
				case 'add':
				case 'save':
					return $this->addItem($_POST);
				break;
				case 'edit':
					if($id){
						return $this->editItem($id, $req);
					}
					return array('status' => false, 'message' => _("No id supplied"));
				break;
				case 'delete':
					if($id){
						return $this->deleteItem($id);
					}
					return array('status' => false, 'message' => _("No id supplied"));
				break;
				default:
					return array('status' => false, 'message' => _("Unknown action provided"));
				break;
			}
		}
	}

	/**
	 * Weather an implintation is supported in this driver
	 * @param  string $method the method "all,backup,readonly,writeonly"
	 * @return bool method is/not supported
	 */
	public function methodSupported($method){
		$permissions = array(
			'all',
			'write',
			'backup',
		);
		return in_array($method, $permissions);
	}

	//CRUD actions
	/**
	 * Add a item for driver
	 * @param $data array of data for required
	 */
	public function addItem($data){
		$id = (isset($data['id']) && !empty($data['id']))?$data['id']:$this->generateId();
		foreach ($this->dbcols as $key => $val) {
			switch ($key) {
				case 'id':
					continue;
				case 'addr':
					$value = isset($data[$key])?$data[$key]:$val;
					$tmp = preg_split("/\\r\\n|\\r|\\n/", $value);
					$tmp = array_filter($tmp,trim);
					$this->FreePBX->Filestore->setConfig($key,$tmp,$id);
				break;
				default:
					$value = isset($data[$key])?$data[$key]:$val;
					$this->FreePBX->Filestore->setConfig($key,$value,$id);
				break;
			}
		}
		$description = isset($data['desc'])?$data['desc']:$this->dbcols['desc'];
		$this->FreePBX->Filestore->setConfig($id,array('id' => $id, 'name' => $data['name'], 'desc' => $description),'emailservers');
		return array('status' => $ret, 'data' => $id);
	}
	/**
	 * Edit Item
	 * @param  string  $id   Id of item
	 * @param  array $data array of data required
	 * @return string id
	 */
	public function editItem($data){
		$this->addItem($data);
	}

	/**
	 * Delete Item by id
	 * @param  string $id Id of item
	 * @return bool   success, failure
	 */
	public function deleteItem($id){
		$this->FreePBX->Filestore->setConfig($id,false,'emailservers');
		$this->FreePBX->Filestore->delById($id);
	}

	public function getItemById($id){
		$data = $this->FreePBX->Filestore->getAll($id);
		$return = array();
		foreach ($this->dbcols as $key => $value) {
			switch ($key) {
				default:
					$return[$key] = isset($data[$key])?$data[$key]:$value;
				break;
			}
		}
		return $return;
	}

	/**
	 * Get list of items for driver
	 * @return array Array of items.
	 */
	public function listItems(){
		$items = $this->FreePBX->Filestore->getAll('emailservers');
		return array_values($items);
	}

	//Filestore Actions

	/**
	 * Put file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param file $file to upload
	 * @return bool  object created
	 */
	public function put($id,$local,$remote){
		$item = $this->getItemById($id);
		$from = isset($item['from'])?$item['from']:get_current_user().'@'.gethostname();
		if(isset($item['from']) && !empty($item['from'])){
			$from = $item['from'];
		}else{
			$from = get_current_user() . '@' . gethostname();
			if(function_exists('sysadmin_get_storage_email')){
				$emails = sysadmin_get_storage_email();
				//Check that what we got back above is a email address
				if(!empty($emails['fromemail']) && filter_var($emails['fromemail'],FILTER_VALIDATE_EMAIL)){
					//Fallback address
					$from = $emails['fromemail'];
				}
			}
		};
		$to = array_filter($item['addr'],trim);
		$brand = $this->FreePBX->Config->get("DASHBOARD_FREEPBX_BRAND");
		$ident = $this->FreePBX->Config->get("FREEPBX_SYSTEM_IDENT");
		$body = !empty($item['body'])?$item['body']:sprintf(_("File from %s, Identifier: %s"),$brand,$ident);
		$mail = \FreePBX::Mail();
		$mail->setSubject($item['desc']);
		$mail->setFrom($from,$from);
		$mail->setTo($to);
		$mail->setBody($body);
		$mail->addAttachment($local);
		return $mail->send();
	}
}
