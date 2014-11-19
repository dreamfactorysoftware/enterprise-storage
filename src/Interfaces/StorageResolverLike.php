<?php
namespace DreamFactory\Library\Enterprise\Storage\Interfaces;

/**
 * Something that can resolve enterprise storage structure
 */
interface StorageResolverLike
{
    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Given a host name and an optional mount point, derive the storage keys
     * and directory structure of the storage space for $hostname
     *
     * @param string $hostname    The storage owner's host name
     * @param string $mountPoint  Optional storage mount point
     * @param string $installRoot Absolute path to installation root
     *
     * @return string
     */
    public function initialize( $hostname, $mountPoint = null, $installRoot = null );

    /**
     * Returns the owner's storage id
     *
     * @return string
     */
    public function getStorageId();

    /**
     * Returns the absolute storage path, sans trailing slash.
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string The instance's absolute storage path, sans trailing slash
     */
    public function getStoragePath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Returns the absolute private storage path, sans trailing slash.
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string The instance's absolute private path, sans trailing slash
     */
    public function getPrivatePath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * @param string $legacyKey The instance's prior key, if one. Will be used as a default if
     *                          there is a problem deriving the storage id.
     *
     * @return bool|string The instance's storage key
     */
    public function getStorageKey( $legacyKey = null );

    /**
     * @param string $legacyKey The instance's prior key, if one. Will be used as a default if
     *                          there is a problem deriving the storage id.
     *
     * @return bool|string The instance's private storage key
     * @deprecated in v1.8.2, to be removed eventually. Private storage is now always under storage path
     */
    public function getPrivateStorageKey( $legacyKey = null );
}
