<?php

namespace FreePBX\modules\Filestore\drivers\FTP;

use League\Flysystem\Adapter\Ftp as FTPAdaptor;
use League\Flysystem\Filesystem;
use FreePBX\modules\Filestore\drivers\FlysystemBase;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory as MemoryStore;
use League\Flysystem\Sftp\SftpAdapter;

class FTP extends FlysystemBase
{
	protected static $path = __DIR__;
	protected static $validKeys = [
		"id" => '',
		"name" => '',
		"desc" => '',
		"host" => '',
		"port" => 21,
		"usetls" => 'no',
		"usesftp" => 'no',
		"user" => 'anonymous',
		"password" => 'anonymous',
		"timeout" => 30,
		"path" => '/',
		"transfer" => 'passive',
		"fstype" => "auto",
		'immortal' => '',
		'enabled' => 'yes',
	];

	/**
	 * Weather an implintation is supported in this driver
	 * @param  string $method the method "all,backup,readonly,writeonly"
	 * @return bool method is/not supported
	 */
	public function methodSupported($method)
	{
		$permissions = array(
			'all',
			'read',
			'write',
			'backup',
			'general'
		);
		return in_array($method, $permissions);
	}

	public function getHandler()
	{
		if (isset($this->handler)) {
			return $this->handler;
		}

		if(isset($this->config['usesftp']) && $this->config['usesftp'] == 'yes') {
			$this->handler = $this->getSftpHandler();
		} else {
			$this->handler = $this->getFtpHandler();
		}

		return $this->handler;
	}

	public function getFtpHandler() {

		$options = [
			'host' => $this->config['host'],
			'username' => $this->config['user'],
			'password' => $this->config['password'],

			/** optional config settings */
			'port' => $this->config['port'],
			'root' => $this->config['path'],
			'passive' => ($this->config['transfer'] === 'passive'),
			'timeout' => (isset($this->config['timeout']) && !empty($this->config['timeout'])) ? $this->config['timeout'] : 30,
			'ssl' => isset($this->config['usetls']) && $this->config['usetls'] === 'yes',
		];

		if ($this->config['fstype'] !== 'auto') {
			$options['systemType'] = $this->config['fstype'];
		}

		// Decorate the adapter
		$adapter = new CachedAdapter(new FTPAdaptor($options), new MemoryStore());

		// And use that to create the file system
		$ftphandler = new Filesystem($adapter);
		return $ftphandler;
	}

	public function getSftpHandler() {
		// Setup the SFTP adapter using configuration details
		$adapter = new SftpAdapter([
			'host' => $this->config['host'],
			'port' => $this->config['port'],
			'username' => $this->config['user'],
			'password' => $this->config['password'],  // Or use privateKey and passphrase for key authentication
			'root' => $this->config['path'],  // Optional, default is '/'
			'timeout' => (isset($this->config['timeout']) && !empty($this->config['timeout'])) ? $this->config['timeout'] : 30,
			'ssl' => isset($this->config['usetls']) && $this->config['usetls'] === 'yes',
		]);

		// Initialize the Flysystem with the SFTP adapter
		$sftphandler = new Filesystem($adapter);
		return $sftphandler;
	}
}
