<?php
namespace DreamFactory\Library\Enterprise\Storage;

use DreamFactory\Library\Enterprise\Storage\Interfaces\FileSystemLike;
use DreamFactory\Library\Enterprise\Storage\Interfaces\MountPointLike;
use DreamFactory\Library\Enterprise\Storage\Interfaces\StorageAdapterLike;

/**
 * An abstract class that provides a file system interface to a mounted service, device, or file system
 */
class ClusterFileSystem extends Gaufre
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type StorageAdapterLike
     */
    protected $_adapter;
    /**
     * @type FileSystemLike
     */
    protected $_fileSystem;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @param StorageAdapterLike $adapter   The adapter for this mount point
     * @param bool               $autoMount If true, the adapter will be mounted
     */
    public function __construct( StorageAdapterLike $adapter, $autoMount = false )
    {
        $this->_adapter = $adapter;

        if ( $autoMount )
        {
            $this->mountAdapter();
        }
    }

    /**
     * Mounts the storage service, device, or file system
     *
     * @param array $options Any options required by the mounter
     *
     * @return FileSystemLike Returns a file system object to manipulate the mounted device
     */
    public function mountAdapter( array $options = array() )
    {
        if ( empty( $this->_adapter ) )
        {
            throw new \LogicException( 'No storage adapter set.' );
        }

        if ( !$this->unmountAdapter() )
        {
            throw new \RuntimeException( 'Unable to unmount currently mounted adapter.' );
        }

        return
            $this->_fileSystem = $this->_adapter->mount( $options );
    }

    /**
     * Unmount the currently mounted adapter
     */
    public function unmountAdapter()
    {
        if ( $this->_fileSystem )
        {
            if ( !$this->_adapter->unmount() )
            {
                return false;
            }
        }

        $this->_fileSystem = null;

        return true;
    }

    /**
     * @return StorageAdapterLike
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * @return FileSystemLike
     */
    public function getFileSystem()
    {
        return $this->_fileSystem;
    }
}