<?php
namespace DreamFactory\Library\Enterprise\Storage\Interfaces;

/**
 * A service that can resolve DSP/DFE structure details
 */
interface PlatformStructureResolverLike extends StructureResolverLike
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int default number of seconds to keep cached provider information
     */
    const DEFAULT_CACHE_TTL = 3600;
    /**
     * @type string The extension to use for cache files
     */
    const DEFAULT_CACHE_EXTENSION = '.cache';

    //******************************************************************************
    //* Methods
    //******************************************************************************

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
