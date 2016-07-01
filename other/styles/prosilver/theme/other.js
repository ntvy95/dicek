function bbcode_spoil(CN) {
	var arr = document.getElementsByClassName(CN);
	for(var i = 0; i < arr.length; i++) {
		if(arr[i].style.display=='none')
		{
			arr[i].style.display='';
		}
		else
		{
			arr[i].style.display='none';
		}
	}
}