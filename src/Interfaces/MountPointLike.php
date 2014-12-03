<?php
namespace DreamFactory\Library\Enterprise\Storage\Interfaces;

/**
 * An object that acts like a mount point
 */
interface MountPointLike
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param StorageAdapterLike $adapter   The adapter for this mount point
     * @param bool               $autoMount If true, the adapter will be mounted
     */
    public function __construct( StorageAdapterLike $adapter, $autoMount = false );

    /**
     * Mounts the storage service, device, or file system
     *
     * @param array $options Any options required by the mounter
     *
     * @return FileSystemLike Returns a file system object to manipulate the mounted device
     */
    public function mountAdapter( array $options = array() );

    /**
     * Unmount the currently mounted adapter
     */
    public function unmountAdapter();

    /**
     * @return StorageAdapterLike
     */
    public function getAdapter();

    /**
     * @return FileSystemLike
     */
    public function getFileSystem();
}
