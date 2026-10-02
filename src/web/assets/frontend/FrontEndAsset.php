<?php
namespace verbb\knockknock\web\assets\frontend;

use Craft;
use craft\web\AssetBundle;

class FrontEndAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/knockknock/web/assets/frontend/dist';

        // Craft 5.6 includes these front-end styles itself.
        if (version_compare(Craft::$app->getInfo()->version, '5.6.0', '<')) {
            $this->css = [
                'knock-knock.css',
            ];
        }

        parent::init();
    }
}
