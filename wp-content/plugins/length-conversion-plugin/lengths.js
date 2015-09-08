
function checnum(as)
{
	var dd = as.value;
	if(isNaN(dd))
	{
		dd = dd.substring(0,(dd.length-1));
		as.value = dd;
	}
} 

metersconv

function metersconv(val){
	with(document.conv){
		cm.value = (Math.round(meters.value/0.010000)).toFixed(2);
		feet.value=(Math.round(meters.value/3.2808)).toFixed(2);
		inch.value=(Math.round(meters.value*39.370)).toFixed(2);
		kilo.value=((meters.value/1000.00)).toFixed(6);
		miles.value=((meters.value*0.000621)).toFixed(6);
	} 
}

function inchconv(val){
	with(document.conv){
		cm.value = (Math.round(inch.value*2.54)).toFixed(2);
		feet.value=(Math.round(inch.value/12)).toFixed(2);
		var tmp=(inch.value* 2.54)*Math.pow(10,-5);
		kilo.value=((inch.value* 2.54)*Math.pow(10,-5)).toFixed(2);
		miles.value=((inch.value*1.58)*Math.pow(10,-5)).toFixed(2);
		meters.value=((inch.value/39.370)).toFixed(2);
	} 
}

function feetconv(val){
	with(document.conv){
		cm.value=(Math.round(feet.value*30.48)).toFixed(2);
		inch.value=(Math.round(feet.value*12)).toFixed(2);
		kilo.value=((feet.value* 3.05)*Math.pow(10,-4)).toFixed(2);
		miles.value==((feet.value* 0.00018939)).toFixed(2);
		meters.value = ((feet.value/3.2808)).toFixed(2);
	} 
} 

function cmconv(val){
	with(document.conv){
		feet.value = (Math.round(cm.value/30.84)).toFixed(2);
		inch.value = (Math.round(cm.value/2.54)).toFixed(2);
		kilo.value=((cm.value*1)*Math.pow(10,-5)).toFixed(2);
		miles.value==((cm.value*6.21)*Math.pow(10,-6)).toFixed(2);
		meters.value = ((cm.value/100)).toFixed(2);
	} 
} 

function kiloconv(val){
	with(document.conv){
		feet.value = ((kilo.value*3.28)*Math.pow(10,3)).toFixed(2);
		inch.value = ((kilo.value*3.94)*Math.pow(10,4)).toFixed(2);
		cm.value=((kilo.value*1)*Math.pow(10,5)).toFixed(2);
		miles.value=((kilo.value* 0.621)).toFixed(2);
		meters.value = ((kilo.value/0.0010000)).toFixed(2);
	} 
} 

function milesconv(val){
	with(document.conv){
		feet.value =((miles.value* 5.28)*Math.pow(10,3)).toFixed(2);
		inch.value = ((miles.value*6.34)*Math.pow(10,4)).toFixed(2);
		cm.value=((miles.value*1.61)*Math.pow(10,5)).toFixed(2);
		kilo.value=((miles.value*1.61)).toFixed(2);
		meters.value=((miles.value/0.00062137)).toFixed(2);
	} 
} 
