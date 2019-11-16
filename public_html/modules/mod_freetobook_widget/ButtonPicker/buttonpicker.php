<?php
class JFormFieldButtonPicker extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	public	$type = 'ButtonPicker';

	protected function getInput()
	{

		// Get the database instance
		$db = JFactory::getDbo();
		// Build the select query
		$query = 'SELECT params FROM #__modules'
			. ' WHERE module="mod_freetobook_widget"';
		$db->setQuery($query);
		$jsonParams = $db->loadObjectList();
        $params = json_decode($jsonParams[0]->params, true);
		$params=$params['widget-settings'];

		$buttonDisplay=$customDisplay='none';
		$buttonChecked=$customChecked=$calendarChecked='';
		
		switch($params['style'])
		{
			case 'custom':
				$customDisplay='block';
				$customChecked=' checked="checked" ';
				break;
			case 'button':
				$buttonDisplay='block';
				$buttonChecked=' checked="checked" ';				
				break;
			default:
				$calendarChecked=' checked="checked" ';	
			
		}
		
		$imagePath=JURI::root().'/modules/mod_freetobook_widget';
		$document =& JFactory::getDocument();
		$document->addScript(JURI::root() . 'modules/mod_freetobook_widget/ButtonPicker/buttonpicker.js');
		$document->addStyleSheet(JURI::root() . 'modules/mod_freetobook_widget/ButtonPicker/buttonpicker.css');	

		$html='<div id="ftb-button-picker" >';
	
		$html.='<div style="clear:both;"> 

					<label style="display:inline-block">Calendar</label> <input type="radio" name="jform[params][widget-settings][style]" value="calendar" onclick="toggleType(this)" '.$calendarChecked.'/>&nbsp; 
					<label style="display:inline-block">Button Only</label> <input type="radio" name="jform[params][widget-settings][style]" value="button" onclick="toggleType(this)" '.$buttonChecked.' /> 
					<label style="display:inline-block">Custom Image</label> <input type="radio" name="jform[params][widget-settings][style]" value="custom" onclick="toggleType(this)" '.$customChecked.' /> 
			<br/><br/>
		   </div>';

		$html.='<div id="ftb-widget-custom" style="display:'.$customDisplay.'">
		<labeL>URL:</label><input type="text" name="jform[params][widget-settings][customUrl]" id="jform_params_custom_url"  size="90" value="'. $params['customUrl']  .'">
		</div>';


		$html.='<div style="overflow-x:scroll;width:900px;display:'.$buttonDisplay.'" id="ftb-widget-buttons">	
					<table id="ftb-button-list">	
						<tr>';
		
		$i=$params['buttonid'];
		$selectedButton=(!empty($i) )?$i:'11';				
				
		$numberOfStyles=7;
		$numberOfButtons=6;
		for($i=1;$i<=$numberOfStyles;$i++)
		{
			for ($j=1;$j<=$numberOfButtons;$j++)
			{
			$checked=(($i.$j)==$selectedButton)?' checked="checked" ':'';
			$html.='<td style="text-align:center;padding:7px;">
						<img  src="' . $imagePath . '/stock_buttons/style' . $i . '/btn'.$j.'.gif" alt=""><br>
						<input type="radio" name="jform[params][widget-settings][buttonid]"  value="'.$i.$j.'" '.$checked.'  >
					</td>';	
			}
			$html.='</tr><tr>';					
		}
		$html.='
		</tr>
		</table>
		</div>
		</div>
		';
		

		return $html;
	}
}