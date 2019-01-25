<?php
namespace verbb\knockknock\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class AdminAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/knockknock/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/knock-knock.css',
        ];

        parent::init();
    }
}
