<?php
namespace DreamFactory\Library\Enterprise\Storage\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * Enterprise resources
 */
class EnterpriseResources extends FactoryEnum
{
    //*************************************************************************
    //* Defaults
    //*************************************************************************

    /**
     * @var int
     */
    const MOUNT_POINT = 0;
    /**
     * @var int
     */
    const INSTALL_ROOT = 1;
    /**
     * @var int
     */
    const STORAGE_PATH = 2;
    /**
     * @var int
     */
    const ZONE = 3;
    /**
     * @var int
     */
    const PARTITION = 4;
}
