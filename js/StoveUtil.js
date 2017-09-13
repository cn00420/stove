/**
 * Created by lrj on 2015/12/3.
 */
function stove_alert(msg){
    alert(msg);
}

function stove_save_login_cookie(user_code, user_pwd){
    document.cookie = "user_code=" + encodeURIComponent(user_code) + "; max-age=600";
    document.cookie = "user_pwd=" + encodeURIComponent(user_pwd) + "; max-age=600";
}

function stove_clear_login_cookie(){
    document.cookie = "user_code=''; max-age=0";
    document.cookie = "user_pwd=''; max-age=0";
}

function stove_read_login_cookie(){
    var cookie = {};
    var cookie_string = document.cookie;
    if (cookie_string !== ""){
        var list = cookie_string.split(";");
        for(var i = 0; i < list.length; i++){
            var elem = list[i];
            var pair = elem.split("=");
            var name = decodeURIComponent(pair[0]).trim();
            var value = decodeURIComponent(pair[1]).trim();
            cookie[name] =value;
        }
    }

    return cookie;
}

function stove_is_empty_str(str){
    if ((str == null) || (str == undefined) || (str.length == 0))
      return true;
    else
      return false;
}

// 检测用户的登录状态
function stove_check_session(sucessfunc, failurefunc){
    var cookie = stove_read_login_cookie();
    var user_code = cookie["user_code"];
    var user_pwd = cookie["user_pwd"];

    if (stove_is_empty_str(user_code)) {
        $.ajax({
            url: "/php/action/login.php",
            data: {
                action: "get-session"
            },
            type: "POST",
            aysnc: false,
            dataType: "json",
            success: function (data) {
                if (sucessfunc)
                        sucessfunc(data);
            },
            error: function(xhr, statusCode, statusMsg){
                if (failurefunc)
                    failurefunc();
                else
                    stove_alert("登录错误:" + statusCode + "-" + statusMsg);
            }
        });
    }
    else{
        $.ajax({
            url: "/php/action/login.php",
            data: {
                user_code: user_code,
                user_pwd: user_pwd,
                action: "get-session"
            },
            type: "POST",
            async: true,
            dataType: "json",
            success: function (data) {
                if (sucessfunc)
                    sucessfunc(data);

            },
            error: function(xhr, statusCode, statusMsg){
                if (failurefunc)
                    failurefunc();
                else
                    stove_alert("登录错误:" + statusCode + "-" + statusMsg);
            }
        });
    }
}

function stove_get_value_from_url(url, param){
    var result = '';
    var index = url.indexOf('?');
    if (index < 0)
        return result;

    var paramPart = url.substr(url.indexOf('?') + 1);
    var paramArray = paramPart.split('&');

    for(var i = 0; i < paramArray.length; i++){
        var p = paramArray[i];
        index = p.indexOf('=');
        if (index > 0){
            var key = p.substr(0, index);
            var value = p.substr(index + 1);
            if (key == param) {
                result = value;
                break;
            }
        }
    }

    return result;
}

// 是否输入了有效的文本字符
function stove_is_text_char(event){
    if (event.altKey || event.ctrlKey || event.which < 32)
        return false;
    else
        return true;
}