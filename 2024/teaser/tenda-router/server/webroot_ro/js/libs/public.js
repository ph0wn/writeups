﻿function setCheckbox(arry) {
	var i = 0,
		len = arry.length,
		val;
	for (i = 0; i < len; i++) {
		val = $("#" + arry[i]).val();
		if (val == 1) {
			$("#" + arry[i]).attr("checked", true);
		} else {
			$("#" + arry[i]).attr("checked", false);
		}
	}
}

function loginOut() {
	//$(document).ajaxError(function () {
	//top.location.reload(true);
	//});
}

function getCheckbox(arry) {
	var i = 0,
		length = arry.length;
	for (i = 0; i < length; i++) {
		if ($("#" + arry[i])[0].checked == true) {
			$("#" + arry[i]).val(1);
		} else {
			$("#" + arry[i]).val(0);
		}
	}
}

function inputValue(obj) {
	var prop;
	for (prop in obj) {
		$("#" + prop).val(obj[prop]);
	}
}

function formatSeconds(value) {
	var theTime = parseInt(value); // 秒 
	var theTime1 = 0; // 分 
	var theTime2 = 0; // 小时
	var theTime3 = 0; // 天
	// alert(theTime); 
	if (theTime > 60) {
		theTime1 = parseInt(theTime / 60);
		theTime = parseInt(theTime % 60);
		// alert(theTime1+"-"+theTime); 
		if (theTime1 > 60) {
			theTime2 = parseInt(theTime1 / 60);
			theTime1 = parseInt(theTime1 % 60);
			if (theTime2 > 24) {
				theTime3 = parseInt(theTime2 / 24);
				theTime2 = parseInt(theTime2 % 24);
			}
		}
	}
	var result = "" + parseInt(theTime) + _("s");
	if (theTime1 > 0) {
		result = "" + parseInt(theTime1) + _("min") + " " + result;
	}
	if (theTime2 > 0) {
		result = "" + parseInt(theTime2) + _("hour(s)") + " " + result;
	}
	if (theTime3 > 0) {
		result = "" + parseInt(theTime3) + _("day(s)") + " " + result;
	}
	return result;
}

function checkIpInSameSegment(eip, emask, ip, mask) {
	var index = 0;
	var eipp = "";
	var emaskk = "";
	if (typeof (eip) == "object")
		eipp = eip.split(".");
	else
		eipp = eip.split(".");

	if (typeof (emask) == "object")
		emaskk = emask.split(".");
	else
		emaskk = emask.split(".");
	if (ip == '' && mask == '')
		return false;
	var ipp = ip.split(".");
	var maskk = mask.split(".");
	var msk = maskk;
	for (var i = 0; i < 4; i++) {
		if (emaskk[i] == maskk[i]) {
			continue;
		} else if (emaskk[i] > maskk[i]) {
			msk = maskk;
			break;
		} else {
			msk = emaskk;
			break;
		}
	}
	for (var i = 0; i < 4; i++) {
		if ((eipp[i] & msk[i]) != (ipp[i] & msk[i])) {
			return false;
		}
	}
	return true;
}

function objTostring(obj) {
	var prop,
		str = "";
	for (prop in obj) {
		str += prop + "=" + encodeURIComponent(obj[prop]) + "&";
	}
	str = str.replace(/[&]$/, "");
	return str;
}


//拿到字符串的字节数
function getStrByteNum(str) {
	var totalLength = 0,
		charCode;

	for (var i = str.length - 1; i >= 0; i--) {
		charCode = str.charCodeAt(i);
		if (charCode <= 0x007f) {
			totalLength++;
		} else if ((charCode >= 0x0080) && (charCode <= 0x07ff)) {
			totalLength += 2;
		} else if ((charCode >= 0x0800) && (charCode <= 0xffff)) {
			totalLength += 3;
		} else {
			totalLength += 4;
		}
	}
	return totalLength;
}


function showErrMsg(id, str, noFadeAway) {
	var T = 0;
	var showErrMsgFun = function (id, str, noFadeAway) {
		clearTimeout(T);
		$("#" + id).html(str);
		if (!noFadeAway) {
			T = setTimeout(function () {
				$("#" + id).html("&nbsp;");
			}, 2000);
		}
	}
	showErrMsg = showErrMsgFun;
	showErrMsgFun(id, str, noFadeAway);
}

function initIframeHeight() {
	if (!$("#gbx_overlay").is(":visible")) {
		return;
	}

	var iframeHeight = $(".dailog-iframe").contents().find("fieldset:eq(0)").height() + 40,
		maxHeight = $(window).height(),
		marginTop = 30;
	//dialogBoxHeight = iframeHeight + 80;

	//dialogBoxHeight = (dialogBoxHeight > maxHeight ? maxHeight : dialogBoxHeight);
	//子框架适应ios safari，解决ios safari浏览器子框架滚动不了
	$(".dailog-iframe").contents().find("fieldset:eq(0)").addClass("scroll-wrapper");

	iframeHeight = maxHeight - 50 - (marginTop * 2);
	$(".dailog-iframe").contents().find(".scroll-wrapper").css("height", iframeHeight + "px");
	$(".dailog-iframe").css("height", iframeHeight + "px");
	$(".dailog-iframe").contents().find("body").css({
		"height": iframeHeight + "px",
		"overflow-y": "hidden"
	});
	/*$(".dailog-iframe").contents().find("body").css({
		"height": "100%",
		"overflow-y": "hidden"
	});*/
	$(".main-dailog").css({
		"top": "10px",
		"margin-top": marginTop + "px",
		"height": maxHeight - (marginTop * 2) + "px"
	});
	if ($(".main-dailog").offset().top < 0) {
		$(".main-dailog").css({
			"top": "10px",
			"margin-top": "0"
		});
	}
}

function showIframe(title, url, width, height, extraDataStr) {
	width = 822;
	height = 579;
	var extraDataStr = extraDataStr || "";
	if ($("#gbx_overlay").length == 0) {
		$("<div id='gbx_overlay'></div>").appendTo("body");
	}
	$(".save-msg").removeClass("none");
	$("#page-message").html(_("Loading..."));

	$("iframe").attr("src", url + "?random=" + Math.random() + "&" + extraDataStr);
	$("#head_title").html(title);

	//位置调整
	$(".main-dailog").css("width", width + "px").addClass("none");
	$(".dailog-iframe").css("width", width + "px");
	$(".main-dailog").css({
		"left": "50%",
		"top": "50%",
		"width": width + "px",
		"margin-left": -width / 2 + "px",
		"margin-top": -$(".main-dailog").outerHeight() / 2 + "px"
	});

	$("#head_title2").addClass("none").removeClass("selected");
	$(".fopare-ifmwrap-title").removeClass("border-bottom");
	$("#head_title").removeClass("selected");
	top.iframeload = false;
	//iframe加载成功之后，initIframeHeight()
	$("iframe").on("load.iframeload", function () {
		top.iframeload = true;
		$(".main-dailog").removeClass("none")
		$(".save-msg").addClass("none");
		var time = 0;
		(function () {
			if (time < 1) {
				initIframeHeight();
				time++;
				setTimeout(arguments.callee, 30);
			} else {
				return;
			}
		})();
		initIframeHeight();
		$("iframe").off(".iframeload");
	});
}

function closeIframe() {
	$(".main-dailog").addClass("none");
	$(".main-dailog").find("iframe").attr("src", "").removeClass("none");
	$("#iframe-msg").html("");
	$("#gbx_overlay").remove();
	if (window[top.mainPageLogic.modelObj] && typeof window[top.mainPageLogic.modelObj].initValue == "function") {
		window[top.mainPageLogic.modelObj].initValue();
	}
}

function showSaveMsg(num, str, flag, change) {
	var str = str || _("Saving..."),
		flag = flag || "-1",
		hideDialog = true;

	$("#gbx_overlay").remove();
	$("<div id='gbx_overlay'></div>").appendTo("body");

	$(".dailog-iframe").contents().find("input:focus").blur();

	if (num == 0) {
		$("#page-message").html(str);
		$(".save-msg").removeClass("none");
		if (flag == "-1") { //常用 
			setTimeout(function () {
				$(".save-msg").addClass("none");
				$("#gbx_overlay").remove();
			}, 2000);
		} else if (flag == "1") { //复制
			$(".save-loading").addClass("hidden");
			setTimeout(function () {
				$(".save-msg").addClass("none");
				$("#gbx_overlay").remove();
				$(".save-loading").removeClass("hidden");
			}, 1000);
		} else if (flag == 2) { //加入黑名单
			//$("#gbx_overlay").remove();
			setTimeout(function () {
				$(".save-msg").addClass("none");
				$(".main-dailog").removeClass("none");
			}, 2000);
		} else if (flag == 3) {
			//针对禁止wifi和加入黑名单，等待1秒后再获取数据
			setTimeout(function () {
				top.mainPageLogic.modelObj = "staInfo";
				top.staInfo.initValue();
				$(".save-msg").addClass("none");
				$("#gbx_overlay").remove();
			}, 1000);

		} else if ((/^([1-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.){2}([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/).test(flag)) {
			//修改LAN
			//改变LAN
			if (change) {
				setTimeout(function () {
					jumpTo(flag, function () {
						$(".save-msg").addClass("none");
						$(".main-dailog").removeClass("none");
						$("#gbx_overlay").remove();
					});
					//top.location.href = "http://" + flag;
				}, 20000);
			} else {
				setTimeout(function () {
					$(".save-msg").addClass("none");
					$("#gbx_overlay").remove();
				}, 2000);
			}

		} else if (flag == 4) {
			//智能QoS测速中	
			if (str == _("Saved")) {
				getCloudInfo()
			} else {
				checkBandWidth();
			}
		} else if (flag == 5) { //外网设置耗时 5 秒
			hideDialog = false;
			/*setTimeout(function () {
				$(".save-msg").addClass("none");
				$("#gbx_overlay").remove();
			}, 4000);*/
		}
	} else if (num == "999") {
		hideDialog = false; //不隐藏弹出框
		$(".save-msg").removeClass("none");
		$("#page-message").html(_("Saving success"));
		$("#page-message").addClass("none");
		top.location.reload(true);
	} else {
		$(".save-msg").removeClass("none");
		$("#page-message").html(_("Saving success"));
		setTimeout(function () {
			$(".save-msg").addClass("none");
			$("#gbx_overlay").remove();
		}, 1000);
	}
	if (hideDialog) {
		$(".main-dailog").addClass("none");
	}
}

function getCloudInfo() {

	$.getJSON("goform/cloud?module=getInfo&rand=" + new Date().toTimeString(), function (obj) {

		if (!obj.password) {
			//无密码需要自动生成密码
			obj.password = str_encode(randomString());
		}
		var str = "ucloud_enable=" + obj.enable + "&password=" + obj.password + "&speed_dir=1"; //1：下行，0：上行
		//不管是否开启云服务，都将数据传给后台，如果
		//if(confirm("测速期间网络将断开，是否继续？")) {

		$.post("goform/SetSpeedWan", str, function () {
			showSaveMsg(0, _("Testing the download speed..."), 4);
		});

		//}

	});
}


/***********************
	*重启、升级、恢复出厂设置、还原等操作
	*str: 操作的动作 
	
**********************/
var pc = 0;
var upgradeTime = 0,
	rebootTime = 0;
(function ($) {
	$.progress = {
		showPro: function (str, str2, ip) {
			closeIframe();
			$("body").undelegate("#gbx_overlay", "click");
			clearTimeout(top.staInfo.time);
			str2 = str2 || _("Rebooting... Please wait.");
			ipaddress = "";
			ipaddress = ip || "";
			if ($("#gbx_overlay").length == 0) {
				$("<div id='gbx_overlay'></div>").appendTo("body");
			}
			var html = '<div id="loading_div" >' +
				'<div id="up_contain">' +
				'<span class="upgrading"><span class="upgrade_pc"></span></span><br />' + (_("Upgrading... Do not power off the router.")) + '<span id="upgrade_text"></span>' +
				'</div>' +
				'<div class="load-img"><span id="load_pc" class="up-loadding load-reboot"></span></div><br />' + str2 + '<span id="load_text"></span>' +
				'</div>';
			$(html).appendTo("body");
			$this_obj = $("#loading_div")
			$("#loading_div").css("left", ($.viewportWidth() - $this_obj.width()) / 2);
			$("#loading_div").css("top", ($.viewportHeight() - $this_obj.height()) / 2);
			$this_obj.css("z-index", 3000);
			$this_obj.css("position", "fixed");
			switch (str) {
			case "upgrade":
				$("#up_contain").addClass("none");
				rebooting(1450);
				break;
			case "reboot":
				$("#up_contain").addClass("none");
				rebooting(550);
				break;
			case "apclient":
				$("#up_contain").addClass("none");
				rebooting(750);
				break;
			case "restore":
			case "restoreDefault":
				$("#up_contain").addClass("none");
				rebooting(650);
				break;
			}
		}
	}
})(jQuery);

function rebooting(time) {
	time = time || 200;
	if (pc <= 100) {
		clearTimeout(rebootTime);
		rebootTime = setTimeout('rebooting(' + time + ')', time);
		//$(".load_pc").css("width", pc+'%');
		$("#load_pc").attr("class", "up-loadding load-reboot");
		$("#load_text").html(pc + '%');
		pc++;
	} else {
		clearTimeout(rebootTime);
		pc = 1;
		if (ipaddress != "" && (/^([1-9]|[1-9]\d|1\d\d|2[0-1]\d|22[0-3])\.(([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.){2}([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/).test(ipaddress) == true || ipaddress == "tendawifi.com") {
			jumpTo(ipaddress, function () {
				$("#gbx_overlay").remove();
				$("#loading_div").remove();
			});
		} else {
			jumpTo(window.location.host, function () {
				$("#gbx_overlay").remove();
				$("#loading_div").remove();
			});
		}
	}
}

/**
 * 跳转，用于重启之后，升级之后，恢复出厂设置，修改lanip的跳转
 * @param  {[string]}   address  跳转地址，可以为空
 * @param  {Function} callback 回调函数
 */
function jumpTo(address, callback) {
	var localDomain = "tendawifi.com";
	//优先域名跳转
	var checkDomainRebootT = setInterval(function () {
		$.ajax({
			type: "get",
			url: "http://" + localDomain + "/goform/getRebootStatus?random=" + Math.random(),
			dataType: "jsonp",
			jsonp: "callback", //传递给请求处理程序或页面的，用以获得jsonp回调函数名的参数名(一般默认为:callback)
			jsonpCallback: "flightHandler", //自定义的jsonp回调函数名称，默认为jQuery自动生成的随机函数名，也可以写"?"，jQuery会自动为你处理数据
			success: function (json) {
				(typeof callback == "function") && callback();
				clearInterval(checkDomainRebootT);
				clearInterval(checkRebootT);
				top.location.href = "http://" + localDomain;

			}
		});
	}, 2000);

	var checkRebootT;
	setTimeout(function () {
		//IP获取数据，跳转IP
		if (localDomain != address) {
			checkRebootT = setInterval(function () {
				$.ajax({
					type: "get",
					url: "http://" + address + "/goform/getRebootStatus?random=" + Math.random(),
					dataType: "jsonp",
					jsonp: "callback", //传递给请求处理程序或页面的，用以获得jsonp回调函数名的参数名(一般默认为:callback)
					jsonpCallback: "flightHandler", //自定义的jsonp回调函数名称，默认为jQuery自动生成的随机函数名，也可以写"?"，jQuery会自动为你处理数据
					success: function (json) {
						(typeof callback == "function") && callback();
						clearInterval(checkRebootT);
						clearInterval(checkDomainRebootT);
						top.location.href = "http://" + address;
					}
				});
			}, 2000);
		}
	}, 2010)

}

function isTimeout(str) {
	if (str.indexOf("<!DOCTYPE") != -1) {
		top.location.reload(true);
		return false;
	}
	return true;
}

function randomString() {
	var len = 8;
	var $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	var maxPos = $chars.length;
	var pwd = '';
	for (i = 0; i < len; i++) {
		pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
	}

	return pwd;
}


function str_decode(str) {
	return utf8to16(base64decode(str))
}

function str_encode(str) {
	return base64encode(utf16to8(str));
}

function utf8to16(str) {
	var out, i, len, c;
	var char2, char3;

	out = "";
	len = str.length;
	i = 0;
	while (i < len) {
		c = str.charCodeAt(i++);
		switch (c >> 4) {
		case 0:
		case 1:
		case 2:
		case 3:
		case 4:
		case 5:
		case 6:
		case 7:
			// 0xxxxxxx
			out += str.charAt(i - 1);
			break;
		case 12:
		case 13:
			// 110x xxxx   10xx xxxx
			char2 = str.charCodeAt(i++);
			out += String.fromCharCode(((c & 0x1f) << 6) | (char2 & 0x3f));
			break;
		case 14:
			// 1110 xxxx  10xx xxxx  10xx xxxx
			char2 = str.charCodeAt(i++);
			char3 = str.charCodeAt(i++);
			out += String.fromCharCode(((c & 0x0f) << 12) |
				((char2 & 0x3f) << 6) |
				((char3 & 0x3f) << 0));
			break;
		}
	}

	return out;
}

function base64encode(str) {
	var out, i, len;
	var c1, c2, c3;

	len = str.length;
	i = 0;
	out = "";
	while (i < len) {
		c1 = str.charCodeAt(i++) & 0xff;
		if (i == len) {
			out += base64EncodeChars.charAt(c1 >> 2);
			out += base64EncodeChars.charAt((c1 & 0x3) << 4);
			out += "==";
			break;
		}
		c2 = str.charCodeAt(i++);
		if (i == len) {
			out += base64EncodeChars.charAt(c1 >> 2);
			out += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
			out += base64EncodeChars.charAt((c2 & 0xF) << 2);
			out += "=";
			break;
		}
		c3 = str.charCodeAt(i++);
		out += base64EncodeChars.charAt(c1 >> 2);
		out += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
		out += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >> 6));
		out += base64EncodeChars.charAt(c3 & 0x3F);
	}
	return out;
}

function base64decode(str) {
	var c1, c2, c3, c4;
	var i, len, out;

	len = str.length;

	i = 0;
	out = "";
	while (i < len) {

		do {
			c1 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
		} while (i < len && c1 == -1);
		if (c1 == -1)
			break;


		do {
			c2 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
		} while (i < len && c2 == -1);
		if (c2 == -1)
			break;

		out += String.fromCharCode((c1 << 2) | ((c2 & 0x30) >> 4));


		do {
			c3 = str.charCodeAt(i++) & 0xff;
			if (c3 == 61)
				return out;
			c3 = base64DecodeChars[c3];
		} while (i < len && c3 == -1);
		if (c3 == -1)
			break;

		out += String.fromCharCode(((c2 & 0xf) << 4) | ((c3 & 0x3c) >> 2));


		do {
			c4 = str.charCodeAt(i++) & 0xff;
			if (c4 == 61)
				return out;
			c4 = base64DecodeChars[c4];
		} while (i < len && c4 == -1);
		if (c4 == -1)
			break;
		out += String.fromCharCode(((c3 & 0x03) << 6) | c4);
	}
	return out;
}

function utf16to8(str) {
	var out, i, len, c;

	out = "";
	len = str.length;
	for (i = 0; i < len; i++) {
		c = str.charCodeAt(i);
		if ((c >= 0x0001) && (c <= 0x007F)) {
			out += str.charAt(i);
		} else if (c > 0x07FF) {
			out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F));
			out += String.fromCharCode(0x80 | ((c >> 6) & 0x3F));
			out += String.fromCharCode(0x80 | ((c >> 0) & 0x3F));
		} else {
			out += String.fromCharCode(0xC0 | ((c >> 6) & 0x1F));
			out += String.fromCharCode(0x80 | ((c >> 0) & 0x3F));
		}
	}
	return out;
}


var base64EncodeChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
var base64DecodeChars = new Array(-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63,
	52, 53, 54, 55, 56, 57, 58, 59, 60, 61, -1, -1, -1, -1, -1, -1, -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14,
	15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, -1, -1, -1, -1, -1, -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
	41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1, -1, -1
);

/*检查IP 掩码的合法性*/
function checkIsVoildIpMask(ip, mask) {
	var mask_arry = mask.split("."),
		ip_arry = ip.split("."),
		mask_arry2 = [],
		maskk,
		netIndex = 0,
		netIndexl = 0,
		bIndex = 0;
	if (ip_arry[0] == 127) {
		return _("The IP address cannot begin with 127.");
	}
	if (ip_arry[0] == 0 || ip_arry[0] >= 224) {
		return _("Incorrect IP address.");
	}

	for (var i = 0; i < 4; i++) { // IP & mask
		if ((ip_arry[i] & mask_arry[i]) == 0) {
			netIndexl += 0;
		} else {
			netIndexl += 1;
		}
	}

	for (var i = 0; i < mask_arry.length; i++) {
		maskk = 255 - parseInt(mask_arry[i], 10);
		mask_arry2.push(maskk);
	}
	for (var k = 0; k < 4; k++) { // ip & 255-mask
		if ((ip_arry[k] & mask_arry2[k]) == 0) {
			netIndex += 0;
		} else {
			netIndex += 1;
		}
	}
	if (netIndex == 0 || netIndexl == 0) {
		return _("The IP address must not indicate a network segment.");
	}
	for (var j = 0; j < 4; j++) { // ip | mask
		if ((ip_arry[j] | mask_arry[j]) == 255) {
			bIndex += 0;
		} else {
			bIndex += 1;
		}
	}

	if (bIndex == 0) {
		return _("The IP address cannot be a broadcast IP address.");
	}
}

//usb容量条
(function ($) {
	var create = function (ele, percent, width, showTxt) {
		$(ele).addClass("progress-bar");
		ele.innerHTML = "<span class='progress-btm'></span><p class='progress-txt'></p>";
		ele.style.width = width + "px";
		setTimeout(function () {
			setPercent(ele, percent, showTxt);
		}, 10);

	}
	var setPercent = function (ele, percent, showTxt) {
		var showTxt = showTxt ? showTxt : percent + "%";
		if (/\.\d{3,}/.test(percent.toString())) {
			percent = Math.ceil(percent * 100) / 100;
		}
		$(ele).find(".progress-btm").css("width", percent + "%");
		/*if(percent>100){
		    $(ele).find(".progress-btm").after("<span class='progress-txt'>"+showTxt+"</span>");
		}else{
		    if(percent>98){
		        $(ele).find(".progress-btm").css("border-radius","4px").html("<span class='progress-txt'>"+showTxt+"</span>");
		    }else{
		        $(ele).find(".progress-btm").css("border-radius","4px 0 0 4px").html("<span class='progress-txt'>"+showTxt+"</span>");
		    }
		}*/
		$(ele).find(".progress-txt").html(showTxt);
	}

	$.fn.toProgress = function (percent, width, showTxt) {
		var percent = percent ? (parseFloat(percent) <= 100 && parseFloat(percent) >= 0 ? parseFloat(percent) : 0) : 0,
			width = width ? (parseInt(width) > 30 ? parseInt(width) : 100) : 100;
		this.each(function () {
			create(this, percent, width, showTxt);
			var that = this;
		});
		return this;
	}
})($);


/**
 * 定时刷新类 by zzc
 * 约定是返回json数据
 * @method startUpdate 开始更新 创建对象后自动调用一次
 * @method stopUpdate 停止更新
 */
function AjaxInterval(options) {
	var defaults = {
			url: "", //更新数据的url
			data: "", //更新数据的url附带数据
			successFun: null, //更新成功的回调
			errorFun: null, //更新异常（网络问题）的回调
			gapTime: -1 //更新间隔时间
		},
		nextT = 1,
		handling = false,
		stop = false,
		errorWaitTime = 2000;

	options = $.extend(defaults, options);

	function update() {
		if (handling || stop) {
			return;
		}
		clearTimeout(nextT);
		handling = true;
		$.ajax({
			"type": "get",
			"url": options.url + "?" + Math.random(),
			"data": "",
			"success": function (data) {
				if (data.indexOf("<!DOCTYPE") != -1) {
					window.location.reload(true);
					return false;
				}

				data = $.parseJSON(data);

				var goOnUpdate = options.successFun(data);
				if (goOnUpdate == "-1") {
					stop = true;
				}
				handling = false;
				if (!stop)
					if (options.gapTime != -1) {
						nextT = setTimeout(update, options.gapTime);
					}
			},
			"error": function (a, b, errorThrown) {
				options.failFun && options.failFun();
				handling = false;
				if (!stop)
					if (options.gapTime != -1) {
						nextT = setTimeout(update, options.gapTime);
					}
			}
		});
	}
	update();

	this.startUpdate = function () {
		stop = false;
		update();
	}
	this.stopUpdate = function () {
		stop = true;
	}
}


/**
 * jq自动纠错插件 by zzc
 * 在输入框元素keyup blur的时候纠错
 */
!(function () {
	var corrector = {
		ip: function (str) {
			var curVal = str,
				ipArr;
			curVal = curVal.replace(/([^\d\.]|\s)/g, "");

			ipArr = curVal.split(".");
			$.each(ipArr, function (i, ipPart) {
				ipArr[i] = (ipArr[i] == "" ? "" : parseInt(ipPart, 10));
			});
			return ipArr.join(".");
		},
		mac: function (str) {
			var curVal = str;
			curVal = curVal.replace(/([^\d\:a-fA-F]|\s)/g, "");
			return curVal;
		},
		num: function (str) {
			var curVal = str;
			curVal = curVal.replace(/([^\d]|\s)/g, "");
			return isNaN(parseInt(curVal, 10)) ? "" : parseInt(curVal, 10) + "";
		},
		float: function (str) {
			var curVal = str;
			curVal = curVal.replace(/([^\d\.]|\s)/g, "");
			if (/\./.test(curVal)) {
				var split = curVal.split(".");
				curVal = split[0] + ".";
				split.shift();
				curVal += split.join("");
			}
			return curVal;
		}
	}
	$.fn.inputCorrect = function (type) {
		this.each(function () {
			$(this).on("keyup blur", function () {
				if (this.value == "") return;
				var newVal = corrector[type](this.value);
				if (newVal != this.value) {
					this.value = newVal;
				}
			});
		});
		return this;
	}
})();

//判断时间是否重叠, 传入的是整型 如16:30 ---> 数字 1630
function isTimeOverlaping(timeAStart, timeAEnd, timeBStart, timeBEnd) {
	timeAStart = parseInt(timeAStart, 10);
	timeAEnd = parseInt(timeAEnd, 10);
	timeBStart = parseInt(timeBStart, 10);
	timeBEnd = parseInt(timeBEnd, 10);

	if (timeAStart > timeAEnd && timeBStart > timeBEnd) {
		return true;
	} else if (timeAStart > timeAEnd) {
		return !(timeAStart >= timeBEnd && timeAEnd <= timeBStart);
	} else if (timeBStart > timeBEnd) {
		return !(timeBStart >= timeAEnd && timeBEnd <= timeAStart);
	} else {
		return !(timeAStart >= timeBEnd || timeAEnd <= timeBStart);
	}
}


$.GetSetData = {
	getData: function (url, handler) {
		if (url.indexOf("?") < 0) {
			url += "?" + Math.random();
		}
		$.ajax({
			url: url,
			cache: false,
			type: "get",
			dataType: "text",
			async: true,
			success: function (data, status) {
				if (data.indexOf("<!DOCTYPE") != -1) {
					window.location.reload(true);
					return false;
				}

				if (data === "") {
					alert(_("Please exit your security suite to display this webpage normally."));
				}

				if (typeof handler == "function") {
					handler.apply(this, arguments);
				}
			},
			error: function (msg, status) {
				if (typeof handler == "function") {
					//handler.apply(this, arguments);
				}
			},
			complete: function (xhr) {
				xhr = null;
			}
		});

	},

	getJson: function (url, handler) {
		this.getData(url, function (data) {
			handler($.parseJSON(data));
		});
	},

	setData: function (url, data, handler) {
		$.ajax({
			url: url,
			cache: false,
			type: "post",
			dataType: "text",
			async: true,
			data: data,
			success: function (data) {
				if (data.indexOf("<!DOCTYPE") != -1) {
					window.location.reload(true);
					return false;
				}
				if ((typeof handler).toString() == "function") {
					handler(data);
				}
			}
		});
	}
};


//转换剩余容量单位 单位GB 
//TODO: 删除 samba.js getSpaceSpecify函数
function translateCapacity(capacityG) {
	var cGB = capacityG,
		cMB = cGB * 1024,
		cTB,
		str;

	//cGB = (cMB >= 1024 ? (cMB / 1024).toFixed(1) : 0);
	cTB = (cGB >= 1024 ? (cGB / 1024).toFixed(2) : 0);

	if (cTB >= 1) {
		str = Number(cTB) + "TB";
	} else if (cGB >= 1) {
		if (Number(cGB) == parseInt(cGB)) {
			str = Number(cGB) + "GB";
		} else {
			str = Number(cGB).toFixed(2) + "GB";
		}

	} else {
		if (Number(cMB) == parseInt(cMB)) {
			str = Number(cMB) + "MB";
		} else {
			str = Number(cMB).toFixed(1) + "MB";
		}
	}

	return str;
}

//速度转换单位
function translateSpeed(speedKB) {
	if (Number(speedKB) < 1024) {
		return Number(speedKB).toFixed(1) + "KB/s";
	} else {
		return (speedKB / 1024).toFixed(1) + "MB/s";
	}
}


/******************************************************************************
  Modify by Milo.z
******************************************************************************/

/**创建一个类*/
var Class = function () {};
var R;

/**类的继承*/
Class.prototype.extend = extend;
/**
 *实现对象的继承，用于对象的扩展
 *@returns { object} 返回一个继承后的对象
 */
function extend() {
	var target = arguments[0] || {};
	var len = arguments.length;
	var src;

	if (len === 1) {

	} else {
		for (var i = 0; i < len; i++) {
			if (target == arguments[i]) {
				continue;
			} else {
				for (var name in arguments[i]) {
					if (Object.prototype.toString.call(arguments[i][name]) === "[object Object]") {
						target[name] = R.extend(target[name], arguments[i][name]);
					} else if (arguments[i][name] != "undefined") {
						target[name] = arguments[i][name];
					}
				}

			}
		}

	}
	return target;
}


/**
 * [Base 基类]
 * @type {Base}
 * @property {object} view  -   基类view
 * @property {object} model -   基类model 
 */
var Base = new Class;


/**
 * 基类的view
 * @requires Base
 * @returns {class} [基类view的基本方法]
 * @type {Object}
 */
Base.view = {
	/**
	 * [init 基类view的初始化，默认执行事件绑定]
	 */
	init: function () {
		this.initEvent();
	},
	/**
	 * [initEvent view初始化事件函数]
	 */
	initEvent: function () {},
	showMsg: function (msg) {
		if ($("#msg-err").length == 1) {
			showErrMsg("msg-err", msg);
		} else {
			alert(msg);
		}

	}
};

/**
 * @augments  Base
 * @returns {class} [基类的基本方法]
 * @type {Object}
 */
Base.model = {
	/**
	 * [translateData 数据转换]
	 * @param  {object} data [需要转换的数据]
	 * @return {object}      [转换之后的数据]
	 */
	translateData: function (data) {
		return data;
	},

	/**
	 * [getSubmitData 获取提交数据]
	 * @return {string} [返回需要提交的字符串数据]
	 */
	getSubmitData: function () {

	}
};

R = new RouterPage();
/**
 * @classdesc Class representing a socket connection.
 * @class
 * @augments {function}
 */
function RouterPage() {
	/**
	 * @module moduleView
	 * @description 初始化dom、数据验证
	 * @function moduleView
	 * @memberof RouterPage
	 * @inner
	 * 模块视图
	 * @returns {object}
	 * @param {object} [config] 具体模块的view对象
	 */
	this.constructor = "RouterPage";

	this.moduleView = function (config) {
		var modules = { //模块内部特有属性
			initHtml: function () {},
			checkData: function () {
				return "";
			}
		};
		var obj = Base.extend({}, Base.view, modules, config);
		return obj;
	};

	//模块Model
	/**
	 * @description 数据初始化、数据提交、数据转化
	 * @function moduleModel
	 * @memberof RouterPage
	 * @param {object} [config] 模块model
	 * @returns {object}
	 * @property {function} [initData] 初始化数据，调用数据转换函数
	 **/
	this.moduleModel = function (config) {
		var modules = { //模块特有数据初始化
			initData: function (data) {
				data = this.translateData(data);
			}
		};
		var obj = Base.extend({}, Base.model, modules, config);
		return obj;
	};

	//模块注册
	/**
	 * module 模块注册函数
	 * @function module
	 * @memberof RouterPage
	 * @param  {string} moduleName 模块名称
	 * @param  {object} view       模块view对象
	 * @param  {object} model      模块model对象
	 * @return {object}            整个模块
	 * @property {function} [init] 模块初始化，调用view的初始化
	 * @property {object} [view]   模块的view接口
	 * @property {object} [model]  模块的model接口
	 * @property {string} [name]   模块名称
	 */
	this.module = function (moduleName, view, model) {
		function ModuleLogic(moduleName, view, model) {
			this.view = view;
			this.model = model;
			this.name = moduleName; //模块名称
			this.init = function () { //模块内部入口
				view.init();
				view.initHtml();
			};
			//页面模块数组
			R.pageModules.push(this);
			return this;
		}
		return new ModuleLogic(moduleName, view, model);
	};

	/*********页面方法********************/
	/**
	 *页面view
	 *@function pageView
	 *@memberOf RouterPage
	 *@param {object} [config] 页面view对象
	 *@property {function} [checkData] 检查数据合法性
	 */
	this.pageView = function (config) {
		config = config || {};
		R.pageModules = [];
		var modules = {
			checkData: function () { //页面数据验证，主要调用子模块的数据验证函数
				var msg = "";
				var modules = R.pageModules;
				for (var i = 0; i < modules.length; i++) {
					msg = modules[i].view.checkData();
					if (msg) {
						return msg;
					}
				}
				return msg;
			}
		};
		var obj = Base.extend({}, Base.view, modules, config);
		return obj;
	};


	/**
	 *数据提交和提交前的操作
	 *@function pageModel
	 *@memberOf RouterPage
	 *@param {object} [config] page的model配置
	 *@property {string} [getUrl] 获取数据的接口
	 *@property {string} [setUrl] 提交数据的接口
	 *@property {object} [originalData] page的原始数据
	 *@property {object} [defaultData] page默认数据
	 *@property {function} [getData] [获取页面数据接口]
	 *
	 */
	this.pageModel = function (config) {
		config = config || {};
		//页面model特有属性
		var model = {
			getUrl: "", //获取数据接口
			setUrl: "", //提交数据接口
			originalData: null, //原始数据
			defaultData: null, //默认数据
			getData: function () { //页面获取数据函数
				//TODO: 实现获取数据操作

				var _this = this;
				if (!this.getUrl) {
					return;
				}
				$.getJSON(this.getUrl + "?" + Math.random(), function (data) {
					_this.initData.call(_this, data);
				});
				//this.originalData = data; //获取到数据之后需要先保存到原始数据中
				//this.initData(); //回调
			},
			update: function () {
				this.getData();
			},
			initData: function (data) { //初始化数据
				data = this.translateData(data);
				//统一赋值，最好是自定义初始化函数
				//TODO: 数据对象与模块名称一一对应
				var modules = R.pageModules;
				for (var i = 0; i < modules.length; i++) {
					if (data && data[modules[i].name] && typeof data[modules[i].name] === "object") {
						//初始化各个模块的数据
						modules[i].model.initData(data[modules[i].name]);
					}
				}
			},
			beforeSubmit: function () { //数据提交前操作
				return true; //返回true表示继续执行submit  false表示不执行，返回
			},
			submitData: function () { //默认提交动作 具体需在页面执行

			},
			afterSubmit: function (str) { //数据提交后的操作
				this.getData();
			},
			getSubmitData: function () { //页面获取提交数据接口
				var dataStr = "";
				var modules = R.pageModules;
				for (var i = 0; i < modules.length; i++) {
					dataStr += modules[i].model.getSubmitData() + "&";
				}
				return dataStr.replace(/&$/, "");
			}
		};
		var obj = Base.extend({}, Base.model, model, config);
		return obj;
	};

	this.page = function (pageView, pageModel) {
		function PageLogic(pageView, pageModel) {
			this.init = function () {
				var modules = R.pageModules; //初始化各个模块
				pageView.init(); //初始化页面

				for (var i = 0; i < modules.length; i++) {
					modules[i].init();
				}
				pageModel.getData.call(pageModel); //页面获取数据
			};

			this.view = pageView;
			this.model = pageModel;

			this.submit = function () { //提交数据接口
				var msg = "";
				var dataStr = "";

				//数据验证
				msg = pageView.checkData(); //执行各个模块数据验证
				if (msg) {
					// TODO: 显示提示信息
					pageView.showMsg(msg);
					return;
				}

				//执行提交前动作
				if (!pageModel.beforeSubmit()) {
					return;
				}

				dataStr = pageModel.getSubmitData();

				pageModel.submitData();
				//TODO: 执行提交动作
				$.post(pageModel.setUrl, dataStr, function (str) {
					pageModel.afterSubmit(str);
				});
			};

			this.init();

			return this;
		}
		return new PageLogic(pageView, pageModel);
	};
}

RouterPage.prototype.extend = Base.extend;

//扩展工具
R.extend(R, {
	/******************
	 *功能：判断是否超时
	 *输入：字符串
	 *输出：true表示已超时， false表示未超时
	 ******************/
	isTimeOut: function (str) {
		if (str.indexOf("<!DOCTYPE html") === 0 && str.indexOf("login.js?ddf99c235f9eb897c1c37f8e2199b7d2") != -1) {
			return true;
		}

		return false;
	},

	/**
	 * 显示页面提示信息
	 * @param {string} [description]
	 */
	showMsg: function (elemId, msg) {
		var containerId,
			msgStr,
			durationTime;

		//如果只有一个参数，设置默认的显示ID
		if (typeof msg == "undefined") {
			containerId = "";
			msgStr = elemId;
		} else {
			containerId = elemId;
			msgStr = msg;
		}

		//提示持续时间
		durationTime = msgStr.length * 100;

		//
		$("#" + containerId).html(msgStr);
		setTimeout(function () {
			$("#" + containerId).html("&nbsp;");
		}, durationTime);
	},

	/**
	 * 实现数据转换
	 */
	configData: function (dataObj, dataArr) {
		var newObj = {},
			len = dataArr.length,
			i = 0;

		for (; i < len; i++) {
			newObj[dataArr[i]] = dataObj[dataArr[i]];
		}

		return newObj;
	}
});

/**
 * 检查设备名的合法性
 * @param {string} [devName] [设备名称]
 * @param {bool} [isCanNull] [设备名是否可以为空]
 */
function checkDevNameValidity(devName, isCanNull) {

	//设备名为空时不做判断
	if (isCanNull && devName == "") {
		return;
	}
	if (devName == "") {
		return _("Please enter a device name.");
	}

	if (devName.replace(/\s/g, "") == "") {
		return _("The device name must not consist only of spaces.");
	}

	if (devName.length > 20) {
		return _("The device name can contain only a maximum of %s bytes.", [20]);
	}
	return;
}


/**
 * 设备名称绑定事件，清除不支持的字符
 * @param  {dom} elem [输入框节点]
 * @return {mull}      
 */
function clearDevNameForbidCode(elem) {
	$(elem).on("blur.devname", function () {
		var value = this.value;
		this.value = value.replace(/[;]/g, "");
	});
}

//初始化有表格弹出层的高度
function initTableHeight() {
	var elems = $(".legend-main").children().not($("table").parent()),
		frameHeight = 0,
		bodyHeight = $("body").height(),
		tableHeight;

	//获取除table之外的高度
	//只适应于没有隐藏显示切换的表格元素
	$(elems).each(function () {
		if (!$(this).hasClass("none")) {
			frameHeight += $(this).outerHeight(true);
		}
	});

	tableHeight = bodyHeight - frameHeight - 30;

	if (tableHeight < 200) {
		tableHeight = 200;
	}

	//解决ios safari弹出框滚动条问题
	$("body").find("fieldset").removeClass("scroll-wrapper");
	$("table").parent().css("margin-bottom", "10px");
	$("table").parent().css("height", tableHeight + "px");
}


function getTimeString() {
	var hour_str = "",
		min_str = "",
		i = 0,
		k = 0;
	for (i = 0; i < 24; i++) {
		hour_str += "<option value='" + ((100 + i).toString()).slice(1, 3) + "'>" + ((100 + i).toString()).slice(1, 3) + "</option>";
	}
	for (k = 0; k < 60; k++) {
		if (k % 5 != 0) {
			continue;
		}
		min_str += "<option value='" + ((100 + k).toString()).slice(1, 3) + "'>" + ((100 + k).toString()).slice(1, 3) + "</option>";
	}

	return {
		hour: hour_str,
		minute: min_str
	}
}

/**
 * 统一转换设备类型
 * @param  {string} str [设备类型值]
 * @return {string}     [返回页面显示的类名]
 */
function translateDeviceType(str) {

	var classStr = "",
		obj = {};
	/*if (!str) {
		return;
	}*/
	str = str || "unknown";
	//存在时
	if (top.G.deviceList.indexOf(str) != -1) {
		obj = {
			src: "./img/device/" + str + ".png",
			exsit: true,
			logoTxt: ""
		}
	} else if (top.G.noDeviceList.indexOf(str) != -1) {
		//设备未支持的手机图标
		obj = {
			src: "./img/device/others.png?ddf99c235f9eb897c1c37f8e2199b7d2",
			exsit: false,
			logoTxt: str.slice(0, 2).toUpperCase()
		}
	} else {
		$.ajax({
			type: "get",
			url: "/img/device/" + str + ".png?random=" + Math.random(),
			async: false,
			success: function (data) {
				top.G.deviceList.push(str);
				obj = {
					src: "./img/device/" + str + ".png",
					exsit: true,
					logoTxt: ""
				}
			},
			error: function (ajaxObj, status) {
				top.G.noDeviceList.push(str);
				obj = {
					src: "./img/device/others.png?ddf99c235f9eb897c1c37f8e2199b7d2",
					exsit: false,
					logoTxt: str.slice(0, 2).toUpperCase()
				}
			}
		});
	}

	return obj;

}

function showDeviceLogoString(obj, deviceType) {
	if (!obj.exsit) {
		return "<div class='txt-device' title='" + deviceType + "'>" + deviceType.slice(0, 2).toUpperCase() + "</div>";
	} else {
		return "";
	}
}
/**
 * 获取浏览器的语言
 * @return {string} 当前浏览器的语言
 */
function getBrowserLang() {
	var special = {
			"zh": "cn",
			"zh-chs": "cn",
			"zh-cn": "cn",
			"zh-cht": "cn",
			"zh-hk": "cn",
			"zh-mo": "zh",
			"zh-tw": "cn",
			"zh-sg": "zh"
		},
		localLang = (window.navigator.language || window.navigator.userLanguage ||
			window.navigator.browserLanguage || window.navigator.systemLanguage || B.getLang()).toLowerCase(),
		browserLang;

	browserLang = special[localLang] || localLang.split("-")[0].toString();
	browserLang = browserLang.toUpperCase();
	return browserLang;
}