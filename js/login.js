/**
 * Created by lrj on 2015/11/29.
 */
$(function() {
   // 注册事件
   $("#stove_login").on("click", stove_login);
   $("#switch_to_register").on("click", stove_switch_to_register);
   $("#stove_register").on("click", stove_register);
   $("#switch_to_login").on("click", stove_switch_to_login);
   $("#rememberme").on("click", stove_check_cookie);

   // 读取cookie
   var cookie = stove_read_login_cookie();
   if (!stove_is_empty_str(cookie['user_code'])){
       $("#user_code").val(cookie['user_code']);
       $("#user_pwd").val(cookie['user_pwd']);
       $("#rememberme")[0].checked=true;
   }
 }
);

function stove_login(){
    var user_code = $("#user_code")[0].value;
    var user_pwd = $("#user_pwd")[0].value;
    if ((user_pwd.length == 0) || (user_pwd.length == null) || (user_pwd.length == undefined)){
        stove_alert("请输入密码");
        $("#user_pwd")[0].focus();
        return;
    }

    if (!stove_validate_mail(user_code)){
        stove_alert("请使用正确的邮箱地址");
        $("#user_code")[0].focus();
        return;
    }

    var server_url = "/php/action/login.php";
    if ($("#rememberme")[0].checked)
        stove_save_login_cookie(user_code, user_pwd);
    else
        stove_clear_login_cookie();

    $.ajax({
        url: server_url,
        data: {
          user_code: user_code,
          user_pwd: user_pwd,
          action: "login"
        },
        type:'POST',
        dataType: 'json',
        /*success: function(data){
            alert(data);
        },*/
        success: stove_show_login_result,
        error: function(xhr, statusCode, statusMsg){
            stove_alert("登录错误:" + statusCode + "-" + statusMsg);
        }
    });
}

function stove_show_login_result(retdata) {
    if (retdata.status == 0){
        var location = window.location.href;
        var index = location.indexOf('?to=');
        if (index > -1){
            window.location = location.substr(index + 4);
        }
        else
            window.location = "/html/stove_main.html";
    }
    else{
        $("#user_pwd")[0].value="";
        $("#user_code")[0].focus();
        stove_alert(retdata.description);
    }
}

function stove_switch_to_register(){
    $(".register-fields").show();
    $(".login-fields").hide();
}

function stove_switch_to_login(){
    $(".register-fields").hide();
    $(".login-fields").show();
}

function stove_register(){
    var user_code = $("#user_code")[0].value;
    var user_pwd1 = $("#user_pwd")[0].value;
    var user_pwd2 = $("#user_pwd2")[0].value;
    var user_nick = $("#user_nick")[0].value;

    if ((user_code.length == 0) || (user_code == null) || (user_code == undefined)){
        stove_alert("请输入注册用户名");
        $("#user_code")[0].focus();
        return;
    }

    if ((user_nick.length == 0) || (user_nick.length == null) || (user_nick.length == undefined)){
        stove_alert("请输入笔名");
        $("#user_nick")[0].focus();
        return;
    }

    if ((user_pwd1.length == 0) || (user_pwd1.length == null) || (user_pwd1.length == undefined)){
        stove_alert("请输入密码");
        $("#user_pwd")[0].focus();
        return;
    }

    if ((user_pwd2.length == 0) || (user_pwd2.length == null) || (user_pwd2.length == undefined)){
        stove_alert("请再次输入密码");
        $("#user_pwd2")[0].focus();
        return;
    }

    if (user_pwd1 != user_pwd2){
        stove_alert("两次输入的密码不一致");
        $("#user_pwd2")[0].focus();
        return;
    }

    if (!stove_validate_mail(user_code)){
        stove_alert("请使用正确的邮箱地址");
        $("#user_code")[0].focus();
        return;
    }

    var url = "/php/action/login.php";
    if ($("#rememberme")[0].checked)
        stove_save_login_cookie(user_code, user_pwd1);
    else
        stove_clear_login_cookie();

    $.ajax({
        url: url,
        data: {
            user_code: user_code,
            user_pwd: user_pwd1,
            user_nick: user_nick,
            action: "register"
        },
        type:'POST',
        dataType: 'json',
        success: stove_show_register_result,
        error: function(xhr, statusCode, statusMsg){
            stove_alert("登录错误:" + statusCode + "-" + statusMsg);
        }
    });
}

function stove_show_register_result(retdata) {
    if (retdata.status == 0)
        window.location = "/sample/upload.html";
    else{
        $("#user_pwd")[0].value="";
        $("#user_pwd2")[0].value="";
        $("#user_code")[0].focus();
        stove_alert(retdata.description);
    }
}

function stove_check_cookie(){
    if ((navigator.cookieEnabled == false) && $("#rememberme")[0].checked == true) {
        stove_alert("请开启浏览器的Cookie功能");
    }
}