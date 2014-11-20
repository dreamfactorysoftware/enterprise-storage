<?php
namespace DreamFactory\Library\Enterprise\Storage\Enums;

use DreamFactory\Library\Utility\IfSet;

/**
 * Standard DSP/DFE storage paths & keys
 */
class EnterprisePaths extends EnterpriseKeys
{
    //*************************************************************************
    //* Path Construction Constants
    //*************************************************************************

    /**
     * @type string Absolute path where storage is mounted
     */
    const MOUNT_POINT = '/data';
    /**
     * @type string Relative path under storage mount
     */
    const STORAGE_PATH = '/storage';
    /**
     * @type string Relative path under storage base
     */
    const PRIVATE_STORAGE_PATH = '/.private';
    /**
     * @type string Name of the applications directory relative to storage base
     */
    const APPLICATIONS_PATH = '/applications';
    /**
     * @type string Name of the plugins directory relative to storage base
     */
    const PLUGINS_PATH = '/plugins';
    /**
     * @type string Name of the config directory relative to storage and private base
     */
    const CONFIG_PATH = '/config';
    /**
     * @type string Name of the scripts directory relative to private base
     */
    const SCRIPTS_PATH = '/scripts';
    /**
     * @type string Name of the user scripts directory relative to private base
     */
    const USER_SCRIPTS_PATH = '/scripts.user';
    /**
     * @type string Name of the snapshot storage directory relative to private base
     */
    const SNAPSHOT_PATH = '/snapshots';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return bool True if this is an entprise/hosted instance (i.e. marker exists and doc root matches)
     */
    public static function hostedInstance()
    {
        static $_hostedInstance = null;
        static $_validRoots = array(EnterpriseDefaults::DEFAULT_DOC_ROOT, EnterpriseDefaults::DEFAULT_DEV_DOC_ROOT);

        if ( false === ( $_documentRoot = isset( $_SERVER ) ? IfSet::get( $_SERVER, 'DOCUMENT_ROOT' ) : false ) )
        {
            return false;
        }

        return
            $_hostedInstance =
                $_hostedInstance
                    ?: in_array( $_documentRoot, $_validRoots ) &&
                    ( file_exists( EnterpriseDefaults::FABRIC_MARKER ) || file_exists( EnterpriseDefaults::ENTERPRISE_MARKER ) );
    }

}
