var domain = 'http://' + window.location.host;
var myurl = window.location.href;
var data = {
    'url': myurl
};
if (myurl.indexOf('Goods') != -1 || myurl.indexOf('Goods') != -1 || myurl.indexOf('details') != -1 || window.location.href.indexOf('tb') != -1) {
    menu_list = ['menuItem:share:qq',
        'menuItem:share:weiboApp',
        'menuItem:favorite',
        'menuItem:share:facebook',
        'menuItem:copyUrl', // 复制链接:
        'menuItem:originPage', // 原网页
        'menuItem:share:email',
        'menuItem:share:QZone'];
} else {
    menu_list = ['menuItem:share:qq',
        'menuItem:share:weiboApp',
        'menuItem:favorite',
        'menuItem:share:facebook',
        'menuItem:copyUrl', // 复制链接:
        'menuItem:originPage', // 原网页
        'menuItem:share:appMessage',
        'menuItem:openWithQQBrowser',
        'menuItem:share:timeline',
        'menuItem:openWithSafari',
        'menuItem:share:email',
        'menuItem:share:QZone'];
}

$.ajax({
    url: domain + "/Home/User/getShareInfo.html",
    type: 'post',
    dataType: 'json',
    data: data,
    success: function (data) {
        wx.config({

            debug: false,
            appId: data.signPackage.appId,
            timestamp: data.signPackage.timestamp,
            nonceStr: data.signPackage.nonceStr,
            signature: data.signPackage.signature,
            jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage', 'hideMenuItems']
        });
        wx.ready(function () {
            if (window.location.href.indexOf('tb') != -1) {
                var nowurl = "tao.hena360.com"
            } else {
                var nowurl = window.location.href;
            }
            //去除url中不必要参数
            nowurl = nowurl.replace(/&amp;mid=\d+/,'');
            if (nowurl.indexOf('mid') == -1) {
                if (nowurl.indexOf('?') == -1) {
                    nowurl += '?mid=' + data.mid;
                } else {
                    nowurl += '&mid=' + data.mid;
                }
            }else{
                nowurl = nowurl.replace(/&mid=\d+/,'&mid='+data.mid);
            }
            //去除url中不必要参数
            nowurl = nowurl.replace(/&from=groupmessage/,'');
            nowurl = nowurl.replace(/&amp;from=groupmessage/,'');
            //分享功能
            wx.onMenuShareTimeline({
                title: '自用省钱，躺着赚钱！福利0元抢，爆品限时抢，海量折扣单品……',
                link: nowurl,
                desc: '生态化布局，亮点多多，收益多多，总能留住你的心！加入合纳，人生不将就！', // 分享描述
                imgUrl:'http://img.hena360.cn/wxshare.jpg',
                success: function () {
                    alert("分享成功");
                },
                cancel: function () {
                    alert('你没有分享到朋友圈');
                }
            });
            wx.onMenuShareAppMessage({
                title: '自用省钱，躺着赚钱！福利0元抢，爆品限时抢，海量折扣单品……',
                link: nowurl,
                desc: '生态化布局，亮点多多，收益多多，总能留住你的心！加入合纳，人生不将就！', // 分享描述
                imgUrl:'http://img.hena360.cn/wxshare.jpg',
                success: function () {
                    alert("分享成功");
                    // 用户确认分享后执行的回调函数
                },
                cancel: function () {
                    alert('你没有分享给朋友');
                    // 用户取消分享后执行的回调函数
                }
            });
        })
    }
});