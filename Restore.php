<?php
namespace FreePBX\modules\Filestore;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $settings = $this->getConfigs();
		$this->importKVStore($settings);
  }
}
