<?php
namespace DreamFactory\Library\Enterprise\Storage;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterpriseDefaults;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterpriseKeys;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterprisePaths;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterpriseResources;
use DreamFactory\Library\Enterprise\Storage\Interfaces\PlatformStorageResolverLike;
use DreamFactory\Library\Utility\Exceptions\FileSystemException;
use DreamFactory\Library\Utility\FileSystem;
use DreamFactory\Library\Utility\IfSet;

/**
 * DreamFactory Enterprise(tm) and Services Platform Storage Resolver
 *
 * The default functionality (Resolver::$partitioned is set to TRUE) of this resolver is to provide partitioned
 * layout paths for the hosted storage area. The structure generated is as follows:
 *
 * /mount_point                             <----- Mount point/absolute path of storage area
 *      /storage                            <----- Root directory of hosted storage
 *          /zone                           <----- The storage zones (ec2.us-east-1, ec2.us-west-1, local, etc.)
 *              /[00-ff]                    <----- The first two bytes of hashes within
 *                  /instance-hash          <----- The hash of the instance name
 *
 * Example paths:
 *
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/applications
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/plugins
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private/.cache
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private/config
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private/scripts
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private/scripts.user
 *
 * This class also provides path mapping for non-hosted DSPs as well. Set the $partitioned property to FALSE
 * for this functionality. The structure will use the installation path as a mount point.
 *
 * The structure is as follows:
 *
 * install_root/storage/
 * install_root/storage/applications
 * install_root/storage/plugins
 * install_root/storage/.private
 * install_root/storage/.private/config
 * install_root/storage/.private/.cache
 * install_root/storage/.private/scripts
 * install_root/storage/.private/scripts.user
 */
class Resolver extends EnterprisePaths implements PlatformStorageResolverLike
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /** @inheritdoc */
    const DEBUG_ZONE_URL = 'https://ec2.us-east-1.amazonaws.com';
    /** @inheritdoc */
    const DEBUG_ZONE_NAME = 'ec2.us-east-1';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type bool If true, structure resolved will be laid out in a partitioned manner
     */
    protected $_partitioned = true;
    /**
     * @type string This instance's storage ID
     */
    protected $_storageId;
    /**
     * @type string This instance's host name
     */
    protected $_hostname;
    /**
     * @type string The absolute storage root path
     */
    protected $_mountPoint = null;
    /**
     * @type string The deployment zone name/id
     */
    protected $_zone = null;
    /**
     * @type string The storage partition name/id
     */
    protected $_partition = null;
    /**
     * @type CacheProvider
     */
    protected $_cache;
    /**
     * @type array Array of calculated paths
     */
    protected $_paths;
    /**
     * @type ResourceLocator[] An array of resource locators
     */
    protected $_locators = array();

    //*************************************************************************
    //* Methods
    //*************************************************************************

    public function __construct( $hostname, $mountPoint = null, $installRoot = null )
    {
    }

    /** @inheritdoc */
    public function initialize( $hostname, $mountPoint = null, $installRoot = null )
    {
        //  Create our default services
        $this->_hostname = $hostname;
        $this->_zone = $this->_partition = null;

        $installRoot = $installRoot ?: $this->_locateInstallRoot();

        $this->_paths = array(
            EnterpriseKeys::INSTALL_ROOT_KEY       => $installRoot,
            EnterpriseKeys::SYSTEM_CONFIG_PATH_KEY => $installRoot . static::CONFIG_PATH,
            EnterpriseKeys::MOUNT_POINT_KEY        => $this->_mountPoint = $this->_mountPoint ?: $mountPoint,
        );

        false !== stripos( $hostname, EnterpriseDefaults::PLATFORM_VIRTUAL_SUBDOMAIN ) || $hostname .= EnterpriseDefaults::PLATFORM_VIRTUAL_SUBDOMAIN;

        $this->_storageId = hash( static::DATA_STORAGE_HASH, $hostname );

        //  Check the cache
//        if ( false !== ( $_data = $this->_getCache()->fetch( $this->_storageId ) ) )
//        {
//            list( $this->_mountPoint, $this->_zone, $this->_partition, $this->_paths ) = $_data;
//
//            return;
//        }

        //  Find the zone for this host
        if ( false === ( $this->_zone = $this->_locateZone( static::DEBUG_ZONE_NAME ) ) )
        {
            //  Local installation
            $this->_mountPoint = $this->_paths[EnterpriseKeys::MOUNT_POINT_KEY] = $this->_paths[EnterpriseKeys::INSTALL_ROOT_KEY];
        }

        //  Find the partition
        $this->_partition = $this->_locatePartition( $this->_storageId );

        //  Set the paths
        $this->_createStructure(
            $this->_mountPoint,
            $this->_mountPoint . static::STORAGE_PATH . DIRECTORY_SEPARATOR . $this->getStorageKey()
        );
    }

    /**
     * Registers a resource locator with the resolver
     *
     * @param string   $resource
     * @param callable $locator
     *
     * @return $this
     */
    public
    function registerLocator( $resource, $locator )
    {
        if ( !is_callable( $locator ) )
        {
            throw new \InvalidArgumentException( 'The $locator provided must be callable.' );
        }

        if ( !EnterpriseResources::contains( $resource ) )
        {
            throw new \InvalidArgumentException( 'The $resource "' . $resource . '" is not valid.' );
        }

        $this->_locators[$resource] = $locator;

        return $this;
    }

    /**
     * Find the zone of this cluster
     *
     * @param string $zone
     *
     * @return bool|mixed|null
     */
    protected
    function _locateZone( $zone = null )
    {
        //  Use location service if registered
        if ( isset( $_locators[EnterpriseResources::ZONE] ) )
        {
            return call_user_func( $_locators[EnterpriseResources::ZONE], $zone, $this->_partitioned );
        }

        //  Zones only apply to partitioned layouts
        if ( !$this->_partitioned )
        {
            return false;
        }

        //  If a zone was passed in, use it
        if ( !empty( $zone ) )
        {
            return $zone;
        }

        //  No zone... :(
        return false;
    }

    /**
     * Find the zone of this cluster
     *
     * @param string $storageId
     *
     * @return bool|string The partition or false if no partition available/used/needed
     */
    protected
    function _locatePartition( $storageId )
    {
        //  Use location service if registered
        if ( isset( $_locators[EnterpriseResources::PARTITION] ) )
        {
            return call_user_func( $_locators[EnterpriseResources::PARTITION], $storageId, $this->_partitioned );
        }

        //  Partitions only apply to partitioned layouts
        if ( !$this->_partitioned )
        {
            return false;
        }

        return substr( $storageId, 0, 2 );
    }

    /**
     * Locate the base platform directory
     *
     * @param string $start
     *
     * @return string
     */
    protected
    function _locateInstallRoot( $start = null )
    {
        $_path = $start ?: getcwd();

        //  Use location service if registered
        if ( isset( $_locators[EnterpriseResources::INSTALL_ROOT] ) )
        {
            return call_user_func( $_locators[EnterpriseResources::INSTALL_ROOT], $_path, $this->_partitioned );
        }

        while ( true )
        {
            if ( file_exists( $_path . DIRECTORY_SEPARATOR . 'composer.json' ) && is_dir( $_path . DIRECTORY_SEPARATOR . 'vendor' ) )
            {
                break;
            }

            $_path = dirname( $_path );

            if ( empty( $_path ) || $_path == DIRECTORY_SEPARATOR )
            {
                throw new \RuntimeException( 'Base platform installation path not found.' );
            }
        }

        return $_path;
    }

    /**
     * Give a storage path, set up the default sub paths...
     *
     * @param string $mountPoint
     *
     * @throws FileSystemException
     * @return array
     */
    protected
    function _createStructure( $mountPoint )
    {
        $_storagePath = rtrim( $mountPoint . static::STORAGE_PATH . DIRECTORY_SEPARATOR . $this->getStorageKey(), DIRECTORY_SEPARATOR );
        $_privatePath = rtrim( $mountPoint . static::STORAGE_PATH . DIRECTORY_SEPARATOR . $this->getPrivateStorageKey(), DIRECTORY_SEPARATOR );

        $this->_paths = array_merge(
            is_array( $this->_paths ) ? $this->_paths : array(),
            array(
                Enterprisekeys::STORAGE_PATH_KEY         => $_storagePath,
                Enterprisekeys::PRIVATE_STORAGE_PATH_KEY => $_privatePath,
                Enterprisekeys::APPLICATIONS_PATH_KEY    => $_storagePath . static::APPLICATIONS_PATH,
                Enterprisekeys::PLUGINS_PATH_KEY         => $_storagePath . static::PLUGINS_PATH,
                Enterprisekeys::LOCAL_CONFIG_PATH_KEY    => $_privatePath . static::CONFIG_PATH,
                Enterprisekeys::PRIVATE_CONFIG_PATH_KEY  => $_privatePath . static::CONFIG_PATH,
                Enterprisekeys::SCRIPTS_PATH_KEY         => $_privatePath . static::SCRIPTS_PATH,
                Enterprisekeys::USER_SCRIPTS_PATH_KEY    => $_privatePath . static::USER_SCRIPTS_PATH,
            )
        );

        // Ensures the directories in the structure are created and available.
        // Only template items that are arrays are processed.
        foreach ( $this->_paths as $_id => $_path )
        {
            if ( !FileSystem::ensurePath( $_path ) )
            {
                throw new FileSystemException( 'Unable to create storage path "' . $_path . '"' );
            }
        }

        //  Cache it
        $this->_getCache()->save(
            $this->_storageId,
            array($mountPoint, $this->_zone, $this->_partition, $this->_paths),
            static::DEFAULT_CACHE_TTL
        );

        return $this->_paths;
    }

    /**
     * Constructs a virtual platform path
     *
     * @param string $base            The base path to start with
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @throws FileSystemException
     * @return string
     */
    protected
    function _buildPath( $base, $append = null, $createIfMissing = true, $includesFile = false )
    {
        static $_cache = null;

        !$_cache && $_cache = $this->_getCache();

        $_appendage = ( $append ? DIRECTORY_SEPARATOR . ltrim( $append, DIRECTORY_SEPARATOR ) : null );

        //	Make a cache tag that includes the requested path...
        $_cacheKey = hash( static::DATA_STORAGE_HASH, $base . $_appendage );

        $_path = $_cache->fetch( $_cacheKey );

        if ( empty( $_path ) )
        {
            $_path = realpath( $base );
            $_checkPath = $_path . $_appendage;

            if ( $includesFile )
            {
                $_checkPath = dirname( $_checkPath );
            }

            if ( $createIfMissing && !is_dir( $_checkPath ) )
            {
                if ( false === @\mkdir( $_checkPath, 0777, true ) )
                {
                    throw new FileSystemException( 'File system error creating directory: ' . $_checkPath );
                }
            }

            $_path .= $_appendage;

            //	Store path for next time...
            $_cache->save( $_cacheKey, $_path, static::DEFAULT_CACHE_TTL );
        }

        return $_path;
    }

    /**
     * @return CacheProvider
     */
    protected
    function _getCache()
    {
        if ( empty( $this->_storageId ) )
        {
            throw new \LogicException( 'Cannot create a cache file without a storage id.' );
        }

        return
            $this->_cache = $this->_cache
                ?: new FilesystemCache(
                    sys_get_temp_dir() . DIRECTORY_SEPARATOR .
                    '.dreamfactory' . DIRECTORY_SEPARATOR .
                    '.compiled' . DIRECTORY_SEPARATOR .
                    sha1( $this->_storageId ), static::DEFAULT_CACHE_EXTENSION
                );
    }

    /**
     * Constructs the virtual storage path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public
    function getStoragePath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_paths[EnterpriseKeys::STORAGE_PATH_KEY], $append, $createIfMissing, $includesFile );
    }

    /**
     * Constructs the virtual private path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public
    function getPrivatePath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_paths[EnterpriseKeys::PRIVATE_STORAGE_PATH_KEY], $append, $createIfMissing, $includesFile );
    }

    /**
     * Returns the platform's local configuration path, not the platform's config path in the root
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public
    function getPrivateConfigPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_paths[EnterpriseKeys::PRIVATE_CONFIG_PATH_KEY], $append, $createIfMissing, $includesFile );
    }

    /**
     * Returns the platform configuration path, in the root
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public
    function getConfigPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_paths[EnterpriseKeys::SYSTEM_CONFIG_PATH_KEY], $append, $createIfMissing, $includesFile );
    }

    /**
     * Constructs the virtual plugins path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public
    function getPluginsPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_paths[EnterpriseKeys::PLUGINS_PATH_KEY], $append, $createIfMissing, $includesFile );
    }

    /**
     * Constructs the virtual applications path
     *
     * @param string $append          What to append to the base
     * @param bool   $createIfMissing If true and final directory does not exist, it is created.
     * @param bool   $includesFile    If true, the $base includes a file and is not just a directory
     *
     * @return string
     */
    public
    function getApplicationsPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_paths[EnterpriseKeys::APPLICATIONS_PATH_KEY], $append, $createIfMissing, $includesFile );
    }

    /**
     * @param string $legacyKey
     *
     * @return string The zone/partition/id that make up the new public storage key. Local installs return null
     */
    public
    function getStorageKey( $legacyKey = null )
    {
        $_storageKey = null;

        if ( $this->_partitioned )
        {
            $_storageKey = $this->_zone . DIRECTORY_SEPARATOR .
                $this->_partition . DIRECTORY_SEPARATOR .
                $this->_storageId;
        }

        if ( empty( $_storageKey ) )
        {
            $_storageKey = $legacyKey;
        }

        return $_storageKey;
    }

    /**
     * @param string $legacyKey
     *
     * @return bool|string The zone/partition/id/tag that make up the new private storage key
     */
    public
    function getPrivateStorageKey( $legacyKey = null )
    {
        return ltrim(
            $this->getStorageKey( $legacyKey ) . DIRECTORY_SEPARATOR . ltrim( static::PRIVATE_STORAGE_PATH, DIRECTORY_SEPARATOR ),
            DIRECTORY_SEPARATOR
        );
    }

    /** @inheritdoc */
    public
    function getStorageId()
    {
        return $this->_storageId;
    }

    /**
     * @return boolean
     */
    public
    function isPartitioned()
    {
        return $this->_partitioned;
    }

    /**
     * @param boolean $partitioned
     *
     * @return Resolver
     */
    public
    function setPartitioned( $partitioned )
    {
        $this->_partitioned = $partitioned;

        return $this;
    }

    /**
     * @param string $key The path to get. Use the LocalStorageTypes constants please.
     *
     * @return string Returns the path for $key or null if not yet set
     */
    public
    function getPath( $key )
    {
        if ( !EnterpriseKeys::contains( $key ) )
        {
            throw new \InvalidArgumentException( 'The path type "' . $key . '" is invalid.' );
        }

        return IfSet::get( $this->_paths, $key );
    }

    /**
     * @return string
     */
    public
    function getHostname()
    {
        return $this->_hostname;
    }

    /**
     * @param string $hostname
     *
     * @return Resolver
     */
    public
    function setHostname( $hostname )
    {
        $this->_hostname = $hostname;

        return $this;
    }

}