<?php
namespace FreePBX\modules;

/*
 * Class stub for BMO Module class
 * In _Construct you may remove the database line if you don't use it
 * In getActionbar change extdisplay to align with whatever variable you use to decide if the page is in edit mode.
 *
 */

class Filestore extends \DB_Helper implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->classlist = [];
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->hookdrivers = [];
		$this->drivers = $this->listDrivers();

	}

	public function listDrivers(){
		$drivers = array();
		foreach (new \DirectoryIterator(__DIR__.'/drivers/') as $dir) {
			if($dir->isDot() || !$dir->isDir()){
				continue;
			}
			$drivers[] = $dir->getFilename();
		}
		$hooks = $this->FreePBX->Hooks->processHooks();
		$hookdrivers = array();
		foreach ($hooks as $hook) {
			$this->hookdrivers[$hook['name']] = $hook['class'];
		}
		return $drivers;
	}

	public function install() {
		foreach ($this->drivers as $key) {
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$key.'\\'.$key;
			$class = new $class($this->FreePBX);
			$class->install();
		}
	}
	public function uninstall() {
		foreach ($this->drivers as $key) {
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$key.'\\'.$key;
			$class = new $class($this->FreePBX);
			$class->uninstall();
		}
	}
	public function getDisplay($driver){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		$class = new $class($this->FreePBX);
		return $class->displayView();
	}
	public function getSettingDisplay($driver){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class, 'settingsView')){
			return $class->settingsView();
		}
		return false;
	}
	public function backup() {}
	public function restore($backup) {}
	public function doConfigPageInit($page) {
		if(isset($_REQUEST['driver'])){
			$driver = $_REQUEST['driver'];
			if(isset($this->hookdrivers[$driver])){
				$class = $this->hookdrivers[$driver];
			}else{
				$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
			}
			$class = new $class($this->FreePBX);
			if(isset($_REQUEST['authaction'])){
				$class->authAction($_REQUEST);
			}else{
				$class->doConfigPageInit($page);
			}
		}
	}
	public function getActionBar($request) {
		if(!isset($_REQUEST['driver'])){
			return array();
		}else{
			$driver = $_REQUEST['driver'];
			if(isset($this->hookdrivers[$driver])){
				$class = $this->hookdrivers[$driver];
			}else{
				$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
			}
			$class = new $class;
			if(method_exists($class,'getActionBar')){
				return $class->getActionBar($request);
			}else{
				return array();
			}
		}
	}
	public function showPage(){
		if(!isset($_GET['driver'])|| empty($_GET['driver'])){
			$vars['drivers'] = $this->drivers;
			$vars['hookdrivers'] = array_keys($this->hookdrivers);
			$vars['fs'] = $this;
			return load_view(__DIR__.'/views/main.php',$vars);
		}else{
			if(isset($this->hookdrivers[$driver])){
				$class = new $this->hookdrivers[$driver];
				$class->getDisplay($driver);
			}else{
				return $this->getDisplay($_GET['driver']);
			}
		}
	}
	public function ajaxRequest($req, &$setting) {
		if(!isset($_REQUEST['driver'])){
			return false;
		}else{
			$driver = $_REQUEST['driver'];
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
			$class = new $class($this->FreePBX);
			if(method_exists($class,'ajaxRequest')){
				return $class->ajaxRequest($req);
			}else{
				return false;
			}
		}
	}
	public function ajaxHandler(){
		if(!isset($_REQUEST['driver'])){
			return false;
		}else{
			if(isset($this->hookdrivers[$driver])){
				$class = $this->hookdrivers[$driver];
			}else{
				$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
			}
			$class = new $class($this->FreePBX);
			if(method_exists($class,'ajaxHandler')){
				return $class->ajaxHandler();
			}else{
				return false;
			}
		}
	}
	public function getRightNav($request) {
		return '';
	}
	public function listLocations($permissions = 'all'){
		$locations = array(
			'filestoreTypes' => array(),
			'locations'	=> array(),
		);
		foreach ($this->drivers as $driver) {
			if(isset($this->hookdrivers[$driver])){
				$class = $this->hookdrivers[$driver];
			}else{
				$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
			}
			$class = new $class($this->FreePBX);
			if($class->methodSupported($permissions)){
				$locations['filestoreTypes'][] = $driver;
				$location['locations'][$driver] = isset($location['locations'][$driver])?$location['locations'][$driver]:array();
				foreach($class->listItems() as $item){
					$name = isset($item['name'])?$item['name']:$driver.'-'.substr($item['id'], -5);
					$description = isset($item['description'])?$item['description']:'';
					$locations['locations'][$driver][] = array('id' => $item['id'], 'name' => $name, 'description' => $description);
				}
			}
		}
		return $locations;
	}

	//CRUD actions
	/**
	 * Add a item for driver
	 * @param $data array of data for required
	 */
	public function addItem($driver,$data){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'addItem')){
			return $class->addItem($data);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'addItem'),501);
		}
	}

	/**
	 * Edit Item
	 * @param  string  $id   Id of item
	 * @param  array $data array of data required
	 * @return bool       success, failure
	 */
	public function editItem($driver,$id,$data){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'editItem')){
			return $class->editItem($id,$data);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'editItem'),501);
		}
	}

	/**
	 * Delete Item by id
	 * @param  string $id Id of item
	 * @return bool   success, failure
	 */
	public function deleteItem($id){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'deleteItem')){
			return $class->deleteItem($id);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'addItem'),501);
		}
	}

	/**
	 * Git list of items for driver
	 * @return array Array of items.
	 */
	public function listItems($driver){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'listItems')){
			return $class->listItems();
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'listItems'),501);
		}
	}

	//Filestore Actions
	/**
	 * Get file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @return        File object
	 */
	public function get($driver,$id,$remote,$local){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'get')){
			return $class->get($id,$remote,$local);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'get'),501);
		}
	}

	/**
	 * Put file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to put
	 * @param file $file to upload
	 * @return bool  object created
	 */
	public function put($driver,$id,$local,$remote){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'put')){
			return $class->put($id,$local,$remote);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'put'),501);
		}
	}

	/**
	 * List files/directories in path
	 * @param  int $id  filestore item id
	 * @param  string $path  path to list
	 * @param  type file/dir Default both.
	 * @return        File object
	 */
	public function ls($driver,$id,$path,$recursive=false){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'ls')){
			return $class->ls($id,$path,$recursive);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'ls'),501);
		}
	}

	/**
	 * Delete object by path
	 * @param  int  $id        filestore item id
	 * @param  [type]  $path   path to delete
	 * @param  boolean $recursive delete recursively
	 * @return boolean            Did it delete?
	 */
	public function delete($driver,$id,$path,$recursive=false){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'delete')){
			return $class->delete($id,$path,$recursive);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'delete'),501);
		}
	}

	/**
	 * Rename file or directory
	 * @param  int $id      filestore item id
	 * @param  string $oldpath file to rename
	 * @param  string $newpath What to rename it to
	 * @return bool  was the operation successful
	 */
	public function move($driver,$id,$oldpath,$newpath){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'move')){
			return $class->move($id,$oldpath,$newpath);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'move'),501);
		}
	}

	/**
	* Find a file's path
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function find($driver,$id,$filename){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'find')){
			return $class->get($id,$filename);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'find'),501);
		}
	}

	/**
	* check if file exists
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function fileExists($driver,$id,$path){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'fileExists')){
			return $class->exists($id,$path);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'fileExists'),501);
		}
	}
	/**
	* check if direcrory exists
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function directoryExists($driver,$id,$path){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'directoryExists')){
			return $class->directoryExists($id,$path);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'directoryExists'),501);
		}
	}
	/**
	* check if direcrory exists
	* @param  int $id       filestore item $id
	* @param  string $filename name of file to find
	* @return mixed $path  path of found item or false
	*/
	public function makeDirectory($driver,$id,$path){
		if(isset($this->hookdrivers[$driver])){
			$class = $this->hookdrivers[$driver];
		}else{
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		}		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = new $class($this->FreePBX);
		if(method_exists($class,'makeDirectory')){
			return $class->makeDirectory($id,$path);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'makeDirectory'),501);
		}
	}
}
