<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.8
 * @package BreezingForms
 * @copyright (C) 2008-2012 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

jimport('joomla.version');
$version = new JVersion();

$active_language_code = JRequest::getVar('active_language_code', '');
if($active_language_code != ''){
	$active_language_code = '_translation'.$active_language_code;
}

JImport( 'joomla.html.editor' );
$editor = JFactory::getEditor();
$description00 = $editor->setContent('bfEditor','\'+item.properties[key]+\'');
$description   = $editor->setContent('bfEditor','\'+item.properties[key]+\'');
$description0  = $editor->setContent('bfEditor','item.properties[key]');

$intro = $editor->setContent('bfEditor','\'+item.properties[key]+\'');
$intro0 = $editor->setContent('bfEditor','item.properties[key]');
$intro00 = $editor->setContent('bfEditor','\'+item.properties[key]+\'');

if(version_compare($version->getShortVersion(), '3.7', '>=')) {

	// unfortunately, we must replace as setContent adds auto quotes
	$description = str_replace('\'','"', $description);
	$description00 = str_replace('\'','"', $description00);

	$intro = str_replace('\'','"', $intro);
        $intro00 = str_replace('\'','"', $intro);
    
   if($editor == JFactory::getEditor('tinymcebootstrap')) {
        $intro0 = str_replace('item.properties[key]','""+item.properties[key]+""', $intro0);
        $description0 = str_replace('item.properties[key]','""+item.properties[key]+""', $description0);
    } else {
        $intro0 = str_replace('item.properties[key]','"+item.properties[key]+"', $intro0);
        $description0 = str_replace('item.properties[key]','"+item.properties[key]+"', $description0);
    }    
}

$editor = JFactory::getEditor();
echo '<input type="submit" class="btn btn-primary" value="'.JText::_('SAVE').'" onclick="saveText();parent.SqueezeBox.close();"/><br/><br/>';
echo '<div style="width:700px;">'.$editor->display("bfEditor",'',700,300,40,20,1).'</div>';
echo '<br/><input type="submit" class="btn btn-primary" value="'.JText::_('SAVE').'" onclick="saveText();parent.SqueezeBox.close();"/>';
echo '<script>
function bfLoadText(){
        var keyPageIntro   = "pageIntro'.$active_language_code.'";
        var keyDescription = "description'.$active_language_code.'";
            
	var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);

	// workaround for quote bug with jce
	var testEditor = '.$editor->getContent('bfEditor').'

	if(testEditor == "item.properties[keyPageIntro]" || testEditor == "item.properties[keyDescription]"){
		if(item && item.properties.type == "page"){
			setTimeout("setIntro()",100);
		} else if(item && item.properties.type == "section"){
			setTimeout("setDescription()",250);
		}
	} else {
                if(item && item.properties.type == "page"){
			setTimeout("setIntro0()",100);
		} else if(item && item.properties.type == "section"){

			setTimeout("setDescription0()",250);
		}
        }
};
function saveText(){
        var keyPageIntro   = "pageIntro'.$active_language_code.'";
        var keyDescription = "description'.$active_language_code.'";
	var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);
	if(item && item.properties.type == "page"){
		item.properties[keyPageIntro] = '.$editor->getContent('bfEditor').'
	} else if(item && item.properties.type == "section"){
		item.properties[keyDescription] = '.$editor->getContent('bfEditor').'
	}
	'.$editor->save('bfEditor').'
}
function setIntro0(){
        var key = "pageIntro'.$active_language_code.'";
	var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);
        if(typeof item.properties[key] == "undefined"){
            item.properties[key] = "";
        }
	'.$intro0.'
        var testEditor = '.$editor->getContent('bfEditor').'
        
        if( testEditor == "+item.properties[key]+" || testEditor == "<div>\"+item.properties[key]+\"</div>" || testEditor == "<p>\"+item.properties[key]+\"</p>" || testEditor == "\"+item.properties[key]+\"" || testEditor == "item.properties[key]" || testEditor == "<p>item.properties[key]</p>" || testEditor == "<div>item.properties[\'pageIntro'.$active_language_code.'\']</div>" ){
            setTimeout("setIntro00()",250);
        }
}
function setIntro00(){
    var key = "pageIntro'.$active_language_code.'";
    var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);
    if(typeof item.properties[key] == "undefined"){
        item.properties[key] = "";
    }
    var testEditor = '.$editor->getContent('bfEditor').'
    if( testEditor == "+item.properties[key]+" ){
        ' . $editor->setContent('bfEditor','item.properties[key]') . '
    }
    else{
    ' . $intro00 . '
    }
}
function setDescription0(){
        var key = "description'.$active_language_code.'";
	var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);
        if(typeof item.properties[key] == "undefined"){
            item.properties[key] = "";
        }
	'.$description0.'
        var testEditor = '.$editor->getContent('bfEditor').'
            
        if( testEditor == "+item.properties[key]+" || testEditor == "<div>\"+item.properties[key]+\"</div>" || testEditor == "<p>\"+item.properties[key]+\"</p>" || testEditor == "\"+item.properties[key]+\"" || testEditor == "item.properties[key]" || testEditor == "<p>item.properties[key]</p>" || testEditor == "<div>item.properties[key]</div>"){
        
            setTimeout("setDescription00()",250);
        }
}
function setDescription00(){
    var key = "description'.$active_language_code.'";
    var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);
    if(typeof item.properties[key] == "undefined"){
        item.properties[key] = "";
    }
    var testEditor = '.$editor->getContent('bfEditor').'
    if( testEditor == "+item.properties[key]+" ){
        ' . $editor->setContent('bfEditor','item.properties[key]') . '
    }
    else{
        '.$description00.'
    }
}
function setIntro(){
        var key = "pageIntro'.$active_language_code.'";
	var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);
	'.$intro.'
}
function setDescription(){
        var key = "description'.$active_language_code.'";
	var item = parent.app.findDataObjectItem(parent.app.selectedTreeElement.id, parent.app.dataObject);
        if(typeof item.properties[key] == "undefined"){
            item.properties[key] = "";
        }
	'.$description.'
}

setTimeout("bfLoadText()",500);
</script>';

