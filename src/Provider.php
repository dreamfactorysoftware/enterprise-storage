<?php
namespace DreamFactory\Library\Enterprise\Storage;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterpriseDefaults;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterpriseKeys;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterprisePaths;
use DreamFactory\Library\Enterprise\Storage\Interfaces\PlatformStructureResolverLike;
use DreamFactory\Library\Utility\Exceptions\FileSystemException;
use DreamFactory\Library\Utility\FileSystem;
use DreamFactory\Library\Utility\IfSet;

/**
 * DreamFactory Enterprise(tm) and Services Platform Storage Resolver
 *
 * The layout of the hosted storage area is as follows:
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
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private/config
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private/scripts
 * /data/storage/ec2.us-east-1/33/33f58e59068f021c975a1cac49c7b6818de9df5831d89677201b9c3bd98ee1ed/.private/scripts.user
 *
 * This class also provides path mapping for non-hosted DSPs as well. The directory is located in the
 * root installation path of the platform. The structure is as follows:
 *
 * /storage/
 * /storage/applications
 * /storage/plugins
 * /storage/.private
 * /storage/.private/config
 * /storage/.private/scripts
 * /storage/.private/scripts.user
 */
class Provider extends EnterprisePaths implements PlatformStructureResolverLike
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
     * @type string This instance's storage ID
     */
    protected $_storageId;
    /**
     * @type string The absolute storage root path
     */
    protected $_mountPoint;
    /**
     * @type string The deployment zone name/id
     */
    protected $_zone;
    /**
     * @type string The storage partition name/id
     */
    protected $_partition;
    /**
     * @type CacheProvider
     */
    protected $_cache;
    /**
     * @type array Array of calculated paths
     */
    protected $_paths;

    /**
     * @return bool True if the running system is an enterprise installation
     */
    public function isEnterpriseInstallation()
    {
        $_documentRoot = IfSet::get( $_SERVER, 'DOCUMENT_ROOT' );

        return
            $_documentRoot == EnterpriseDefaults::DEFAULT_DOC_ROOT && file_exists( EnterpriseDefaults::ENTERPRISE_MARKER );
    }

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /** @inheritdoc */
    public function initialize( $hostname, $mountPoint = EnterprisePaths::MOUNT_POINT )
    {
        $this->_paths = array(
            EnterpriseKeys::INSTALL_ROOT_KEY => $this->_findBasePath(),
            EnterpriseKeys::MOUNT_POINT_KEY  => $this->_mountPoint = $this->_mountPoint ?: $mountPoint,
        );

        false !== stripos( $hostname, EnterpriseDefaults::PLATFORM_VIRTUAL_SUBDOMAIN ) || $hostname .= EnterpriseDefaults::PLATFORM_VIRTUAL_SUBDOMAIN;

        $this->_storageId = hash( static::DATA_STORAGE_HASH, $hostname );

        //  Check the cache
        if ( $this->_getCache() && false !== ( $_data = $this->_cache->fetch( $this->_storageId ) ) )
        {
            list( $this->_mountPoint, $this->_zone, $this->_partition, $this->_paths ) = $_data;

            return;
        }

        $this->_partition = substr( $this->_storageId, 0, 2 );

        //  Find the zone for this host
        if ( false === ( $this->_zone = $this->_findZone( static::DEBUG_ZONE_NAME ) ) )
        {
            //  Local installation
            $this->_mountPoint = $this->_paths[EnterpriseKeys::INSTALL_ROOT_KEY];
        }

        //  Hosted
        $this->_setStoragePaths(
            $this->_mountPoint,
            $this->_mountPoint . static::STORAGE_PATH . $this->getStorageKey( null, true )
        );
    }

    /**
     * Find the zone of this cluster
     *
     * @param string $zone
     *
     * @return bool|mixed|null
     */
    protected function _findZone( $zone = null )
    {
        if ( !empty( $zone ) )
        {
            return $zone;
        }

        if ( !static::isEnterpriseInstallation() )
        {
            return false;
        }

        //  Try ec2...
        $_url = getenv( 'EC2_URL' ) ?: static::DEBUG_ZONE_URL;

        //  Not on EC2, we're something else
        if ( empty( $_url ) )
        {
            return false;
        }

        //  Get the EC2 zone of this instance from the url
        $_zone = str_ireplace( array('https://', '.amazonaws.com'), null, $_url );

        return $_zone;
    }

    /**
     * Locate the base platform directory
     *
     * @param string $start
     *
     * @return string
     */
    protected function _findBasePath( $start = null )
    {
        $_path = $start ?: getcwd();

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
    protected function _buildPath( $base, $append = null, $createIfMissing = true, $includesFile = false )
    {
        $_appendage = ( $append ? DIRECTORY_SEPARATOR . ltrim( $append, DIRECTORY_SEPARATOR ) : null );

        //	Make a cache tag that includes the requested path...
        $_cacheKey = hash( static::DATA_STORAGE_HASH, $base . $_appendage );

        $_path = $this->_cache->fetch( $_cacheKey );

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
            $this->_cache->save( $_cacheKey, $_path, static::DEFAULT_CACHE_TTL );
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
    protected function _setStoragePaths( $mountPoint )
    {
        $_storagePath = $mountPoint . static::STORAGE_PATH . $this->getStorageKey( null, true );
        $_privatePath = $_storagePath . $this->getPrivateStorageKey( null, true );

        $this->_paths = array(
            Enterprisekeys::STORAGE_PATH_KEY         => $_storagePath,
            Enterprisekeys::PRIVATE_STORAGE_PATH_KEY => $_privatePath,
            Enterprisekeys::APPLICATIONS_PATH_KEY    => $_storagePath . static::APPLICATIONS_PATH,
            Enterprisekeys::PLUGINS_PATH_KEY         => $_storagePath . static::PLUGINS_PATH,
            Enterprisekeys::LOCAL_CONFIG_PATH_KEY    => $_privatePath . static::CONFIG_PATH,
            Enterprisekeys::SCRIPTS_PATH_KEY         => $_privatePath . static::SCRIPTS_PATH,
            Enterprisekeys::USER_SCRIPTS_PATH_KEY    => $_privatePath . static::USER_SCRIPTS_PATH,
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
        $this->_cache &&
        $this->_cache->save(
            $this->_storageId,
            array($mountPoint, $this->_zone, $this->_partition, $this->_paths),
            static::DEFAULT_CACHE_TTL
        );

        return $this->_paths;
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
    public function getStoragePath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_paths[EnterpriseKeys::SCRIPTS_PATH_KEY], $append, $createIfMissing, $includesFile );
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
    public function getPrivatePath( $append = null, $createIfMissing = true, $includesFile = false )
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
    public function getLocalConfigPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->getPrivatePath( static::CONFIG_PATH ), $append, $createIfMissing, $includesFile );
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
    public function getPlatformConfigPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->_findBasePath() . static::CONFIG_PATH, $append, $createIfMissing, $includesFile );
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
    public function getSnapshotPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->getPrivatePath( static::SNAPSHOT_PATH ), $append, $createIfMissing, $includesFile );
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
    public function getPluginsPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->getStoragePath( static::PLUGINS_PATH ), $append, $createIfMissing, $includesFile );
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
    public function getApplicationsPath( $append = null, $createIfMissing = true, $includesFile = false )
    {
        return $this->_buildPath( $this->getStoragePath( static::APPLICATIONS_PATH ), $append, $createIfMissing, $includesFile );
    }

    /**
     * @param string $legacyKey
     * @param bool   $asPath If true, a leading directory separator is added to the return
     *
     * @return string The zone/partition/id that make up the new public storage key. Local installs return null
     */
    public function getStorageKey( $legacyKey = null, $asPath = false )
    {
        static $_storageKey = null;

        if ( !$_storageKey && ( empty( $this->_zone ) || empty( $this->_partition ) || empty( $this->_storageId ) ) )
        {
            return $legacyKey;
        }

        return
            $_storageKey = $_storageKey
                ?: ( $asPath ? DIRECTORY_SEPARATOR : null ) . $this->_zone .
                DIRECTORY_SEPARATOR . $this->_partition .
                DIRECTORY_SEPARATOR . $this->_storageId;
    }

    /**
     * @param string $legacyKey
     * @param bool   $asPath If true, a leading directory separator is added to the return
     *
     * @return bool|string The zone/partition/id/tag that make up the new private storage key
     */
    public function getPrivateStorageKey( $legacyKey = null, $asPath = false )
    {
        static $_privateKey = null;

        return
            $_privateKey =
                $_privateKey ?: $this->getStorageKey( $legacyKey, $asPath ) . static::PRIVATE_STORAGE_PATH;
    }

    /** @inheritdoc */
    public function getStorageId()
    {
        return $this->_storageId;
    }

    /**
     * @param string $key The path to get. Use the LocalStorageTypes constants please.
     *
     * @return string Returns the path for $key or null if not yet set
     */
    public function getPath( $key )
    {
        if ( !EnterpriseKeys::contains( $key ) )
        {
            throw new \InvalidArgumentException( 'The path type "' . $key . '" is invalid.' );
        }

        return IfSet::get( $this->_paths, $key );
    }

    /**
     * @return CacheProvider
     */
    private function _getCache()
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
                    'dfe-storage' . DIRECTORY_SEPARATOR .
                    sha1( $this->_storageId ), static::DEFAULT_CACHE_EXTENSION
                );
    }

}