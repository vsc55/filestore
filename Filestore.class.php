<?php
namespace FreePBX\modules;

/*
 * Class stub for BMO Module class
 * In _Construct you may remove the database line if you don't use it
 * In getActionbar change extdisplay to align with whatever variable you use to decide if the page is in edit mode.
 *
 */

class Filestore extends \FreePBX_Helpers implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->classlist = [];
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->hookdrivers = null;
		$this->drivers = $this->listDrivers();

	}

	public function listDrivers(){
		$drivers = array();
		foreach (new \DirectoryIterator(__DIR__.'/drivers/') as $dir) {
			if($dir->isDot() || !$dir->isDir()){
				continue;
			}
			$driver = $dir->getFilename();
			$drivers[] = $driver;
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
		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		$class = new $class($this->FreePBX);
		return $class->displayView();
	}
	public function getSettingDisplay($driver){
		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		$class = new $class($this->FreePBX);
		if(method_exists($class, 'settingsView')){
			return $class->settingsView();
		}
		return false;
	}
	public function backup($backup) {
		$kvstoreids = $this->getAllids();
		$kvstoreids[] = 'noid';
		$settings = [];
		foreach ($kvstoreids as $value) {
			$settings[$value] = $this->getAll($value);
		}
		$backup->addConfigs($settings);
	}
	public function restore($restore) {
		$settings = $restore->getConfigs();
		$ids = [];
		if(!$restore->getReplace()){
			$ids = $this->getAllids();
			$ids = is_array($ids)?$ids:[];
		}
		foreach ($settings as $key => $value) {
			if(in_array($key, $ids)){
				continue;
			}
			$this->setMultiConfig($value,$id);
		}
	}
	public function doConfigPageInit($page) {
		if(isset($_REQUEST['driver'])){
			$driver = $_REQUEST['driver'];
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
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
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
			$class = new $class($this->FreePBX);
			if(method_exists($class,'getActionBar')){
				return $class->getActionBar($request);
			}else{
				return array();
			}
		}
	}
	public function showPage(){
		if(!isset($_GET['driver'])|| empty($_GET['driver'])){
			$vars['drivers'] = $this->validateDrivers($this->drivers);
			$vars['fs'] = $this;
			return load_view(__DIR__.'/views/main.php',$vars);
		}else{
			return $this->getDisplay($_GET['driver']);
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
		}
		$driver = $_REQUEST['driver'];
		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		$class = new $class($this->FreePBX);
		if(method_exists($class,'ajaxHandler')){
				return $class->ajaxHandler();
		}
		return false;
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
			$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
			$class = new $class($this->FreePBX);
			$locations['filestoreTypes'][] = $driver;
			$location['locations'][$driver] = isset($location['locations'][$driver])?$location['locations'][$driver]:array();
			foreach($class->listItems() as $item){
				$name = isset($item['name'])?$item['name']:$driver.'-'.substr($item['id'], -5);
				$description = isset($item['description'])?$item['description']:'';
				$locations['locations'][$driver][] = array('id' => $item['id'], 'name' => $name, 'description' => $description);
			}
		}
		return $locations;
	}

	/**
	 * Validates driver list
	 *
	 * @param array $drivers
	 * @return array validated drivers. 
	 */
	public function validateDrivers($drivers = []){
		$final = [];
		foreach($drivers as $k => $v){
			if(!$this->getConfig('d'.$v)){
				$final[$k] = $v;
			}
		}
		return $final;
	}

	//CRUD actions
	/**
	 * Add a item for driver
	 * @param $data array of data for required
	 */
	public function addItem($driver,$data){
		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
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

		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
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
		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
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
		$class = "\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
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
		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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

		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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

		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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
		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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

		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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
		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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
		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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
		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
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
		$class = is_object($driver)?$driver:"\FreePBX\\modules\\Filestore\\drivers\\".$driver.'\\'.$driver;			
		if(!class_exists($class)){
			throw new \Exception(sprintf(_("The requested driver %s seems invalid"),$driver),404);
		}
		$class = is_object($class)?$class:new $class($this->FreePBX);
		if(method_exists($class,'makeDirectory')){
			return $class->makeDirectory($id,$path);
		}else{
			throw new \Exception(sprintf(_("The Driver %s doesn't support the method %s"),$driver,'makeDirectory'),501);
		}
	}
	public function runHook($hookname,$params = false){
		if (!file_exists("/etc/incron.d/sysadmin")) {
			throw new \Exception("Sysadmin RPM not up to date, or not a known OS. Can not start System Firewall. See http://bit.ly/fpbxfirewall");
		}
		$spooldir = $this->FreePBX->Config->get('ASTSPOOLDIR');
		$basedir = $spooldir."/asterisk/incron";
		if (!is_dir($basedir)) {
			throw new \Exception("$basedir is not a directory");
		}
		// Does our hook actually exist?
		if (!file_exists(__DIR__."/hooks/$hookname")) {
			throw new \Exception("Hook $hookname doesn't exist");
		}
		$filename = $basedir.'/filestore'.$hookname;

		// Do I have any params?
		if ($params) {
			// Oh. I do. If it's an array, json encode and base64
			if (is_array($params)) {
				$b = base64_encode(gzcompress(json_encode($params)));
				// Note we derp the base64, changing / to _, because filepath.
				$filename .= ".".str_replace('/', '_', $b);
			} elseif (is_object($params)) {
				throw new \Exception("Can't pass objects to hooks");
			} else {
				// Cast it to a string if it's anything else, and then make sure
				// it doesn't have any spaces.
				$filename .= ".".preg_replace("/[[:blank:]]+/", (string) $params);
			}
		}

		$fh = fopen($filename, "w+");
		if ($fh === false) {
			// WTF, unable to create file?
			throw new \Exception("Unable to create hook trigger '$filename'");
		}

		// As soon as we close it, incron does its thing.
		fclose($fh);

		// Wait for up to 5 seconds and make sure it's been deleted.
		$maxloops = 10;
		$deleted = false;
		while ($maxloops--) {
			if (!file_exists($filename)) {
				$deleted = true;
				break;
			}
			usleep(500000);
		}

		if (!$deleted) {
			throw new \Exception("Hook file '$filename' was not picked up by Incron after 5 seconds. Is it not running?");
		}
		return true;
	}
	public function listAllFilesByPath($path){
		$final = [];
		$locations = $this->listLocations('all');
		foreach($locations['locations'] as $driver => $instances ){
			if($driver == "Email"){
				continue;
			}
			foreach($instances as $instance){
				$final[$driver][$instance['id']] = $instance;
				try{
					$final[$driver][$instance['id']]['results'] = $this->ls($driver,$instance['id'],$path,true);
				}catch(\Exception $e){
					dbug($e->getMessage());
					continue;
				}
			}
		}
		return $final;
	}
}
