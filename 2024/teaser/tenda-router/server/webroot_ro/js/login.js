function PageLogic(view, authService) {

	this.init = function () {
		var that = this;

		view.init();
		view.addSubmitHandler(function () {
			that.validate.checkAll();
		});
	};

	this.validate = $.validate({
		custom: function () {
			var username = view.getUsername(),
				password = view.getPassword();

			function checksValid(username, password) {
				return username !== '' && password !== '';
			}

			if (!checksValid(username, password)) {
				return _("Please specify a login password.");
			}

		},

		success: function () {
			var data = view.getSubmitData();

			authService.login(data, view.showSuccessful, view.showError);
		},

		error: function (msg) {
			view.showInvalidError(msg);
		}
	});

}

function PageService(url) {
	this.method = 'asp';

	this.login = function (subData, successCallback, errorCallback) {
		$.ajax({
			url: url,
			type: "POST",
			data: subData,
			success: successCallback,
			error: errorCallback
		});
	};
}

function PageView() {
	this.getUsername = function () {
		return $('#username').val();
	};

	this.getPassword = function () {
		return $('#login-password').val();
	};

	this.getSubmitData = function () {
		var ret = '';

		ret = {
			username: this.getUsername(),
			password: hex_md5(this.getPassword())
		};

		return ret;
	};

	this.init = function () {
		var that = this;
		$("#forgetBtn").on("click", function () {
			that.toggleForgetText();
		});
	};

	this.addSubmitHandler = function (callBack) {
		$('#subBtn').on('click', function (e) {
			e.preventDefault();
			callBack.apply();
		});
		$(document).on("keydown", function (e) {
			var char_code = e.charCode || e.keyCode;

			if (char_code == 13) {
				if (e.preventDefault) {
					e.preventDefault();
				} else {
					e.returnValue = false;
				}
				callBack();
			}
		});
	};

	this.toggleForgetText = function () {
		$("#forgetMore").toggleClass("none");
	}

	this.showInvalidError = function (msg) {
		$('#login-message').html(msg || _("Please enter a login password."));
	};

	this.showSuccessful = function (str) {
		var num = str;
		if (num == 1) {
			$('#login-message').html(_("Incorrect password."));
		} else {
			window.location.href = "/main.html"; //解决android系统下360浏览器不能正常登陆问题
			//window.location.reload(true);
		}

		//location.reload();
	};

	this.showError = function () {
		$('#login-message').html(_("Committing the settings failed."));
	};

}
var base64EncodeChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

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

function str_encode(str) {
	return base64encode(utf16to8(str));
}

$(function () {
	var serviceUrl = '/login/Auth',
		authService = new PageService(serviceUrl),
		loginPageView = new PageView(),
		loginPageLogin = new PageLogic(loginPageView, authService);

	loginPageLogin.init();

	$('#login-password').initPassword(_("Please enter a login password."), true, false, function () {
		$('#login-message').html(_("You entered a capital letter."));
		setTimeout(function () {
			$('#login-message').html('&nbsp;');
		}, 2000);
	});

	if (top != window) {
		top.location.reload(true);
	}
	setTimeout(function () {
		$('#login-password_').focus();
	}, 100);
	$('#login-password').focus();

});