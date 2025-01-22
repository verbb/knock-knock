<?php
namespace verbb\knockknock\assetbundles;

use Craft;
use craft\web\AssetBundle;

class FrontEndAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/knockknock/resources/dist';

        // No longer required in Craft 5.6, due to front-end styles
        if (version_compare(Craft::$app->getInfo()->version, '5.6.0', '<')) {
            $this->css = [
                'css/knock-knock.css',
            ];
        }

        parent::init();
    }
}
