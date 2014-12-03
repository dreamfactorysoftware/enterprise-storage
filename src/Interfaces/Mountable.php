<?php
namespace DreamFactory\Library\Enterprise\Storage\Interfaces;

/**
 * An object that can mount/unmount a storage system
 */
interface Mountable
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Mounts the storage service, device, or file system
     *
     * @param array $options Any options required by the mounter
     *
     * @return FileSystemLike Returns a file system object to manipulate the mounted device
     */
    public function mount( array $options = array() );

    /**
     * Unmounts the previously mounted thing
     *
     * @return bool True if unmounted, false if busy or otherwise unmounted.
     */
    public function unmount();
}
