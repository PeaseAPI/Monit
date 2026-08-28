/*!
 * Monit Analytics Pixel SDK v1.0.0
 * 隐私优先：无 Cookie（sessionStorage/localStorage UUID）、无指纹采集
 * 用法：<script src="https://your-domain/assets/pixel/monit.js" data-monit="PIXEL_KEY" async></script>
 */
(function () {
    'use strict';

    var script = document.currentScript;
    if (!script) return;

    var PIXEL_KEY = script.getAttribute('data-monit') || script.getAttribute('data-key');
    if (!PIXEL_KEY) { console.error('[Monit] missing data-monit key'); return; }

    // 自动判断采集端点：与 SDK 同源
    var ENDPOINT = (script.src ? new URL(script.src).origin : '') + '/pixel-track/' + PIXEL_KEY;

    var SESSION_TIMEOUT = 30 * 60 * 1000; // 30 分钟
    var LS_VISITOR = 'monit_v';
    var SS_SESSION = 'monit_s';
    var SS_LAST = 'monit_sl';

    /* ---------- UUID（RFC4122 v4） ---------- */
    function uuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    /* ---------- 无 Cookie 标识 ---------- */
    function getVisitorUuid() {
        try {
            var v = localStorage.getItem(LS_VISITOR);
            if (!v) { v = uuid(); localStorage.setItem(LS_VISITOR, v); }
            return v;
        } catch (e) { return uuid(); } // 隐私模式退化：每次会话新访客
    }

    function getSessionUuid(forceNew) {
        try {
            var last = parseInt(sessionStorage.getItem(SS_LAST) || '0', 10);
            var s = sessionStorage.getItem(SS_SESSION);
            if (forceNew || !s || Date.now() - last > SESSION_TIMEOUT) {
                s = uuid();
                sessionStorage.setItem(SS_SESSION, s);
            }
            sessionStorage.setItem(SS_LAST, String(Date.now()));
            return s;
        } catch (e) { return uuid(); }
    }

    /* ---------- 发送（sendBeacon 优先） ---------- */
    function send(payload) {
        payload.visitor_uuid = getVisitorUuid();
        payload.visitor_session_uuid = getSessionUuid(false);
        var body = 'data=' + encodeURIComponent(JSON.stringify(payload));
        try {
            if (navigator.sendBeacon && navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/x-www-form-urlencoded' }))) {
                return;
            }
        } catch (e) { /* 降级 fetch */ }
        try {
            fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body,
                keepalive: true,
                mode: 'cors'
            }).catch(function () {});
        } catch (e) { /* 静默 */ }
    }

    /* ---------- 环境数据 ---------- */
    function environmentData() {
        return {
            url: location.href,
            title: document.title || '',
            referrer: document.referrer || '',
            resolution: { width: screen.width, height: screen.height },
            viewport: { width: window.innerWidth, height: window.innerHeight },
            language: navigator.language || '',
            timezone: (Intl.DateTimeFormat().resolvedOptions().timezone || ''),
            theme: (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'
        };
    }

    /* ---------- 事件 API ---------- */
    var eventUuid = uuid();

    window.Monit = {
        // 发起访客（advanced 模式首次进入）
        initiateVisitor: function (customParameters) {
            var data = environmentData();
            if (customParameters) data.custom_parameters = customParameters;
            send({ type: 'initiate_visitor', data: data });
        },
        // 落地页
        landingPage: function () {
            eventUuid = uuid();
            send({ type: 'landing_page', visitor_session_event_uuid: eventUuid, url: location.href, data: environmentData() });
        },
        // 页面浏览（SPA 路由切换手动调用）
        pageview: function (url, title) {
            eventUuid = uuid();
            var data = environmentData();
            if (url) data.url = url;
            if (title) data.title = title;
            send({ type: 'pageview', visitor_session_event_uuid: eventUuid, url: url || location.href, data: data });
        },
        // 目标转化
        goalConversion: function (goalKey) {
            send({ type: 'goal_conversion', goal_key: goalKey, visitor_session_event_uuid: eventUuid, url: location.href });
        },
        // 出站点击
        outboundClick: function (url, title) {
            send({ type: 'outbound_click', outbound_url: url, outbound_title: title || '', visitor_session_event_uuid: eventUuid, url: location.href });
        }
    };

    /* ---------- 自动初始化 ---------- */
    // 1. initiate_visitor + landing_page
    window.Monit.initiateVisitor();
    window.Monit.landingPage();

    // 2. 自动出站点击捕获
    document.addEventListener('click', function (e) {
        var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
        if (!a) return;
        var href = a.getAttribute('href') || '';
        if (href.indexOf('http') === 0 && a.hostname && a.hostname !== location.hostname) {
            window.Monit.outboundClick(href, a.textContent || '');
        }
    }, { passive: true });

    // 3. SPA 路由变化自动 pageview（pushState/replaceState/popstate 钩子）
    ['pushState', 'replaceState'].forEach(function (name) {
        var original = history[name];
        if (typeof original !== 'function') return;
        history[name] = function () {
            var result = original.apply(this, arguments);
            setTimeout(function () { window.Monit.pageview(); }, 0);
            return result;
        };
    });
    window.addEventListener('popstate', function () {
        setTimeout(function () { window.Monit.pageview(); }, 0);
    });
})();
