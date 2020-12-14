<?php
/**
 * @package     BreezingForms
 * @author      Markus Bopp
 * @link        http://www.crosstec.de
 * @license     GNU/GPL
*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$tabs->startTab('Google Drive®','tab_googledrive');
?>
<fieldset><legend>Google Drive®</legend>
<table width="80%" cellpadding="4" cellspacing="1" border="0">

    <?php
    if($error != ''){
    ?>
    
    <tr>
        <td colspan="2" style="color: red;"><?php echo $error;?></td>
    </tr>
    
    <?php
    }
    ?>
    
    <?php
    if($accessToken){
    ?>
    
    <tr>
        <td style="width: 200px;" valign="top"><?php echo BFText::_('COM_BREEZINGFORMS_GD_RESET'); ?></td>
        <td valign="top">
            <input type="checkbox" onclick="document.getElementById('reload_flag').value=1;" name="gdata_reset"  value="1" size="50"  class="inputbox"/>
        </td>
    </tr>
    
    <tr>
        <td style="width: 200px;" valign="top"><?php echo BFText::_('COM_BREEZINGFORMS_GD_ENABLED'); ?></td>
        <td valign="top">
            <input type="checkbox" onclick="document.getElementById('reload_flag').value=1;" name="gdata_enabled"  value="1" size="50"  class="inputbox"<?php echo $gdata->enabled == 1 ? ' checked="checked"' : ''; ?>/>
            <input type="hidden" name="reload_flag" id="reload_flag" value="0"/>
        </td>
    </tr>

    <?php 
    }
    ?>
    
    <?php
    if(!$accessToken){
    ?>
    
    <tr>
        <td valign="top"></td>
        <td valign="top"><a href="<?php echo $auth_url;?>" target="_blank"><?php echo JText::_('COM_BREEZINGFORMS_GD_LOGIN_TEXT');?></a><br /><?php echo JText::_('');?></td>
    </tr>
    
    <tr>
        <td valign="top"><?php echo BFText::_('COM_BREEZINGFORMS_GD_CODE'); ?></td>
        <td valign="top"><input type="password" name="gdata_code"  value="" size="50"  class="inputbox"/></td>
    </tr>
    
    <?php
    }
    ?>
    
    <?php
    if(count($gdata_spreadsheets)){
    ?>
    <tr>
        <td valign="top"><?php echo BFText::_('COM_BREEZINGFORMS_GD_SPREADSHEETS'); ?></td>
        <td valign="top">
            <select name="gdata_spreadsheet_id" onchange="document.getElementById('reload_flag').value=1;">
                <option value="">-</option>
            <?php
            foreach($gdata_spreadsheets As $key => $value){
            ?>
                <option value="<?php echo strToHex($key);?>"<?php echo $key == $gdata->spreadsheet_id ? ' selected="selected"' : ''?>><?php echo htmlentities($value, ENT_QUOTES, 'UTF-8');?></option>
            <?php
            }
            ?>
            </select>
        </td>
    </tr>
    <?php
    }
    ?>
    <?php
    if(count($gdata_worksheets)){
    ?>
    <tr>
        <td valign="top"><?php echo BFText::_('COM_BREEZINGFORMS_GD_WORKSHEETS'); ?></td>
        <td valign="top">
            <select name="gdata_worksheet_id" onchange="document.getElementById('reload_flag').value=1;">
                <option value=""> - <?php echo BFText::_('COM_BREEZINGFORMS_GD_SELECT_WORKSHEET'); ?> - </option>
            <?php
            foreach($gdata_worksheets As $key => $value){
            ?>
                <option value="<?php echo strToHex($key);?>"<?php echo $key == $gdata->worksheet_id ? ' selected="selected"' : ''?>><?php echo htmlentities($value, ENT_QUOTES, 'UTF-8');?></option>
            <?php
            }
            ?>
            </select>
        </td>
    </tr>
    <?php
    }
    ?>
    <?php
    if($gdata->worksheet_id != ''){
    ?>
    
    <tr>
        <td style="width: 200px;" valign="top"><?php echo BFText::_('COM_BREEZINGFORMS_GD_SYNCH'); ?></td>
        <td valign="top">
            <input type="checkbox" onclick="document.getElementById('reload_flag').value=1;" name="gdata_synch"  value="1" size="50"  class="inputbox"/>
            <div>&nbsp;</div>
            <div><?php echo BFText::_('COM_BREEZINGFORMS_GD_SYNCH_WARNING'); ?></div>
        </td>
    </tr>
    
    <tr><td colspan="2">
            <strong><?php echo BFText::_('COM_BREEZINGFORMS_GD_META_ASSIGNMENT'); ?></strong>
        </td>
    </tr>
    <?php
    $meta_fields = array();
    $meta_fields[0] = new stdClass();
    $meta_fields[0]->title = 'Record ID';
    
    $meta_fields[1] = new stdClass();
    $meta_fields[1]->title = 'Form ID';
    
    $meta_fields[2] = new stdClass();
    $meta_fields[2]->title = 'Form Title';
    
    $meta_fields[3] = new stdClass();
    $meta_fields[3]->title = 'Form Name';
    
    $meta_fields[4] = new stdClass();
    $meta_fields[4]->title = 'Timestamp';
    
    $meta_fields[5] = new stdClass();
    $meta_fields[5]->title = 'Date';
    
    $meta_fields[6] = new stdClass();
    $meta_fields[6]->title = 'Time';
    
    $meta_fields[7] = new stdClass();
    $meta_fields[7]->title = 'IP';
    
    $meta_fields[8] = new stdClass();
    $meta_fields[8]->title = 'USER AGENT';
    
    $meta_fields[9] = new stdClass();
    $meta_fields[9]->title = 'User ID';
    
    $meta_fields[10] = new stdClass();
    $meta_fields[10]->title = 'Username';
    
    $meta_fields[11] = new stdClass();
    $meta_fields[11]->title = 'User Fullname';
    
    foreach($meta_fields As $bfField){
    ?>
    <tr>
        <td valign="top"><?php echo $bfField->title; ?></td>
        <td valign="top">
            <select name="gdata_meta[]">
                <option value=""> - <?php echo BFText::_('COM_BREEZINGFORMS_GD_UNUSED'); ?> - </option>
                <?php
                foreach($gdata_columns As $gdata_column){
                ?>
                <option value="<?php echo $bfField->title?>::<?php echo $gdata_column;?>"<?php echo in_array($bfField->title.'::'.$gdata_column, $gdata->meta) ? ' selected="selected"' : '' ?>><?php echo $gdata_column;?></option>
                <?php
                }
                ?>
            </select>
        </td>
    </tr>
    <?php
    }
    ?>
    <tr><td colspan="2">
            <strong><?php echo BFText::_('COM_BREEZINGFORMS_GD_FIELD_ASSIGNMENT'); ?></strong>
        </td>
    </tr>
    <?php
    foreach($breezingforms_fields As $bfField){
    ?>
    <tr>
        <td valign="top"><?php echo $bfField->title; ?> (<?php echo $bfField->name?>)</td>
        <td valign="top">
            <select name="gdata_fields[]">
                <option value=""> - <?php echo BFText::_('COM_BREEZINGFORMS_GD_UNUSED'); ?> - </option>
                <?php
                foreach($gdata_columns As $gdata_column){
                ?>
                <option value="<?php echo $bfField->title.$bfField->id; ?>::<?php echo $gdata_column;?>"<?php echo in_array($bfField->title.$bfField->id.'::'.$gdata_column, $gdata->fields) ? ' selected="selected"' : '' ?>><?php echo $gdata_column;?></option>
                <?php
                }
                ?>
            </select>
        </td>
    </tr>
    <?php
    }
    }
    ?>
</table>
</fieldset>
<?php
$tabs->endTab();
?>

