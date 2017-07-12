<?php
namespace FreePBX\modules\Filestore\drivers;
interface DriverBase{
	//Base actions
	/**
	 * Authentication action. This will process things like oauth flows
	 * @param  array $request http request
	 * @return bool action success/fail
	 */
	public function authAction($request);

	/**
	 * Run on install/update
	 * @return null
	 */
	public function install();

	/**
	 * Run on module uninstall
	 * @return null
	 */
	public function uninstall();

	/**
	 * The settings page for the module
	 * @return string html
	 */
	public function settingsView();

	/**
	 * The display view for non setting items.
	 * @return string html
	 */
	public function displayView();

	/**
	 * Process post data
	 * @param  [type] $page [description]
	 * @return [type]       [description]
	 */
	public function doConfigPageInit($page);

	/**
	 * Weather an implintation is supported in this driver
	 * @param  string $method the method "all,backuponly,readonly,writeonly"
	 * @return bool method is/not supported
	 */
	public function methodSupported($method);

	//CRUD actions
	/**
	 * Add a item for driver
	 * @param $data array of data for required
	 */
	public function addItem($data);
	/**
	 * Edit Item
	 * @param  string  $id   Id of item
	 * @param  array $data array of data required
	 * @return bool       success, failure
	 */
	public function editItem($id,$data);

	/**
	 * Delete Item by id
	 * @param  string $id Id of item
	 * @return bool   success, failure
	 */
	public function deleteItem($id);

	/**
	 * Git list of items for driver
	 * @return array Array of items.
	 */
	public function listItems();
	//Filestore Actions
	/**
	 * Get file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @return        File object
	 */
	public function get($id,$path);

	/**
	 * Put file
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param file $file to upload
	 * @return bool  object created
	 */
	public function put($id,$path,$file);

	/**
	 * List files/directories in path
	 * @param  int $id  filestore item id
	 * @param  string $path path of item to get
	 * @param  type file/dir Default both.
	 * @return        File object
	 */
	public function ls($id,$path,$type=null);

	/**
	 * Delete object by path
	 * @param  int  $id        filestore item id
	 * @param  [type]  $path   path to delete
	 * @param  boolean $recursive delete recursively
	 * @return boolean            Did it delete?
	 */
	public function delete($id,$path,$recursive=false);

	/**
	 * Rename file or directory
	 * @param  int $id      filestore item id
	 * @param  string $oldpath file to rename
	 * @param  string $newpath What to rename it to
	 * @return bool  was the operation successful
	 */
	public function move($id,$oldpath,$newpath);

/**
 * Find a file's path
 * @param  int $id       filestore item $id
 * @param  string $filename name of file to find
 * @return mixed $path  path of found item or false
 */
	public function find($id,$filename);

}
