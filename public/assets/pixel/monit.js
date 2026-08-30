/*!
 * Monit Analytics Pixel SDK（规格书 §4.5）
 * 无依赖轻量采集脚本。用法：
 *   <script src="https://your-host/js/monit.js" data-website-id="PIXEL_KEY" data-mode="advanced|lightweight"></script>
 * 可选属性：data-manual / data-respect-dnt="1" / data-heatmap-id / data-replay="1" / data-custom-parameters(JSON)
 * 存储键：__{pixel_key}__visitor_uuid（localStorage）/ __{pixel_key}__visitor_session_uuid（sessionStorage）
 * 全局 API：window.monitGoal('goal_key')
 */
(function () {
    'use strict';

    var currentScript =
        document.currentScript ||
        (function () {
            var scripts = document.getElementsByTagName('script');
            return scripts[scripts.length - 1];
        })();

    var pixelKey = ((currentScript && currentScript.getAttribute('data-website-id')) || '').trim();
    if (!pixelKey) return;

    var scriptSrc = (currentScript && currentScript.src) || '';
    var host = '';
    try { host = scriptSrc ? new URL(scriptSrc, location.href).origin : ''; } catch (e) {}
    if (!host || host === location.origin) host = '';

    var endpoint = host + '/pixel-track/' + encodeURIComponent(pixelKey);

    var settings = {
        mode: (currentScript.getAttribute('data-mode') || 'advanced').toLowerCase(),
        manual: currentScript.hasAttribute('data-manual'),
        respectDnt: currentScript.getAttribute('data-respect-dnt') === '1',
        heatmapId: parseInt(currentScript.getAttribute('data-heatmap-id') || '0', 10) || 0,
        replay: currentScript.getAttribute('data-replay') === '1'
    };

    var VISITOR_KEY = '__' + pixelKey + '__visitor_uuid';
    var SESSION_KEY = '__' + pixelKey + '__visitor_session_uuid';
    var LANDING_KEY = '__' + pixelKey + '__session_landed';

    /* ---------------- 基础工具 ---------------- */

    function uuid() {
        if (window.crypto && crypto.randomUUID) {
            try { return crypto.randomUUID(); } catch (e) {}
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
        });
    }

    function lsGet(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }
    function lsSet(key, v) { try { localStorage.setItem(key, v); } catch (e) {} }
    function ssGet(key) { try { return sessionStorage.getItem(key); } catch (e) { return null; } }
    function ssSet(key, v) { try { sessionStorage.setItem(key, v); } catch (e) {} }

    function getVisitorUuid() {
        var v = lsGet(VISITOR_KEY);
        if (!v) { v = uuid(); lsSet(VISITOR_KEY, v); }
        return v;
    }

    function getSessionUuid() {
        var s = ssGet(SESSION_KEY);
        if (!s) { s = uuid(); ssSet(SESSION_KEY, s); }
        return s;
    }

    function isDoNotTrack() {
        return settings.respectDnt && (navigator.doNotTrack === '1' || window.doNotTrack === '1');
    }

    /* ---------------- 发送 ---------------- */

    function send(payload, useBeacon) {
        payload.visitor_uuid = getVisitorUuid();
        if (settings.mode === 'advanced') {
            payload.visitor_session_uuid = getSessionUuid();
            if (payload.type === 'landing_page' || payload.type === 'pageview') {
                payload.visitor_session_event_uuid = uuid();
            }
        }
        payload.url = location.href;

        var body = 'data=' + encodeURIComponent(JSON.stringify(payload));

        if (useBeacon && navigator.sendBeacon) {
            try {
                navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/x-www-form-urlencoded' }));
                return;
            } catch (e) { /* 降级 fetch */ }
        }

        try {
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body,
                keepalive: true,
                credentials: 'omit',
                cache: 'no-store',
                mode: 'cors'
            }).catch(function () {});
        } catch (e) {}
    }

    /* ---------------- 页面数据 ---------------- */

    function pageData() {
        return {
            url: location.href,
            title: document.title || '',
            referrer: document.referrer || '',
            viewport: { width: window.innerWidth || 0, height: window.innerHeight || 0 },
            resolution: { width: screen.width || 0, height: screen.height || 0 },
            timezone: (Intl && Intl.DateTimeFormat().resolvedOptions().timezone) || '',
            language: navigator.language || '',
            theme: (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'
        };
    }

    /* ---------------- Advanced 采集器 ---------------- */

    var advanced = {
        initiate: function () {
            if (this.initiated) return;
            this.initiated = true;

            var custom = {};
            try { custom = JSON.parse(currentScript.getAttribute('data-custom-parameters') || '{}'); } catch (e) {}

            var pd = pageData();
            send({
                type: 'initiate_visitor',
                data: {
                    custom_parameters: custom,
                    resolution: pd.resolution,
                    timezone: pd.timezone,
                    language: pd.language,
                    theme: pd.theme
                }
            });
        },

        landing: function () {
            ssSet(LANDING_KEY, '1');
            send({ type: 'landing_page', data: pageData() });
        },

        pageview: function () {
            send({ type: 'pageview', data: pageData() });
        },

        eventChild: function (type, data) {
            send({ type: type, data: data || {} });
        },

        outboundClick: function (url, title) {
            send({ type: 'outbound_click', outbound_url: url, outbound_title: title || '', data: {} }, true);
        },

        goal: function (key) {
            send({ type: 'goal_conversion', goal_key: key, data: {} });
        }
    };

    /* ---------------- 热图采集（data-heatmap-id） ---------------- */

    var heatmaps = {
        maxScroll: 0,

        snapshot: function () {
            if (!settings.heatmapId) return;

            var de = document.documentElement;
            var dom = {
                w: de.scrollWidth,
                h: Math.max(de.scrollHeight, document.body ? document.body.scrollHeight : 0),
                url: location.href,
                nodes: (document.body ? document.body.innerText || '' : '').slice(0, 4096)
            };

            send({
                type: 'heatmap_snapshot',
                heatmap_id: settings.heatmapId,
                data: { dom: dom, viewport: pageData().viewport }
            });
        },

        click: function (e) {
            if (!settings.heatmapId) return;

            var de = document.documentElement;
            var x = (e.pageX / (de.scrollWidth || 1)) * 100;
            var y = (e.pageY / (de.scrollHeight || 1)) * 100;

            send({
                type: 'heatmap_snapshot_click',
                heatmap_id: settings.heatmapId,
                x_normalized: Math.round(Math.max(0, Math.min(100, x)) * 100) / 100,
                y_normalized: Math.round(Math.max(0, Math.min(100, y)) * 100) / 100,
                count: 1,
                data: {}
            }, true);
        },

        trackScroll: function () {
            if (!settings.heatmapId) return;

            var de = document.documentElement;
            var percent = ((window.scrollY + window.innerHeight) / (de.scrollHeight || 1)) * 100;
            if (percent > this.maxScroll) this.maxScroll = Math.min(100, percent);
        },

        flushScroll: function () {
            if (!settings.heatmapId || !this.maxScroll) return;

            send({
                type: 'heatmap_snapshot_scroll',
                heatmap_id: settings.heatmapId,
                max_scroll: Math.round(this.maxScroll),
                data: {}
            }, true);
        }
    };

    /* ---------------- 回放采集（页面已加载 rrweb 时启用） ---------------- */

    var replays = {
        started: false,

        start: function () {
            if (!settings.replay || this.started || !window.rrweb) return;
            this.started = true;

            var self = this;

            window.rrweb.record({
                emit: function (event) {
                    if (!self._buffer) self._buffer = [];
                    self._buffer.push(event);
                    if (!self._timer) {
                        self._timer = setTimeout(function () { self.flush(); }, 1000); // 1s 间隔
                    }
                },
                checkoutEveryNms: 10000
            });
        },

        flush: function () {
            clearTimeout(this._timer);
            this._timer = null;
            if (!this._buffer || !this._buffer.length) return;

            send({ type: 'replays', data: { events: this._buffer.splice(0) } }, true);
        }
    };

    /* ---------------- 事件绑定 ---------------- */

    function currentHost() { return location.hostname.replace(/^www\./, ''); }

    function isOutbound(url) {
        try {
            var u = new URL(url, location.href);
            return u.protocol.indexOf('http') === 0 &&
                u.hostname.replace(/^www\./, '') !== currentHost();
        } catch (e) { return false; }
    }

    function bindAdvanced() {
        // 出站点击 + 热图点击坐标（捕获阶段，pagehide 前完成）
        document.addEventListener('click', function (e) {
            var a = e.target.closest ? e.target.closest('a[href]') : null;
            if (a && isOutbound(a.href)) {
                advanced.outboundClick(a.href, (a.textContent || '').trim().slice(0, 512));
            }
            heatmaps.click(e);
        }, true);

        // 子事件：点击（2s 节流）
        var lastClick = 0;
        document.addEventListener('click', function (e) {
            var now = Date.now();
            if (now - lastClick < 2000) return;
            lastClick = now;
            advanced.eventChild('click', {
                tag: (e.target.tagName || '').toLowerCase(),
                id: e.target.id || '',
                selector: (e.target.tagName || '').toLowerCase() + (e.target.id ? '#' + e.target.id : '')
            });
        });

        // 子事件：滚动深度节点（25/50/75/100）
        var scrollMarks = {};
        window.addEventListener('scroll', function () {
            heatmaps.trackScroll();

            var de = document.documentElement;
            var percent = ((window.scrollY + window.innerHeight) / (de.scrollHeight || 1)) * 100;
            [25, 50, 75, 100].forEach(function (mark) {
                if (percent >= mark && !scrollMarks[mark]) {
                    scrollMarks[mark] = true;
                    advanced.eventChild('scroll', { percentage: mark });
                }
            });
        }, { passive: true });

        // 子事件：resize（500ms 防抖）
        var resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                advanced.eventChild('resize', {
                    viewport: { width: window.innerWidth, height: window.innerHeight }
                });
            }, 500);
        });

        // 子事件：表单提交（捕获）
        document.addEventListener('submit', function (e) {
            advanced.eventChild('form', {
                action: (e.target.getAttribute('action') || location.pathname).slice(0, 2048),
                method: (e.target.getAttribute('method') || 'get').toLowerCase()
            });
        }, true);

        // SPA 路由变化
        var lastPath = location.pathname + location.search;
        function onRouteChange() {
            var now = location.pathname + location.search;
            if (now === lastPath) return;
            lastPath = now;
            advanced.pageview();
        }
        ['pushState', 'replaceState'].forEach(function (fn) {
            var orig = history[fn];
            if (typeof orig === 'function') {
                history[fn] = function () {
                    var r = orig.apply(this, arguments);
                    setTimeout(onRouteChange, 0);
                    return r;
                };
            }
        });
        window.addEventListener('popstate', onRouteChange);
        window.addEventListener('hashchange', onRouteChange);

        // 页面卸载：滚动深度 + 回放缓冲
        window.addEventListener('pagehide', function () {
            heatmaps.flushScroll();
            replays.flush();
        });
    }

    /* ---------------- 启动 ---------------- */

    window.monitGoal = function (key) {
        if (!isDoNotTrack() && settings.mode === 'advanced') advanced.goal(key);
    };

    function init() {
        if (isDoNotTrack()) return;

        if (settings.mode === 'lightweight') {
            // LW：仅浏览量，beacon 优先
            send({ type: 'landing_page', data: pageData() }, true);
            return;
        }

        advanced.initiate();
        bindAdvanced();

        if (!ssGet(LANDING_KEY)) {
            advanced.landing();
        } else {
            advanced.pageview();
        }

        setTimeout(function () {
            heatmaps.snapshot();
            replays.start();
        }, 300);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else if (!settings.manual) {
        init();
    }

    window.MonitPixel = { init: init };
})();
