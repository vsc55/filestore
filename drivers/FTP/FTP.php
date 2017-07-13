<?php
namespace FreePBX\modules\Filestore\drivers\FTP;
use \Touki\FTP\FTP as ToukiFTP;
use \Touki\FTP\FTPWrapper;
use \Touki\FTP\Connection\Connection;
use \Touki\FTP\PermissionsFactory;
use \Touki\FTP\FilesystemFactory;
use \Touki\FTP\WindowsFilesystemFactory;
use \Touki\FTP\DownloaderVoter;
use \Touki\FTP\UploaderVoter;
use \Touki\FTP\CreatorVoter;
use \Touki\FTP\DeleterVoter;
use \Touki\FTP\Manager\FTPFilesystemManager;
use \Touki\FTP\Model\File;
use \Touki\FTP\Model\Directory;
use \Touki\FTP\Exception\DirectoryException;
use \Touki\FTP\Exception\ConnectionEstablishedException;
use \Touki\FTP\Exception\InvalidArgumentException;
class FTP{
	public function __construct($freepbx=null){
		$this->FreePBX = $freepbx;
		if(!empty($freepbx)){
			$this->db =  $freepbx->Database;
		}
		require __DIR__ . '/vendor/autoload.php';
		$this->connections = array();
		$this->dbcols = array(
			"id" => '',
			"name" => '',
			"desc" => '',
			"host" => '',
			"port" => 21,
			"user" => 'anonymous',
			"password" => 'anonymous',
			"fstype" => 'auto',
			"path" => '/',
			"transfer" => 'passive'
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
			"host" => array(
				"type" => "string",
				"length" => 150
			),
			"port" => array(
				"type" => "integer",
			),
			"user" => array(
				"type" => "string",
				"length" => 150
			),
			"password" => array(
				"type" => "string",
				"length" => 150
			),
			"fstype" => array(
				"type" => "string",
				"length" => 150
			),
			"path" => array(
				"type" => "string",
				"length" => 150
			),
			"transfer" => array(
				"type" => "string",
				"length" => 150
			)
		);
		$table = $this->db->migrate("filestore_ftp");
		$table->modify($cols);
	}

	/**
	 * Run on module uninstall
	 * @return null
	 */
	public function uninstall(){
		$sql = "DROP TABLE filestore_ftp";
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
			'read',
			'write',
			'backup',
			'general'
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
		$sql = 'INSERT INTO filestore_ftp ('.implode(',', array_keys($keys)).') VALUES ('.implode(',',array_keys($insert)).')';
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
		$sql = 'SELECT * FROM filestore_ftp WHERE id = :id LIMIT 1';
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
			$sql = 'SELECT * FROM filestore_ftp LIMIT '.$limit.' OFFSET '.$start;
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$rows =  $stmt->fetchAll(\PDO::FETCH_ASSOC);
			return array('count' => $rowCount, 'rows' => $rows);
		}else{
			return array('count' => $rowCount, 'rows' => array());
		}
	}

	//TOUKI STUFF
	public function ftpConnection($host, $username,$password, $fstype = 'auto' ,$port = 21, $transfer = 'passive'){
		$connid = md5($host.$username.$password);
			if(isset($this->connections[$connid])){
				return $this->connections[$connid];
			}
		$connection = new Connection($host, $username, $password, $port, 90, ($transfer == 'passive'));
		$connection->open();
		$wrapper = new FTPWrapper($connection);
		$permFactory = new PermissionsFactory;
		switch ($fstype) {
			case 'unix':
				$fsFactory = new FilesystemFactory($permFactory);
			break;
			case 'windows':
				$fsFactory = new WindowsFilesystemFactory;
			break;
			case 'auto':
			default:
				$ftptype = $wrapper->systype();
				if(strtolower($ftptype) == "unix"){
					$fsFactory = new FilesystemFactory($permFactory);
				}else{
					$fsFactory = new WindowsFilesystemFactory;
				}
			break;
		}

		$manager = new \Touki\FTP\Manager\FTPFilesystemManager($wrapper, $fsFactory);
		$dlVoter = new DownloaderVoter;
		$ulVoter = new UploaderVoter;
		$ulVoter->addDefaultFTPUploaders($wrapper);
		$crVoter = new CreatorVoter;
		$crVoter->addDefaultFTPCreators($wrapper, $manager);
		$deVoter = new DeleterVoter;
		$deVoter->addDefaultFTPDeleters($wrapper, $manager);
		$ftp = new ToukiFTP($manager, $dlVoter, $ulVoter, $crVoter, $deVoter);
		if(!$ftp){
			throw new \Exception(_("Error creating the FTP object"), 500);
		}

		$this->connections[$connid] = $ftp;
		return $ftp;
	}

	//Filestore Actions
	/**
	 * Get file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @return        File object
	 */
	public function get($id,$remote,$local){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		$options = array(
			ToukiFTP::NON_BLOCKING  => false,     // Whether to deal with a callback while downloading
			ToukiFTP::TRANSFER_MODE => FTP_BINARY // Transfer Mode
		);
		if($connection->fileExists(new File($path))){
			$file = $connection->findFileByName($remote);
			return $connection->download($local,$file,$options);
		}
		return false;
	}

	/**
	 * Put file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param file $file to upload
	 * @return bool  object created
	 */
	public function put($id,$local,$remote){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		return $connection->upload(new File($remote),$local);
	}

	/**
	 * List files/directories in path
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param  type file/dir Default both.
	 * @return        File object
	 */
	public function ls($id,$path,$type=null){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		$dirs = array();
		$files = array();
		$ftpdirs = $connection->findFilesystems(new Directory($path));
		foreach ($ftpdirs as $dir) {
			if(is_a($dir, 'Touki\\FTP\\Model\\Directory')){
				$dirs[] = $dir->getRealPath();
			}else{
				$files[] = $dir->getRealPath();
			}
		}
		if($type == 'file'){
			return $files;
		}
		if($type == 'dir'){
			return $dirs;
		}
		return array_merge($files,$dirs);
	}

	/**
	 * Delete object by path
	 * @param  int  $id        filestore item id
	 * @param  [type]  $path   path to delete
	 * @param  boolean $recursive delete recursively
	 * @return boolean            Did it delete?
	 */
	public function delete($id,$path,$recursive=false){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		$f = $connection->findFileByName($path);
		if(empty($f)){
			throw new \Exception("File not found", 404);
		}
		return $connection->delete($f);
	}

	/**
	 * Rename file or directory
	 * @param  int $id      filestore item id
	 * @param  string $oldpath file to rename
	 * @param  string $newpath What to rename it to
	 * @return bool  was the operation successful
	 */
	public function move($id,$oldpath,$newpath){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		return $connection->rename($oldpath,$newpath);
	}

	/**
	* Find a file's path
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function find($id,$filename){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		$ret = $connection->findFileByName($filename);
		return $ret->getRealPath();
	}
	/**
	* Check if file exists
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function fileExists($id,$path){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		return $connection->directoryExists(new Directory($path));
	}
	/**
	* Check if directory exists
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function directoryExists($id,$path){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		return $connection->directoryExists(new Directory($path));
	}
	/**
	* Make Directory
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function makeDirectory($id,$path){
		$item = $this->getItemById($id);
		$connection = $this->ftpConnection($item['host'], $item['user'], $item['password'], $item['fstype'], $item['port'], $item['transfer']);
		return $connection->create(new Directory($path),array(ToukiFTP::RECURSIVE => true));
	}


}
