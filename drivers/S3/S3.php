<?php
namespace FreePBX\modules\Filestore\drivers\S3;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FileExistsException;

class S3{
	public function __construct($freepbx=null){
		$this->FreePBX = $freepbx;
		if(!empty($freepbx)){
			$this->db =  $freepbx->Database;
		}
		require __DIR__ . '/../../vendor/autoload.php';
		$this->dbcols = array(
			"awsaccesskey" => '',
			"awssecret" => '',
			"desc" => '',
			"displayname" => '',
			"bucket" => '',
			"region" => '',
			'immortal' => '',
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
		$regions =
		[
			'US East (Ohio)' => 'us-east-2',
			'US East (N. Virginia)' => 'us-east-1',
			'AWS GovCloud (US-East)' => 'us-gov-east-1',
			'US West (N. California)' => 'us-west-1',
			'US West (Oregon)' => 'us-west-2',
			'AWS GovCloud (US)' => 'us-gov-west-1',
			'Canada (Central)' => 'ca-central-1',
			'Asia Pacific (Mumbai)' => 'ap-south-1',
			'Asia Pacific (Osaka-Local)' => 'ap-northeast-3',
			'Asia Pacific (Seoul)' => 'ap-northeast-2',
			'Asia Pacific (Singapore)' => 'ap-southeast-1',
			'Asia Pacific (Sydney)' => 'ap-southeast-2',
			'Asia Pacific (Tokyo)' => 'ap-northeast-1',
			'China (Beijing)' => 'cn-north-1',
			'China (Ningxia)' => 'cn-northwest-1',
			'EU (Frankfurt)' => 'eu-central-1',
			'EU (Ireland)' => 'eu-west-1',
			'EU (London)' => 'eu-west-2',
			'EU (Paris)' => 'eu-west-3',
			'EU (Stockholm)' => 'eu-north-1	',
			'South America (São Paulo)' => 'sa-east-1',
		];
		if(empty($_GET['view'])){
			return load_view(__DIR__.'/views/grid.php');
		}else{
			$vars = array();
			if(isset($_GET['id'])){
				$vars = $this->getItemById($_GET['id']);
			}
			$vars['regions'] = $regions;
			return load_view(__DIR__.'/views/form.php',$vars);
		}
	}
	public function getActionBar($request) {
		if(!isset($_GET['view']) || $_GET['view'] != 'form'){
			return array();
		}
		switch($request['driver']) {
			case 'S3':
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
				return $data;
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
		if($req['driver'] == 'S3'){
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
		$id = (isset($data['id']) && !empty($data['id']))?$data['id']:$this->generateId();
		foreach ($this->dbcols as $key => $val) {
			switch ($key) {
				case 'id':
					continue;
				default:
					$value = isset($data[$key])?$data[$key]:$val;
					$this->FreePBX->Filestore->setConfig($key,$value,$id);
				break;
			}
		}
		$description = isset($data['desc'])?$data['desc']:$this->dbcols['desc'];
		$this->FreePBX->Filestore->setConfig($id,array('id' => $id, 'bucket' => $data['bucket'], 'desc' => $description),'s3servers');
		return array('status' => $ret, 'data' => $id);
	}
	/**
	 * Edit Item
	 * @param  string  $id   Id of item
	 * @param  array $data array of data required
	 * @return bool       success, failure
	 */
	public function editItem($id,$data){
		$this->addItem($data);
	}

	/**
	 * Delete Item by id
	 * @param  string $id Id of item
	 * @return bool   success, failure
	 */
	public function deleteItem($id){
		$this->FreePBX->Filestore->setConfig($id,false,'s3servers');
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
		$items = $this->FreePBX->Filestore->getAll('s3servers');
		return array_values($items);
	}
	//S3 STUFF
	public function getConnection($id){
		$item = $this->getItemById($id);
		$client = new S3Client([
			'credentials' => [
				'key'    => trim($item['awsaccesskey']),
				'secret' => trim($item['awssecret'])
			],
			'region' => $item['region'],
			'version' => 'latest',
			'signature_version' => 'v4',
		]);
		$adapter = new AwsS3Adapter($client, $item['bucket']);
		$filesystem = new Filesystem($adapter);
		return $filesystem;
	}

	//Filestore Actions
	/**
	 * Get file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @return        File object
	 */
	public function get($id,$remote,$local){
		$filesystem = $this->getConnection($id);
		try {
			$contents = $filesystem->read($remote);
			$fh = fopen($local,"w");
			fwrite($fh,$contents);
			fclose($fh);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Put file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param file $file to upload
	 * @return bool  object created
	 */
	public function put($id,$local,$remote){
		$filesystem = $this->getConnection($id);
		try {
			$ret = $filesystem->put($remote, $local);
		} catch (\Exception $e) {
			throw $e;
		}
		return $ret;
	}

	/**
	 * List files/directories in path
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param  type file/dir Default both.
	 * @return        File object
	 */
	public function ls($id,$path,$recursive=false){
		$filesystem = $this->getConnection($id);
		try {
			$ret = $filesystem->listContents($path, $recursive);
		} catch (\Exception $e) {
			throw $e;
		}
		return $ret;
	}

	/**
	 * Delete object by path
	 * @param  int  $id        filestore item id
	 * @param  [type]  $path   path to delete
	 * @param  boolean $recursive delete recursively
	 * @return boolean            Did it delete?
	 */
	public function delete($id,$path,$recursive=false){
		$filesystem = $this->getConnection($id);
		try {
			$ret = $filesystem->delete($path);
		} catch (\Exception $e) {
			throw $e;
		}
		return $ret;
	}

	/**
	 * Rename file or directory
	 * @param  int $id      filestore item id
	 * @param  string $oldpath file to rename
	 * @param  string $newpath What to rename it to
	 * @return bool  was the operation successful
	 */
	public function move($id,$oldpath,$newpath){
		$filesystem = $this->getConnection($id);
		try {
			$ret = $filesystem->rename($oldpath, $newpath);
		} catch (\Exception $e) {
			throw $e;
		}
		return $ret;
	}

	/**
	* Find a file's path
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function find($id,$filename){
		$filesystem = $this->getConnection($id);
		try {
			$contents = $filemanager->listContents();
			foreach ($contents as $object) {
				if($object['basename'] == $filename){
					return ['path' => $object['path'], 'file' => $object['basename']];
				}
			}

		} catch (\Exception $e) {
			return false;
		}
		return false;
	}
	/**
	* Check if file exists
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function fileExists($id,$path){
		$filesystem = $this->getConnection($id);
		try {
			$ret = $filesystem->has($path);
		} catch (\Exception $e) {
			return false;
		}
		return $ret;
	}
	/**
	* Check if directory exists
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function directoryExists($id,$path){
		return $thie->fileExists($id, $path);
	}
	/**
	* Make Directory
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function makeDirectory($id,$path){
		$filesystem = $this->getConnection($id);
		try {
			$ret = $filesystem->createDir($path);
		} catch (\Exception $e) {
			return false;
		}
		return $ret;
	}
}
