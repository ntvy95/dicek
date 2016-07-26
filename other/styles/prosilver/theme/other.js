function bbcode_spoil(CN) {
	var arr = document.getElementsByClassName(CN);
	for(var i = 0; i < arr.length; i++) {
		if(arr[i].className.indexOf(' spoiler_hide ') > -1)
		{
			arr[i].className = arr[i].className + ' spoiler_show ';
			arr[i].className = arr[i].className.replace(' spoiler_hide ', '');
		}
		else
		{
			arr[i].className = arr[i].className + ' spoiler_hide ';
			arr[i].className = arr[i].className.replace(' spoiler_show ', '');
		}
	}
}

function bbcode_spoil_full(thisz) {
	if (thisz.parentNode.parentNode.getElementsByTagName('div')[1].className.indexOf(' spoiler_hide ') > -1) {
		thisz.value = 'Ẩn đi!';
		thisz.parentNode.parentNode.getElementsByTagName('div')[1].style.border = '1px solid black';
		thisz.parentNode.parentNode.getElementsByTagName('div')[1].className += ' spoiler_show ';
		thisz.parentNode.parentNode.getElementsByTagName('div')[1].className =
			thisz.parentNode.parentNode.getElementsByTagName('div')[1].className.replace(' spoiler_hide ', '');
	}
	else {
		thisz.value = 'Xem!';
		thisz.parentNode.parentNode.getElementsByTagName('div')[1].style.border = 'none';
		thisz.parentNode.parentNode.getElementsByTagName('div')[1].className += ' spoiler_hide ';
		thisz.parentNode.parentNode.getElementsByTagName('div')[1].className =
			thisz.parentNode.parentNode.getElementsByTagName('div')[1].className.replace(' spoiler_show ', '');
	}
}