<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.log
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Joomla! System Logging Plugin.
 *
 * @since  1.5
 */
class PlgSystemSysbreezingforms extends JPlugin
{

    public function onAfterRender()
    {
        
        if(!file_exists(JPATH_ADMINISTRATOR . '/components/com_breezingforms/breezingforms.php')){ return; }
        
        $app = JFactory::getApplication();

        if( $app->input->getString('option') == 'com_menus' && $app->input->getString('view') == 'items' ){

            $body = JResponse::getBody();
            $body = str_replace('&lt;img src=../administrator/components/com_breezingforms/images/icons/component-menu-icons/bf_icon.png width=23px; /&gt;', '', $body);
	        $body = str_replace('&lt;img src=../administrator/components/com_breezingforms/images/icons/component-menu-icons/bf_icon.png width=23; /&gt;', '', $body);
            JResponse::setBody($body);
        }
    }
}
