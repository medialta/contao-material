<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace ContaoMaterial;


/**
 * Class Helper
 *
 * @author Medialta <http://www.medialta.com>
 */
class Helper extends \System
{
    /**
     * Post login hook
     *
     * @param \User $objUser current logged in user
     */
    public function postLogin($objUser)
    {
        $session = \Session::getInstance();
        $modules = $session->get('backend_modules');

        if (is_array($modules) && !empty($modules))
        {
            foreach ($modules as $groupName => $value)
            {
                $modules[$groupName] = 1;
            }

            $session->set('backend_modules', $modules);
        }
    }
}
