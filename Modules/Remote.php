<?php
namespace FreePBX\modules\Filestore\Modules;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use phpseclib\Net\SCP;
include __DIR__.'/../vendor/autoload.php';
/**
 * Remote is a class for managing ssh tasks
 * 
 * This class uses phpseclib to perform various tasks
 * that may be required to properly use SSH
 */
#[\AllowDynamicProperties]
 class Remote{
     public function __construct(){
        $this->ssh = null;
    }
    public function createSSH($host = null){
        if(empty($host)){
            throw new \InvalidArgumentException('This method must be called with a SSH host');
        }   
        $this->ssh = new SSH2($host);
    }
    public function authenticateSSH($user = 'root', $keyFile = ''){
        $key = new RSA();
        $key->loadKey(file_get_contents($keyFile));
        if(!$this->ssh->login($user,$key)){
            var_dump($this->ssh->getErrors());
            return false;
        }
        return true;
    }

    public function generateKey($outputDir){
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0700, true);
        }
        // Generate a new ECDSA key pair
        $res = openssl_pkey_new(array(
            'private_key_bits' => 384,
            'curve_name' => 'secp384r1',
            'config' => '/etc/ssl/openssl.cnf',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'private_key_file' => '/tmp/tempkey',
        ));
        
        if (!$res) {
            die("Private key generation failed: " . openssl_error_string());
        }
        
        // Extract the private key
        openssl_pkey_export($res, $privateKey);

        // Extract the public key
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails['key'];
        
        $private = fopen($outputDir."/id_ecdsa","w");
        $public = fopen($outputDir."/id_ecdsa.pub","w");
        $success1 = fwrite($private,$privateKey);
        $success2 = fwrite($public,$publicKey);
        fclose($private);
        fclose($public);
        if(!$success1 || !$success2){
            @unlink($outputDir.'/id_ecdsa');
            @unlink($outputDir.'/id_ecdsa.pub');
            return false;
        }
        return true;
    }   
    public function copyPublicKey($localPath, $remotePath){
        $scp = new SCP($this->ssh);
        $scp->put($remotePath,$localPath, SCP::SOURCE_LOCAL_FILE);
    }
    public function grabFile($remotePath, $localPath){
        $scp = new SCP($this->ssh);
        return $scp->get($remotePath, $localPath);
    }
    public function sendCommand($command,$returnError = false){
        if(!$returnError){
            $this->ssh->enableQuietMode();
        }
        $callback = function($data){ echo $data; };
        $ret = $this->ssh->exec($command,$callback);
        if($returnError){
            return [
                'err' => $this->ssh->getStdError(),
                'exit' => $this->ssh->getExitStatus(),
            ];
        }
        return $ret;
    }
    //TODO: Validate?
    public function addTrustedKey($key){
        return \FreePBX::Filestore()->runHook('addpubkey',$key);
    }
 }