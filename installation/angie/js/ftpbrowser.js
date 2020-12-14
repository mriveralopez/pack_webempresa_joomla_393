/*
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

function ftpBrowserClass(baseurl)
{
	this.url = baseurl;
	
	this.navTo = function(directory)
	{
		var url = this.url + '&directory=' + encodeURIComponent(directory);
		window.location = url;
	}
	
	this.useThis = function(path)
	{
		window.parent.useFTPDirectory(path);
	}
}
