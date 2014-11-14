<?php
namespace DreamFactory\Library\Storage\Enterprise\Interfaces;

/**
 * A provider of storage to a hosted cluster
 */
interface ClusterStorageProviderLike extends StorageProviderLike
{
    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Returns the platform's local configuration path, not the platform's config path in the root
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public function getLocalConfigPath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Returns the library configuration path, not the platform's config path in the root
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public function getLibraryConfigPath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Returns the library template configuration path, not the platform's config path in the root
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public function getLibraryTemplatePath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Returns the platform configuration path, in the root
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public function getPlatformConfigPath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Constructs the private snapshot path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public function getSnapshotPath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Constructs the swagger path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     * @deprecated in v1.8.2, Swagger data is now stored in the database and not on disk
     */
    public function getSwaggerPath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Constructs the plugins path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public function getPluginsPath( $append = null, $createIfMissing = true, $includesFile = false );

    /**
     * Constructs the applications path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public function getApplicationsPath( $append = null, $createIfMissing = true, $includesFile = false );

}
