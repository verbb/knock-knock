<?php
namespace verbb\knockknock\assetbundles;

use craft\web\AssetBundle;

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
