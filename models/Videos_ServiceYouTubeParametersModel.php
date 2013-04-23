<?php

/**
 * Craft Videos by Dukt
 *
 * @package   Craft Videos
 * @author    Benjamin David
 * @copyright Copyright (c) 2013, Dukt
 * @license   http://dukt.net/addons/craft/videos/license
 * @link      http://dukt.net/addons/craft/videos/
 */

namespace Craft;

class Videos_ServiceYouTubeParametersModel extends BaseModel
{
    // --------------------------------------------------------------------

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        $attributes = array(
                'developerKey' => array(AttributeType::String, 'required' => true)
            );

        return $attributes;
    }
}