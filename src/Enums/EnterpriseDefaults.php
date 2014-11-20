<?php
namespace DreamFactory\Library\Enterprise\Storage\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * Defaults for the operations/runtime environment of DSP/DFE
 */
class EnterpriseDefaults extends FactoryEnum
{
    //*************************************************************************
    //* Defaults
    //*************************************************************************

    /**
     * @var string
     */
    const DFE_ENDPOINT = 'http://cerberus.fabric.dreamfactory.com/api';
    /**
     * @var string
     */
    const DFE_AUTH_ENDPOINT = 'http://cerberus.fabric.dreamfactory.com/api/instance/credentials';
    /**
     * @var string
     */
    const OASYS_PROVIDER_ENDPOINT = 'http://oasys.cloud.dreamfactory.com/oauth/providerCredentials';
    /**
     * @var string
     */
    const INSTANCE_CONFIG_FILE_NAME_PATTERN = '/instance.json';
    /**
     * @var string
     */
    const DB_CONFIG_FILE_NAME_PATTERN = '/{instance_name}.database.config.php';
    /**
     * @var string
     */
    const PLATFORM_VIRTUAL_SUBDOMAIN = '.cloud.dreamfactory.com';
    /**
     * @var string
     */
    const FABRIC_MARKER = '/var/www/.fabric_hosted';
    /**
     * @var string
     */
    const ENTERPRISE_MARKER = '/var/www/.dfe_hosted';
    /**
     * @var string
     */
    const DEFAULT_DOC_ROOT = '/var/www/launchpad/web';
    /**
     * @var string
     */
    const DEFAULT_DEV_DOC_ROOT = '/opt/dreamfactory/dsp/dsp-core/web';
    /**
     * @var string
     */
    const MAINTENANCE_MARKER = '/var/www/.dfe_maintenance';
    /**
     * @var string
     */
    const MAINTENANCE_URI = '/static/dreamfactory/maintenance.php';
    /**
     * @var string
     */
    const UNAVAILABLE_URI = '/static/dreamfactory/unavailable.php';
    /**
     * @var int
     */
    const EXPIRATION_THRESHOLD = 30;
    /**
     * @var string Public storage cookie key
     */
    const PUBLIC_STORAGE_COOKIE = 'dfe.storage_key';
    /**
     * @var string Private storage cookie key
     */
    const PRIVATE_STORAGE_COOKIE = 'dfe.private_storage_id';

}
