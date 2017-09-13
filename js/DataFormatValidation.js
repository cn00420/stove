/**
 * Created by lrj on 2015/11/28.
 */

/**
 * 功能：校验邮箱地址
 * @param email
 */
function stove_validate_mail(email){
    var pattern = /(^([\w_\-\.]+))@{1}[\w_\-\.]+\.{1}[\w_\-\.]+$/g;
    var pass = pattern.test(email);
    return pass;
}