<?php
namespace Concrete\Package\DatetimeShifter;

use Concrete\Core\Backup\ContentImporter;
use Package;

class Controller extends Package
{
    protected $pkgHandle = 'datetime_shifter';

    protected $appVersionRequired = '5.7.5.9';

    protected $pkgVersion = '0.0.1';

    public function getPackageName()
    {
        return t('DATETIME shifter');
    }

    public function getPackageDescription()
    {
        return t('Add or substract a specified amount of time from DATETIME database fields');
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installXml();
    }

    public function upgrade()
    {
        $this->installXml();
        parent::upgrade();
    }

    protected function installXml()
    {
        $pkg = Package::getByHandle($this->pkgHandle);
        $ci = new ContentImporter();
        $ci->importContentFile($pkg->getPackagePath() . '/install.xml');
    }
}
