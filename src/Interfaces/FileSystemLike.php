<?php
namespace DreamFactory\Library\Enterprise\Storage\Interfaces;

/**
 * Something that acts like a file system
 */
interface FileSystemLike
{
    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @return string Returns the present working directory
     */
    public function pwd();

    /**
     * @param string $path The path to which to set present working directory
     *
     * @return bool True if successful
     */
    public function chdir( $path );

    /**
     * Attempts to create the directory specified by pathname.
     *
     * @param string   $pathname  The directory path
     * @param int      $mode      [optional] The mode is 0777 by default, which means the widest possible access. For more information on
     *                            modes, read the details on the chmod page. This is ignored on Windows.
     * @param bool     $recursive [optional]  Allows the creation of nested directories specified in the pathname. Default to false.
     * @param resource $context   [optional] The context of the resource
     *
     * @return bool true on success or false on failure.
     */
    public function mkdir( $pathname, $mode = 0777, $recursive = false, $context = null );

    /**
     * Removes a directory
     *
     * @param string   $dirname Path to the directory.
     * @param resource $context [optional] The context of the resource
     *
     * @return bool True on success or false on failure.
     */
    public function rmdir( $dirname, $context = null );

    /**
     * List files and directories inside the specified path
     *
     * @param string   $directory     The directory that will be scanned
     * @param int      $sorting_order [optional] By default, the sorted order is alphabetical in ascending order. If the optional
     *                                sorting_order is set to non-zero, then the sort order is alphabetical in descending order.
     * @param resource $context       [optional] For a description of the context parameter, refer to the streams section of the PHP
     *                                manual.
     *
     * @return array An array of file names on success, or false on failure. If directory is not a directory, then boolean false is
     *               returned, and an error of level E_WARNING is generated.
     */
    public function scandir( $directory, $sorting_order = null, $context = null );

    /**
     * @param string $filename The name of the file
     *
     * @return \FilesystemIterator
     */
    public function getFile($filename);

    /**
     * Reads entire file into a string
     *
     * @param string   $filename The name of the file to read
     * @param int      $flags    [optional]  Optional flags concerning the operation. See {@see file_get_contents} for these values
     * @param resource $context  [optional]  A valid context resource created with {@see stream_context_create}. If you don't need to use a
     *                           custom context, you may omit this parameter or set to null
     * @param int      $offset   [optional]  The offset from which to start reading
     * @param int      $maxlen   [optional]  Maximum length of data read. The default is to read until end of file is reached.
     *
     * @return string The function returns the read data or false on failure.
     */
    public function getContents( $filename, $flags = null, $context = null, $offset = null, $maxlen = null );

    /**
     * @param string   $filename The path to the file in which to write $contents
     * @param mixed    $contents The data to write. This can be a string, an array or a stream resource. If $contents is a stream resource,
     *                           the remaining buffer of that stream will be copied to the specified file. This is similar with using
     *                           stream_copy_to_stream. You can also specify the $contents parameter as a single dimension array. This is
     *                           equivalent to FileSystemLike::putContents($filename, implode('', $array))
     * @param int      $flags    [optional] Flags concerning the operation. See the PHP {@see file_put_contents} call for more
     *                           information.
     * @param resource $context  [optional] A valid context resource created with {@see stream_context_create}.
     *
     * @return mixed
     */
    public function putContents( $filename, $contents, $flags = 0, $context = null );
}
