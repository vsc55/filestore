<?php 

namespace FreepPBX\filestore\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\filestore;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

/**
 * Pm2GqlApiTest
 */
class FilestoreGqlApiTest extends ApiBaseTestCase {
    protected static $filestore;
        
    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass() {
      parent::setUpBeforeClass();
      self::$filestore = self::$freepbx->filestore;
    }
        
    /**
     * tearDownAfterClass
     *
     * @return void
     */
    public static function tearDownAfterClass() {
      parent::tearDownAfterClass();
    }
      
  /**
   * test_fetchAWSRegions_all_Good_Should_return_true
   *
   * @return void
   */
  public function test_fetchAWSRegions_all_Good_Should_return_true(){
    $response = $this->request("query{
      fetchAWSRegion{
        status
        message
        regions
      }}");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"data":{"fetchAWSRegion":{"status":true,"message":"List of AWS Storage","regions":"[\"us-east-2\",\"us-east-1\",\"us-gov-east-1\",\"us-west-1\",\"us-west-2\",\"us-gov-west-1\",\"ca-central-1\",\"ap-south-1\",\"ap-northeast-3\",\"ap-northeast-2\",\"ap-southeast-1\",\"ap-southeast-2\",\"ap-northeast-1\",\"cn-north-1\",\"cn-northwest-1\",\"eu-central-1\",\"eu-west-1\",\"eu-west-2\",\"eu-west-3\",\"eu-north-1\",\"sa-east-1\"]"}}}',$json);
      
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  /**
   * test_fetchFilestoreTypes_all_Good_Should_return_true
   *
   * @return void
   */
  public function test_fetchFilestoreTypes_all_Good_Should_return_true(){

    $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('listLocations'))
      ->getMock();
      
	 $mockfilestore->method('listLocations')
		->willReturn(array('filestoreTypes' => array('Dropbox','Email','FTP')));
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("query{
      fetchFilestoreTypes{
        status message types
      }
    }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"data":{"fetchFilestoreTypes":{"status":true,"message":"List of filestore types","types":"[\"Dropbox\",\"Email\",\"FTP\"]"}}}',$json);
      
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  /**
   * test_fetchFilestoreTypes_when_empty_Should_return_false
   *
   * @return void
   */
  public function test_fetchFilestoreTypes_when_empty_Should_return_false(){
   $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('listLocations'))
      ->getMock();
      
	 $mockfilestore->method('listLocations')
		->willReturn('');
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("query{
      fetchFilestoreTypes{
        status message types
      }
    }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"errors":[{"message":"Sorry unable to find the filestore types","status":false}]}',$json);
      
    $this->assertEquals(400, $response->getStatusCode());
  }
  
  /**
   * test_addFTPInstance_all_good_Should_return_true
   *
   * @return void
   */
  public function test_addFTPInstance_all_good_Should_return_true(){
   $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('addItem'))
      ->getMock();
      
	 $mockfilestore->method('addItem')
		->willReturn('123456789');
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("mutation{
      addFTPInstance(input : {
          serverName: \"testGql\"
          hostName: \"100.100.100.100\"
          userName: \"testGql\"
          password: \"testGql\"    
      }){
        status message id
      }
    }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"data":{"addFTPInstance":{"status":true,"message":"FTP Instance is created successfully","id":"123456789"}}}',$json);
      
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  /**
   * test_addFTPInstance_When_required_param_not_sent_Should_return_false
   *
   * @return void
   */
  public function test_addFTPInstance_When_required_param_not_sent_Should_return_false(){
   $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('addItem'))
      ->getMock();
      
	 $mockfilestore->method('addItem')
		->willReturn('123456789');
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("mutation{
      addFTPInstance(input : {
          serverName: \"testGql\"
          userName: \"testGql\"
          password: \"testGql\"    
      }){
        status message id
      }
    }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"errors":[{"message":"Field addFTPInstanceInput.hostName of required type String! was not provided.","status":false}]}',$json);
      
    $this->assertEquals(400, $response->getStatusCode());
  }
  
  /**
   * test_addFTPInstance_when_return_is_as_null_Should_return_false
   *
   * @return void
   */
  public function test_addFTPInstance_when_return_is_as_null_Should_return_false(){
   $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('addItem'))
      ->getMock();
      
	 $mockfilestore->method('addItem')
		->willReturn(null);
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("mutation{
      addFTPInstance(input : {
          serverName: \"testGql\"
          hostName: \"100.100.100.100\"
          userName: \"testGql\"
          password: \"testGql\"    
      }){
        status message id
      }
    }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"errors":[{"message":"Sorry unable to create FTP Instance","status":false}]}',$json);
      
    $this->assertEquals(400, $response->getStatusCode());
  }

  public function test_addS3Bucket_all_good_Should_return_true(){
   $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('addItem'))
      ->getMock();
      
	 $mockfilestore->method('addItem')
		->willReturn('123456789');
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("mutation{
        addS3Bucket(input : {
            name: \"testGql\"
            bucketName: \"100.100.100.100\"
            AWSRegion: \"us-east-2\"
            AWSAccessKey: \"testGql\"    
            AWSSecret: \"12345\"
        }){
          status message id
        }
      }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"data":{"addS3Bucket":{"status":true,"message":"S3 Instance is created successfully","id":"123456789"}}}',$json);
      
    $this->assertEquals(200, $response->getStatusCode());
  }
    
  /**
   * test_addS3Bucket_When_required_param_not_sent_Should_return_false
   *
   * @return void
   */
  public function test_addS3Bucket_When_required_param_not_sent_Should_return_false(){
   $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('addItem'))
      ->getMock();
      
	 $mockfilestore->method('addItem')
		->willReturn('123456789');
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("mutation{
      addS3Bucket(input : {
          name: \"testGql\"
      }){
        status message id
      }
    }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"errors":[{"message":"Field addS3BucketInput.bucketName of required type String! was not provided.","status":false}]}',$json);
      
    $this->assertEquals(400, $response->getStatusCode());
  }

  public function test_addS3Bucket_when_return_is_null_Should_return_false(){
   $mockfilestore = $this->getMockBuilder(\FreePBX\modules\filestore\Filestore::class)
		->disableOriginalConstructor()
		->disableOriginalClone()
		->setMethods(array('addItem'))
      ->getMock();
      
	 $mockfilestore->method('addItem')
		->willReturn(null);
    
   self::$freepbx->PKCS->setFileStoreObj($mockfilestore); 

    $response = $this->request("mutation{
        addS3Bucket(input : {
            name: \"testGql\"
            bucketName: \"100.100.100.100\"
            AWSRegion: \"us-east-2\"
            AWSAccessKey: \"testGql\"    
            AWSSecret: \"12345\"
        }){
          status message id
        }
      }");
      
    $json = (string)$response->getBody();
    $this->assertEquals('{"errors":[{"message":"Sorry unable to create S3 Instance","status":false}]}',$json);
      
    $this->assertEquals(400, $response->getStatusCode());
  }
}