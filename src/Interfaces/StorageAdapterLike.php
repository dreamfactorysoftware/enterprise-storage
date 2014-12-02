<?php
namespace DreamFactory\Library\Enterprise\Storage\Interfaces;

/**
 * A light-weight interface that can be relied on to provide information about a storage service or device.
 */
interface StorageAdapterLike
{
    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Mounts the storage service, device, or file system
     *
     * @param array $options Any options required by the mounter
     *
     * @return FileSystemLike Returns a resolver that can be use to interact with the mount
     */
    public function mount( array $options = array() );

    /**
     * Unmounts the previously mounted thing
     *
     * @return bool True if unmounted, false if busy or otherwise unmounted.
     */
    public function unmount();

}
