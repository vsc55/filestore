<?php
namespace FreePBX\modules\Filestore\drivers\Email;
use \FreePBX\modules\Filestore\drivers\DriverBase;
class Email extends DriverBase {
	protected static $path = __DIR__;
	protected static $validKeys = [
		"id" => '',
		"name" => '',
		"desc" => '',
		"addr" => '',
		"maxsize" => '25',
		"maxtype" => 'mb',
		"from" => '',
		"body" => ''
	];

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


	//Filestore Actions
	public function put($path, $contents) {
		switch($this->config['maxtype']) {
			case 'mb':
				$maxsize = $this->config['maxsize'] * 1000000;
			break;
			case 'kb':
				$maxsize = $this->config['maxsize'] * 1000;
			break;
		}
		if(strlen($contents) > $maxsize) {
			throw new \Exception("File size exceeds maxsize!");
		}

		$from = isset($this->config['from'])?$this->config['from']:get_current_user().'@'.gethostname();
		if(isset($this->config['from']) && !empty($this->config['from'])){
			$from = $this->config['from'];
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

		$to = array_filter(explode("\n",$this->config['addr']),trim);
		$brand = $this->FreePBX->Config->get("DASHBOARD_FREEPBX_BRAND");
		$ident = $this->FreePBX->Config->get("FREEPBX_SYSTEM_IDENT");
		$body = !empty($this->config['body'])?$this->config['body']:sprintf(_("File from %s, Identifier: %s"),$brand,$ident);
		$mail = \FreePBX::Mail();
		$mail->setSubject($this->config['desc']);
		$mail->setFrom($from,$from);
		$mail->setTo($to);
		$mail->setBody($body);

		file_put_contents("/tmp/".$path, $contents);
		$f_mime = mime_content_type("/tmp/".$path);
		unlink("/tmp/".$path);

		$mail->getMessage()->attach(\Swift_Attachment::newInstance($contents, basename($path), $f_mime));
		$ret = $mail->send();
		return $ret;
	}

	public function putStream($path, $resource) {
		return $this->put($path, stream_get_contents($resource));
	}
  
	public function getDirRecursive($dir){
		$directory = new \RecursiveDirectoryIterator($dir,\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::CURRENT_AS_FILEINFO);
		$iterator = new \RecursiveIteratorIterator($directory);
		$results = [];
		foreach ($iterator as $info) {
		      $results[] = $info->getPathname();
		}
		return $results;
	}

	/**
	 * @method listContents
	 *
	 * @param  string $path
	 * @param  boolean $recursive
	 *
	 * @return array()
	 */
	public function listContents($path = '', $recursive = false){
		$result = [];
		if($path != '' && is_dir($path) && is_bool($recursive)){
			if($recursive === false){
				$result = array_diff(scandir($path), array('..', '.'));
			}
			else{
				$result = $this->getDirRecursive($path);
			}			
		}
		return $result;
	}
}
