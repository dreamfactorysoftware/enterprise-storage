<?php
namespace DreamFactory\Tests\Library\Enterprise\Storage;

use DreamFactory\Library\Enterprise\Storage\Enums\EnterprisePaths;
use DreamFactory\Library\Enterprise\Storage\Interfaces\PlatformStructureResolverLike;
use DreamFactory\Library\Enterprise\Storage\Provider;

class ProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @type string */
    protected $_hostname = 'sandman.cloud.dreamfactory.com';
    /** @type string */
    protected $_storageId = '85ff421dea8a74848f338304891c413d690842cdd2e3b73f6ea46ef00d74ccfa';
    /** @type string */
    protected $_zone = 'ec2.us-east-1';
    /** @type string */
    protected $_partition = '85';
    /** @type string */
    protected $_mountPoint = '/data';
    /** @type PlatformStructureResolverLike */
    protected $_provider;

    public function testGetStoragePath()
    {
        $_storagePath = $this->_provider->getStoragePath();

        $this->assertEquals(
            $_storagePath,
            $this->_mountPoint .
            DIRECTORY_SEPARATOR .
            EnterprisePaths::STORAGE_PATH .
            DIRECTORY_SEPARATOR .
            $this->_zone .
            DIRECTORY_SEPARATOR .
            $this->_partition .
            DIRECTORY_SEPARATOR .
            $this->_storageId
        );
    }

    public function testGetPrivatePath()
    {
        $_privatePath = $this->_provider->getPrivatePath();

        $this->assertEquals(
            $_privatePath,
            $this->_mountPoint .
            DIRECTORY_SEPARATOR .
            EnterprisePaths::STORAGE_PATH .
            DIRECTORY_SEPARATOR .
            $this->_zone .
            DIRECTORY_SEPARATOR .
            $this->_partition .
            DIRECTORY_SEPARATOR .
            $this->_storageId
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $_provider = new Provider();
        $_provider->initialize( $this->_hostname, $this->_mountPoint );
    }

}
