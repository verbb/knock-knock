<?php
namespace verbb\knockknock\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class FrontEndAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = "@verbb/knockknock/resources/dist";

        $this->css = [
            'css/knock-knock.css',
        ];

        parent::init();
    }
}
