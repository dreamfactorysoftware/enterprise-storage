<?php
namespace DreamFactory\Library\Enterprise\Storage\Interfaces;

use DreamFactory\Library\Utility\Interfaces\ResourceLocatorLike;

/**
 * Something that can locate storage resources
 */
interface StorageLocatorLike extends ResourceLocatorLike
{
    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Locates a file resource
     *
     * @param string $fileName The file to locate
     * @param array  $options
     *
     * @return string
     */
    public function locateFile( $fileName, $options = array() );

    /**
     * Locates a path resource
     *
     * @param string $path The path to locate
     * @param array  $options
     *
     * @return string
     */
    public function locatePath( $path, $options = array() );
}
