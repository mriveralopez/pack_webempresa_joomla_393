<?php
/**
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

class AngieViewDatabase extends AView
{
	/** @var int Do we have a flag for large tables? */
	public $large_tables = 0;

	public $substep = '';

	public $number_of_substeps = 0;

	public $db;

	public function onBeforeMain()
	{
		/** @var AngieModelSteps $stepsModel */
		$stepsModel = AModel::getAnInstance('Steps', 'AngieModel', array(), $this->container);
		/** @var AngieModelDatabase $dbModel */
		$dbModel = AModel::getAnInstance('Database', 'AngieModel', array(), $this->container);

		$this->substep = $stepsModel->getActiveSubstep();
		$this->number_of_substeps = $stepsModel->getNumberOfSubsteps();
		$this->db = $dbModel->getDatabaseInfo($this->substep);
		$this->large_tables = $dbModel->largeTablesDetected();

		if ($this->large_tables)
		{
			$this->large_tables = round($this->large_tables / (1024 * 1024), 2);
		}

		return true;
	}
}
