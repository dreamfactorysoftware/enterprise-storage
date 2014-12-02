<?php
namespace DreamFactory\Library\Enterprise\Storage;

use DreamFactory\Library\Enterprise\Storage\Interfaces\FileSystemLike;
use DreamFactory\Library\Enterprise\Storage\Interfaces\StorageAdapterLike;

/**
 * An abstract class that provides a file system interface to a mounted service, device, or file system
 */
class MountPoint
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
     * @param array $mountOptions Any options to pass to mount
     *
     * @return FileSystemLike
     */
    public function mountFileSystem( array $mountOptions = array() )
    {
        if ( empty( $this->_adapter ) )
        {
            throw new \RuntimeException( 'No storage adapter set.' );
        }

        if ( !$this->unmountFileSystem() )
        {
            throw new \RuntimeException( 'Unable to unmount currently mounted adapter.' );
        }

        return
            $this->_fileSystem = $this->_adapter->mount( $mountOptions );
    }

    /**
     * Unmount the currently mounted adapter
     */
    public function unmountFileSystem()
    {
        if ( $this->_fileSystem )
        {
            if ( !$this->_adapter->unmount() )
            {
                return false;
            }

            $this->_fileSystem = null;
        }

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

    /**
     * @param StorageAdapterLike $adapter
     *
     * @return $this
     */
    public function setAdapter( StorageAdapterLike $adapter )
    {
        $this->_adapter = $adapter;

        return $this;
    }

}