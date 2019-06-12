$(document).ready(function(){
	if(typeof(JsObj) != "undefined"){
		try
		{
			JsObj.OnLoad();
		}
		catch(e) //�������Ĵ����д�������Ͳ���
		{
			console.log("Error: JsObj.OnLoad fail!");
		}
			
	}
});
$(function() {
	$('.more-container').click(function (e) {
		e.stopPropagation();
		$('.logout').css('display', 'block');
	})
	// ��������
	$(document).bind('click', function () {
		$('.logout').css('display', 'none')
	})
	$('#close').click(function (e) {
		e.stopPropagation();
		if(typeof(JsObj) != "undefined"){
			try
			{
				JsObj.CloseApp();
			}
			catch(e) //�������Ĵ����д�������Ͳ���
			{
				console.log("Error: JsObj.CloseApp fail!");
			}
		}else{
			window.close();
		}
	})
	$('#log-off').click(function (e) {
		e.stopPropagation();
		logOut();
	})
})

//��ק����
var timer = null;

$("#header").mousedown(function(e){
  clearTimeout(timer);
  timer = setTimeout(function() {
	  if (typeof (JsObj) != "undefined") {
		  try {
			  JsObj.DragApp();
		  }
		  catch (e) //�������Ĵ����д�������Ͳ���
		  {
			  console.log("Error: JsObj.DragApp fail!");
		  }
	  }
	  console.log(e.type)
  },150)
});


function OnKeyPanel(){
	if(typeof(JsObj) != "undefined"){
		try
		{
			JsObj.OnKeyPanel();
		}
		catch(e) //�������Ĵ����д�������Ͳ���
		{
			console.log("Error: JsObj.OnKeyPanel fail!");
		}
	}
}

//˫��ȫ��
$("#header").dblclick(function(e){
  clearTimeout(timer);

  if(typeof(JsObj) != "undefined"){
		try
		{
			JsObj.DoubleClick();
		}
		catch(e) //�������Ĵ����д�������Ͳ���
		{
			console.log("Error: JsObj.DoubleClick fail!");
		}
  }
  console.log(e.type)
});

function WriteLog(log, mode){
	console.log("log:"+log);
}

function loading(bool){
	if(bool){
		$('.loading-wrapper').removeClass('hide');
	}else{
		$('.loading-wrapper').addClass('hide');
	}
}

function showMsg(msg,countdown) {
	if (typeof (msg) == "undefined") {
		$('#msg').html('').addClass('hide');
		$('.page-shade').addClass('hide');
	} else {
		if (typeof (countdown) == "undefined") {
			countdown = 2000;
		}
		$('#msg').html(msg).removeClass('hide');
		$('.page-shade').removeClass('hide');
		if(countdown>0){
			setTimeout(function() {
				$('#msg').html(msg).addClass('hide');
				$('.page-shade').addClass('hide');
			}, countdown);
		}
	}
}

// 判断是否是安卓系统
function isAndroid() {
	var sUserAgent = navigator.userAgent;
	var bIsAndroid = sUserAgent.toLowerCase().match(/android/i) == 'android';
	return bIsAndroid;
}
