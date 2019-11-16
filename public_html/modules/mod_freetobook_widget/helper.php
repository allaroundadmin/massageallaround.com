<?php

class FreetobookWidget
{
	private $token;
	private $params;
	private $styleSettings;
	
	function FreetobookWidget(&$params)
	{
		//read config	
		$this->params=$params;
		$this->token=$params->get('widgetkey');
		$this->styleSettings=$params->get('widget-settings');
	}
	
	
	function display()
	{
		switch ($this->styleSettings->style)
		{
			case 'calendar':
				$this->displayCalendar();
				break;
			case 'button':
				$this->displayButton();
				break;
			case 'custom':
				$this->displayCustom();
				break;	
		}
	}
	
	private function displayButton()
	{
		
		$imagePath=JURI::root().'/modules/mod_freetobook_widget';
		
		$imageId=$this->styleSettings->buttonid;
		$i=substr($imageId,0,1);
		$j=substr($imageId,1,1);
		echo '<a href="http://www.freetobook.com/affiliates/reservation.php?'.$this->token.'">
		<img src="'.$imagePath. '/stock_buttons/style' . $i . '/btn'.$j.'.gif" alt="book now"></a>';
	}
	
	private function displayCustom()
	{

		echo '<a href="http://www.freetobook.com/affiliates/reservation.php?'.$this->token.'">
		<img src="'. $this->styleSettings->customUrl .'" alt="book now"></a>';		
	}
	
	private function displayCalendar()
	{

		$token=$this->token;
		$document =& JFactory::getDocument();
		$document->addScript('http://www.freetobook.com/affiliates/dynamicWidget/js/joomla-widget-js.php?' . $token  );
		$document->addStyleSheet('http://www.freetobook.com/affiliates/dynamicWidget/styles/widget-css.php?' . $token);	



		
		$html='<div style="width:270px;height: 60px; clear:both; font-family: Arial,Helvetica,sans-serif; padding-left: 5px; font-size: 12px;" id="f2b-widget">
	<div>
    	<form action="http://www.freetobook.com/affiliates/reservation.php?'.$token.'" id="f2b_search_form" name="f2b_search_form" method="POST">
        	<div style="width: 120px;" id="cin">
            	<strong>Check In date:</strong>
                <div style="border: 1px solid rgb(170, 170, 170); padding: 2px; width: 113px;" class="cin-box">
                	<div style="width: 16px; height: 15px; margin: 2px 1px 0px 0px;" id="f2b-calendar">
                    	<img style="cursor: pointer;" alt="calendar icon" 
                        	src="http://www.freetobook.com/affiliates/dynamicWidget/images/calendar.gif" 
                            	id="cp_opener_f2b_search_cal" height="15" border="0" width="16" />
                    </div>
                   	<input value="dates" name="search_stage" type="hidden" />
                    <input id="f2b-widget-referrer" value="" name="referrer" type="hidden" />
                    <input value="2011-06-27" id="checkIn" name="checkIn" type="hidden" />
                    <input style="font-size: 12px; border: 1px solid rgb(92, 163, 210); line-height: normal; padding: 1px; width: 88px; height: 16px;" size="11" readonly="readonly" id="checkInDisplay" type="text" />
                </div>
            </div>
            <div id="duration">
            	<div class="label"><strong>Nights:</strong></div>
                <div style="width: 22px; height: 22px; border: 1px solid rgb(170, 170, 170); padding: 2px;" 
                		class="duration-box">
                	<input style="width: 18px; padding: 1px; margin: 1px 0px 0px; height: 16px; 
                    			line-height: normal; font-size: 12px;" maxlength="2" size="2" 
                                id="stayLength" name="stayLength" class="stayLength" type="text" />
                </div>
      </div>
      <div style="width: 94px; " class="searchButtonContainer"><input style="width: 94px; height: 28px;" value="" class="searchButton" type="submit"></div>
      		</form>
  	</div>
</div>

           ';	
		   
		   echo $html;
			
	}
	
	
	
}