<?php
/**
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewSession extends AView
{
	public function onBeforeMain()
	{
		$this->state = $this->getModel()->getStateVariables();
		$this->hasFTP = function_exists('ftp_connect');
		return true;
	}
}
