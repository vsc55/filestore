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
			"addr" => '',
			"maxsize" => '',
			"maxtype" => '',
		);
	}
	//Base actions
	/**
	 * Run on install/update
	 * @return null
	 */
	public function install(){
		//If you add it here add it to dbcols above//
		$cols = array(
			"id" => array(
				"type" => "integer",
				"primaryKey" => true,
				"autoincrement" => true
			),
			"name" => array(
				"type" => "string",
				"notnull" => true,
			),
			"desc" => array(
				"type" => "string",
				"length" => 150
			),
			"addr" => array(
				"type" => "string",
				"length" => 256
			),
			"maxsize" => array(
				"type" => "integer",
			),
			"maxtype" => array(
				"type" => "string",
				"length" => 150
			)
		);
		$table = $this->db->migrate("filestore_email");
		$table->modify($cols);
	}

	/**
	 * Run on module uninstall
	 * @return null
	 */
	public function uninstall(){
		$sql = "DROP TABLE filestore_email";
		$stmt = $this->db->prepare($sql);
		return $stmt->execute;
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
			case 'FTP':
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
				$data = $this->listItems();
				return $data['rows'];
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
		if($req['driver'] == 'FTP'){
			dbug('HERE');
			$action = isset($req['action'])?$req['action']:'';
			$id = isset($req['id'])?$req['id']:false;
			switch ($action) {
				case 'add':
				case 'save':
					return $this->addItem($req);
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
		$insert = array();
		$keys = array();
		$vars = array();
		foreach ($this->dbcols as $key => $value) {
			if($key == 'id'){
				continue;
			}
			$keys['`'.$key.'`'] = '`'.$key.'`';
			$insert[':'.$key] = isset($data[$key])?$data[$key]:$value;
		}
		$sql = 'INSERT INTO filestore_email ('.implode(',', array_keys($keys)).') VALUES ('.implode(',',array_keys($insert)).')';
		$stmt = $this->db->prepare($sql);
		$ret = $stmt->execute($insert);
		return array('status' => $ret, 'data' => $this->db->lastInsertId());
	}
	/**
	 * Edit Item
	 * @param  string  $id   Id of item
	 * @param  array $data array of data required
	 * @return bool       success, failure
	 */
	public function editItem($id,$data){

	}

	/**
	 * Delete Item by id
	 * @param  string $id Id of item
	 * @return bool   success, failure
	 */
	public function deleteItem($id){

	}

	public function getItemById($id){
		$sql = 'SELECT * FROM filestore_email WHERE id = :id LIMIT 1';
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':id' => $id));
		return $stmt->fetch();
	}

	/**
	 * Get list of items for driver
	 * @return array Array of items.
	 */
	public function listItems($start = 0,$limit = 9999){
		$sql = 'SELECT count(id) FROM filestore_ftp';
		$ret = $this->db->query($sql);
		$rowCount = $ret->fetchColumn();
		if($rowCount > 0){
			$sql = 'SELECT * FROM filestore_email LIMIT '.$limit.' OFFSET '.$start;
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$rows =  $stmt->fetchAll(\PDO::FETCH_ASSOC);
			return array('count' => $rowCount, 'rows' => $rows);
		}else{
			return array('count' => $rowCount, 'rows' => array());
		}
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
		$item = $this->getById($id);
		$mail = \FreePBX::Mail();
		$mail->setSubject($item['subject']);
		$mail->setBcc(json_decode($item['addr'],true));
		$mail->setBody($item['body']);
		$mail->attach(\Swift_Attachment::fromPath($local));
		return $mail->send();
	}
}
