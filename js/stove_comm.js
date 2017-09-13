/**
 * Created by lrj on 2016/2/12.
 */
function stove_create_paper_section(papers, parent){
    for(var i = 0; i < papers.length; i++){
        var summary = papers[i]['PAPER_SUMMARY'].replace(/@stove_r@/g, ' ').replace(/@stove_n@/g, ' ');
        if (summary.length > 180){
            summary = summary.substr(0, 180) + "...";
        }

        var htmlelem = '<div class="col-lg-10 col-md-10 col-sm-10 col-xs-10"><div class="thumbnail paper-in-list">'
            + '<div class="post-meta"><img class="author-figure" src="' + papers[i]['AUTHOR']['FIGURE_URL']
            + '"><span>' + papers[i]['AUTHOR']['ALIAS'] + '</span>'
            + '<span>发表于' + papers[i]['PUB_TS'] + '</span>'
            + '<span>预计阅读' + Math.floor(papers[i]['SPEND_TIME']) + '分钟</span>'
            + '</div><a href="/php/action/view_paper.php?paper_id=' + papers[i]['PAPER_ID']
            + '"><div class="paper-title"> <h3>'  + papers[i]['PAPER_TITLE'] + '</h3></div>'
            + '<div class="paper-summary"><p>' + summary + '</p></div></a>'
            + '<div class="post-footer"><span>共有' + papers[i]['RECOMMENDATION_QTY'] + '位用户点赞</span></div>'
            + '</div></div>';

        $(htmlelem).appendTo(parent);
    }
}
