<?php

namespace FreePBX\modules\Filestore\drivers\FTP;

use League\Flysystem\Adapter\Ftp as FTPAdaptor;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpConnectionProvider;
use \League\Flysystem\Ftp\NoopCommandConnectivityChecker;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use FreePBX\modules\Filestore\drivers\FlysystemBase;
use League\Flysystem\Cached\Storage\Memory as MemoryStore;

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

		$systemType=null;
		if (isset($this->config['fstype']) && $this->config['fstype'] !== 'auto') {
			$systemType= $this->config['fstype'];
		}
		$ftpConnectionOptions = FtpConnectionOptions::fromArray([
			'host' => $this->config['host'], 
			'root' => $this->config['path'], 
			'username' => $this->config['user'], 
			'password' => $this->config['password'], 
			'port' => (int) $this->config['port'], 
			'ssl' => (isset($this->config['usetls']) && $this->config['usetls'] === 'yes'),
			'timeout' => (isset($this->config['timeout']) && !empty($this->config['timeout'])) ? (int) $this->config['timeout'] : 30,
			'utf8' => false,
			'passive' => ($this->config['transfer'] === 'passive'),
			'transferMode' => FTP_BINARY,
			'systemType' => $systemType, 
			'ignorePassiveAddress' => null, 
			'timestampsOnUnixListingsEnabled' => false, 
			'recurseManually' => true 
		]);
		
		$adapter = new FtpAdapter(
			$ftpConnectionOptions,
			new FtpConnectionProvider(),
			new NoopCommandConnectivityChecker(),
			PortableVisibilityConverter::fromArray([
				'file' => [
					'public' => 0644,
					'private' => 0600,
				],
				'dir' => [
					'public' => 0755,
					'private' => 0700,
				],
			])
		);
		$this->handler = new Filesystem($adapter);
		return $this->handler;
	}
}