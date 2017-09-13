/**
 * Created by lrj on 2015/12/12.
 */
$(function(){
    // 注册事件
    $("#journal_link").on("click", stove_main_switch_to_journal);
    $("#paper_link").on("click", stove_main_switch_to_papers);
    $("#btn_load_more").on("click", stove_main_load_more);
    $('#search_tf').keyup(function(event){
        stove_jump_to_search(event);
    });

    stove_main_load_journals(1);
    stove_main_load_papers(1);
});

var stove_main_journal_page_index = 1;
var stove_main_paper_page_index = 1;
var stove_main_current_tab = "journal";
var stove_main_page_size = 8;
var stove_main_paper_tail = false;
var stove_main_journal_tail = false;
var session_user = null;

function stove_main_switch_to_journal(){
    $("#journals").show();
    $("#topics").hide();
    $("#papers").hide();

    stove_main_current_tab = "journal";

    if ($("#journals div.journal").length > 0)
        return;

    stove_main_load_journals(1);
}

function stove_main_load_journals(){
    var page_index = arguments[0];

    $.ajax({
        url: "/php/action/journal_mgr.php",
        data: {
            order_by: "1",
            page_index: page_index,
            action: "get-journals",
            page_size:stove_main_page_size
        },
        type:'POST',
        dataType: 'json',
        success: stove_main_create_journal_section,
        error: function(xhr, statusCode, statusMsg){
            stove_alert("读取刊物错误:" + statusCode + "-" + statusMsg);
        }
    });
}

function stove_main_create_journal_section(journals){
  for(var i = 0; i < journals.length; i++){
      var htmlelem = '<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3"><a href="/php/action/journal_center.php?journal_id='
          + journals[i]['JOURNAL_ID'] + '">'
          + '<div class="thumbnail journal" style="background-image: url(&quot;'
          + journals[i]['IMAGE_URL'] + '&quot;);"></div><div class="journal-name"><span>'
          + journals[i]['JOURNAL_NAME'] + '</span></div></div></a>';
      $(htmlelem).appendTo($("#journals"));
  }

  if (journals.length < stove_main_page_size)
    stove_main_journal_tail = true;
}

function stove_main_switch_to_papers(){
    $("#journals").hide();
    $("#topics").hide();
    $("#papers").show();

    stove_main_current_tab = "paper";

    if ($("#papers div.paper-in-list").length > 0)
        return;

    stove_main_load_papers(1);
}

function stove_main_load_papers(){
    var page_index = arguments[0];

    $.ajax({
        url: "/php/action/paper_mgr.php",
        data: {
            order_by: "1",
            page_index: page_index,
            page_size:stove_main_page_size,
            action: "get-papers"
        },
        type:'POST',
        dataType: 'json',
        success: stove_main_create_paper_section,
        error: function(xhr, statusCode, statusMsg){
            stove_alert("读取文章错误:" + statusCode + "-" + statusMsg);
        }
    });
}

function stove_main_create_paper_section(papers){
    var parent = $("#papers");
    stove_create_paper_section(papers, parent);

    if (papers.length < stove_main_page_size)
        stove_main_paper_tail = true;
}

function stove_main_load_more(){
    if (stove_main_current_tab == "journal")
        stove_main_load_more_journal();
    else if (stove_main_current_tab == "paper")
        stove_main_load_more_paper();
}

function stove_main_load_more_journal(){
    if (stove_main_journal_tail == false)
        stove_main_journal_page_index++;
    else{
        stove_alert("已经加载完所有的刊物");
        return;
    }
    stove_main_load_journals(stove_main_journal_page_index);
}

function stove_main_load_more_paper(){
    if (stove_main_paper_tail == false)
        stove_main_paper_page_index ++;
    else{
        stove_alert("已经加载完所有的文章");
        return;
    }
    stove_main_load_papers(stove_main_paper_page_index);
}

// 跳转到创刊界面
function stove_jump_to_journal(){
    // 如果已经有会话信息，直接跳转到创刊界面
    if (session_user){
        window.location = "/html/journal_mgr.html";
        return;
    }

    // 校验会话信息，决定是跳转到写稿界面还是登录界面
    stove_check_session(function(login_result){
        if ((login_result.status == 0) && (login_result.bo) && (login_result.bo.USER_ID > -1)){
            session_user = login_result.bo;
            window.location = "/html/journal_mgr.html";
        }
        else
            window.location = "/html/login.html?to=/html/journal_mgr.html";
    }, null);
}

// 跳转到写稿界面
function stove_jump_to_paper(){
    // 如果已经有会话信息，直接跳转到写稿界面
    if (session_user){
        window.location = "/php/action/write_paper.php";
        return;
    }

    // 校验会话信息，决定是跳转到写稿界面还是登录界面
    stove_check_session(function(login_result){
        if ((login_result.status == 0) && (login_result.bo) && (login_result.bo.USER_ID > -1)){
            session_user = login_result.bo;
            window.location = "/php/action/write_paper.php";
        }
        else
            window.location = "/html/login.html?to=/php/action/write_paper.php";
    }, null);
}

// 跳到search界面
function stove_jump_to_search(event){
    if (stove_is_text_char(event)){
        window.location = "/html/stove_search.html?key=" + $('#search_tf')[0].value;
    }
}