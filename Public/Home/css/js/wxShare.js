var domain = 'http://'+window.location.host;

var myurl = window.location.href;
var data = {
    'url' : myurl
};

if(myurl.indexOf('Goods') != -1 || myurl.indexOf('Goods') != -1 || myurl.indexOf('details') != -1 || window.location.href.indexOf('tb') != -1){
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
    url:domain +　"/Home/User/getShareInfo.html",
    type:'post',
    dataType:'json',
    data:data,
    success:function (data) {
        wx.config({
            debug: false,
            appId: data.signPackage.appId,
            timestamp: data.signPackage.timestamp,
            nonceStr:  data.signPackage.nonceStr,
            signature: data.signPackage.signature,
            jsApiList: ['onMenuShareTimeline','onMenuShareAppMessage','hideMenuItems']
        });

        wx.ready(function(){
            //分享功能
            wxshare()
            function wxshare() {
                wx.onMenuShareTimeline({
                    title: '自用省钱，躺着赚钱！福利0元抢，爆品限时抢，海量折扣单品……',
                    desc: '生态化布局，亮点多多，收益多多，总能留住你的心！加入合纳，人生不将就！', // 分享描述
                    link: nowurl,
                    imgUrl: 'http://img.hena360.cn/wxshare.jpg', // 分享图标
                    success: function () {
                        alert("分享成功");
                    },
                    cancel: function () {
                        alert('你没有分享到朋友圈');
                    }
                });
                wx.onMenuShareAppMessage({
                    title: '自用省钱，躺着赚钱！福利0元抢，爆品限时抢，海量折扣单品……', // 分享标题
                    desc: '生态化布局，亮点多多，收益多多，总能留住你的心！加入合纳，人生不将就！', // 分享描述
                    link: nowurl, // 分享链接，该链接域名必须与当前企业的可信域名一致
                    imgUrl: 'http://img.hena360.cn/wxshare.jpg', // 分享图标
                    success: function () {
                        alert("分享成功");
                        // 用户确认分享后执行的回调函数
                    },
                    cancel: function () {
                        alert('你没有分享给朋友');
                        // 用户取消分享后执行的回调函数
                    }
                });
            }


            //隐藏不用的按钮
            wx.hideMenuItems({
                menuList: menu_list
            });

        })
    }
});