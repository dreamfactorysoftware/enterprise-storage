<?php
namespace DreamFactory\Tests\Library\Enterprise\Storage;

use DreamFactory\Library\Enterprise\Storage\Enums\EnterprisePaths;
use DreamFactory\Library\Enterprise\Storage\Interfaces\PlatformStorageResolverLike;
use DreamFactory\Library\Enterprise\Storage\Resolver;

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
    /** @type string */
    protected $_installRoot = '/opt/dreamfactory/dsp/dsp-core';
    /** @type PlatformStorageResolverLike */
    protected $_resolver;

    /**
     * @covers \DreamFactory\Library\Enterprise\Storage\Resolver::getStoragePath()
     */
    public function testGetStoragePath()
    {
        $_storagePath = $this->_getResolver( true, $this->_hostname, $this->_mountPoint, $this->_installRoot )->getStoragePath();

        $_testPath =
            $this->_mountPoint .
            EnterprisePaths::STORAGE_PATH .
            DIRECTORY_SEPARATOR .
            $this->_zone .
            DIRECTORY_SEPARATOR .
            $this->_partition .
            DIRECTORY_SEPARATOR .
            $this->_storageId;

        $this->assertEquals( $_testPath, $_storagePath );

        $this->_mountPoint = '/opt/dreamfactory/dsp/dsp-core';
        $_storagePath = $this->_getResolver( false, $this->_hostname, $this->_installRoot, $this->_installRoot )->getStoragePath();
        $_testPath = $this->_installRoot . EnterprisePaths::STORAGE_PATH;

        $this->assertEquals( $_storagePath, $_testPath );
    }

    /**
     * @covers \DreamFactory\Library\Enterprise\Storage\Resolver::getPrivatePath()
     */
    public function testGetPrivatePath()
    {
        $this->_mountPoint = '/data';
        $_privatePath = $this->_getResolver( true, $this->_hostname, $this->_mountPoint, $this->_installRoot )->getPrivatePath();

        $this->assertEquals(
            $_privatePath,
            $this->_mountPoint . EnterprisePaths::STORAGE_PATH . DIRECTORY_SEPARATOR .
            $this->_zone . DIRECTORY_SEPARATOR .
            $this->_partition . DIRECTORY_SEPARATOR .
            $this->_storageId . EnterprisePaths::PRIVATE_STORAGE_PATH
        );

        $this->_mountPoint = '/opt/dreamfactory/dsp/dsp-core';
        $_privatePath = $this->_getResolver( false, $this->_hostname, $this->_installRoot, $this->_installRoot )->getPrivatePath();
        $_testPath = $this->_installRoot . EnterprisePaths::STORAGE_PATH . EnterprisePaths::PRIVATE_STORAGE_PATH;

        $this->assertEquals( $_privatePath, $_testPath );
    }

    /**
     * @covers \DreamFactory\Library\Enterprise\Storage\Resolver::setPartitioned()
     * @covers \DreamFactory\Library\Enterprise\Storage\Resolver::isPartitioned()
     * @covers \DreamFactory\Library\Enterprise\Storage\Resolver::initialize()
     *
     * @param bool   $partitionedLayout
     * @param string $hostname
     * @param string $mountPoint
     * @param string $installRoot
     *
     * @return Resolver
     */
    protected function _getResolver( $partitionedLayout = false, $hostname = null, $mountPoint = null, $installRoot = null )
    {
        $_resolver = new Resolver();
        $_resolver->setPartitioned( $partitionedLayout );

        $this->assertEquals( $partitionedLayout, $_resolver->isPartitioned() );

        $_resolver->initialize( $hostname ?: $this->_hostname, $mountPoint ?: $this->_mountPoint, $installRoot ?: $this->_installRoot );

        return $this->_resolver = $_resolver;
    }
}
