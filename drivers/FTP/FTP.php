<?php
namespace FreePBX\modules\Filestore\drivers\FTP;
class FTP{
	public function __construct($freepbx=null){
		$this->FreePBX = $freepbx;
		if(!empty($freepbx)){
			$this->db =  $freepbx->Database;
		}
	}
	//Base actions
	/**
	 * Authentication action. This will process things like oauth flows
	 * @param  array $request http request
	 * @return bool action success/fail
	 */
	public function authAction($request){

	}

	/**
	 * Run on install/update
	 * @return null
	 */
	public function install(){
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
			"description" => array(
				"type" => "string",
				"length" => 150
			),
			"host" => array(
				"type" => "string",
				"length" => 150
			),
			"port" => array(
				"type" => "string",
				"length" => 6
			),
			"username" => array(
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
			"transferMode" => array(
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
	 * The settings page for the module
	 * @return string html
	 */
	public function settingsView(){
		return '';
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
				$vars = $this->getById($_GET['id']);
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

	/**
	 * Git list of items for driver
	 * @return array Array of items.
	 */
	public function listItems(){

	}
	//Filestore Actions
	/**
	 * Get file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @return        File object
	 */
	public function get($id,$path){

	}

	/**
	 * Put file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param file $file to upload
	 * @return bool  object created
	 */
	public function put($id,$path,$file){

	}

	/**
	 * List files/directories in path
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param  type file/dir Default both.
	 * @return        File object
	 */
	public function ls($id,$path,$type=null){

	}

	/**
	 * Delete object by path
	 * @param  int  $id        filestore item id
	 * @param  [type]  $path   path to delete
	 * @param  boolean $recursive delete recursively
	 * @return boolean            Did it delete?
	 */
	public function delete($id,$path,$recursive=false){

	}

	/**
	 * Rename file or directory
	 * @param  int $id      filestore item id
	 * @param  string $oldpath file to rename
	 * @param  string $newpath What to rename it to
	 * @return bool  was the operation successful
	 */
	public function move($id,$oldpath,$newpath){

	}

	/**
	* Find a file's path
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function find($id,$filename){

	}
}
