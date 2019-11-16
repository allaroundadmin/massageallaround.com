
function toggleType(radio)
{
	document.getElementById('ftb-widget-custom').style.display=(radio.value=='custom')?'block':'none';	
	document.getElementById('ftb-widget-buttons').style.display=(radio.value=='button')?'block':'none';				
}