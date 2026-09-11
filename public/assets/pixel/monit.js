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
    // 即使同源也保留 host，确保 rrweb 和 heatmap_check 请求可正确路由
    if (!host) {
        try { host = location.origin; } catch (e) {}
    }

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

    /* 浏览器对 sendBeacon / keepalive fetch 共享 64KB 队列配额（含请求头开销），
     * 超限后请求被浏览器直接丢弃（控制台报 "Reached maximum amount of queued data
     * of 64Kb for keepalive requests"，数据静默丢失）。回放批次 / DOM 全量快照等
     * 大 payload 必须绕开 keepalive 通道改走普通 fetch（无 64KB 限制；服务端
     * nginx client_max_body_size 20m 可承载）。阈值预留 8KB 余量给请求头。 */
    var MAX_KEEPALIVE_BODY = 56 * 1024;

    function post(body, keepalive) {
        try {
            return fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body,
                keepalive: !!keepalive,
                credentials: 'omit',
                cache: 'no-store',
                mode: 'cors'
            }).catch(function () {});
        } catch (e) {
            return null;
        }
    }

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
        var beaconSafe = body.length <= MAX_KEEPALIVE_BODY;

        if (useBeacon && beaconSafe && navigator.sendBeacon) {
            try {
                // 返回 false = 入队失败（64KB 配额已被占满等）→ 降级重试
                if (navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/x-www-form-urlencoded' }))) {
                    return null;
                }
            } catch (e) { /* 降级重试 */ }
            // 配额满导致 beacon 失败时 keepalive fetch 受同样的 64KB 限制，
            // 但小 payload 仍有机会入队 —— 卸载期保命优先尝试
            return post(body, true);
        }

        // 大 payload（回放批次 / 热图快照）或常规请求：普通 fetch 不受 64KB 配额限制
        return post(body, false);
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

    /* ---------------- 热图自动检测（不再需要 data-heatmap-id） ---------------- */

    function autoDetectHeatmap(cb) {
        if (settings.mode === 'lightweight') return;

        var checkUrl = endpoint + '?action=heatmap_check&path=' + encodeURIComponent(location.pathname + location.search);
        try {
            fetch(checkUrl, {
                method: 'GET',
                credentials: 'omit',
                cache: 'no-store',
                mode: 'cors'
            }).then(function (r) { return r.ok ? r.json() : {}; }).then(function (data) {
                            if (data && data.heatmap_id) {
                    settings.heatmapId = data.heatmap_id;
                }
                // 后端告知回放功能状态 → 仅在 data-replay 未显式启用时同步。
                // 站长显式加 data-replay="1" 即主动要求录制，服务端响应不得关闭
                //（套餐配额由服务端落库时校验，客户端无需停录；修复回放无数据根因）
                if (! settings.replay && data && typeof data.replay_enabled !== 'undefined') {
                    settings.replay = !!data.replay_enabled;
                }
                if (cb) cb();
            }).catch(function () { if (cb) cb(); });
        } catch (e) { if (cb) cb(); }
    }

        var heatmaps = {
        maxScroll: 0,

            snapshot: function () {
            if (!settings.heatmapId) return;

            // The heatmap snapshot requires rrweb's full-snapshot event (type 2)
            // to render the page screenshot via rrweb-player.
            // replays.start() is called before this function, so _lastFullSnapshot
            // should be available (rrweb emits it synchronously on start).
            if (replays._lastFullSnapshot) {
                var events = [];
                // rrweb-player requires a Meta event (type 4) before the FullSnapshot (type 2).
                // Build one from the actual snapshot's timestamp to keep them aligned.
                var snapshot = replays._lastFullSnapshot;
                events.push({
                    type: 4,
                    data: { href: location.href, width: window.innerWidth, height: window.innerHeight },
                    timestamp: snapshot.timestamp
                });
                events.push(snapshot);
                send({
                    type: 'heatmap_snapshot',
                    heatmap_id: settings.heatmapId,
                    data: { events: events, viewport: pageData().viewport }
                });
            } else {
                // rrweb failed to start — send viewport-only metadata so the backend
                // can still create a placeholder snapshot for click/scroll data.
                send({
                    type: 'heatmap_snapshot',
                    heatmap_id: settings.heatmapId,
                    data: { events: [], viewport: pageData().viewport }
                });
            }

            // If rrweb was started only for the heatmap snapshot (replay not enabled),
            // stop recording now to free resources.
            replays.stopForHeatmap();
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

            var scrollUuid = uuid();
            send({
                type: 'heatmap_snapshot_scroll',
                heatmap_id: settings.heatmapId,
                max_scroll: Math.round(this.maxScroll),
                visitor_session_event_uuid: scrollUuid,
                data: {}
            }, true);
        }
    };

    /* ---------------- 回放采集（自动加载 rrweb · 周期 #19 #8 修复） ---------------- */
    /* 此前依赖站点页面自行引入 window.rrweb，导致回放功能永远静默不录制；
       现按需动态加载：与像素脚本同源的 rrweb-all.umd.min.js（自托管，离线可用）→
       失败回退 jsDelivr 公共 CDN → 仍失败静默放弃（不影响其他采集）。 */

    var replays = {
        started: false,
        loading: false,
        // Whether rrweb was started solely for heatmap snapshot (not replay)
        _heatmapOnly: false,
        _stopFn: null,

                ensureRrweb: function (cb) {
            if (window.rrweb) return cb();
            if (this.loading) {
                // 正在加载：排队回调，加载完成后统一触发
                if (!this._pendingCbs) this._pendingCbs = [];
                this._pendingCbs.push(cb);
                return;
            }
            this.loading = true;

            var self = this;
            // 仅自托管（host 上的 rrweb-all.umd.min.js）：不回退第三方 CDN。
            // 原因 1：jsDelivr 的 rrweb dist 文件自带失效 sourceMappingURL 注释，
            //         DevTools 打开时又会产生 map 404（与已修复的本地文件同病）；
            // 原因 2：项目隐私承诺是「零第三方请求」（GDPR/自托管），回退 CDN 违背。
            // 加载失败时仅 console.warn 诊断（无网络请求），调用方继续走
            // 「rrweb 不可用」容错分支（回放/热图快照静默降级，页面不受影响）。
            var tryLoad = function () {
                var s = document.createElement('script');
                s.src = host + '/assets/pixel/rrweb-all.umd.min.js';
                s.async = true;
                s.onload = function () {
                    if (!window.rrweb) {
                        // 脚本加载但全局缺失（被 CSP/扩展拦截等）
                        console.warn('[Monit] rrweb script loaded but window.rrweb missing');
                    }
                    self.loading = false;
                    cb();
                    // 触发排队回调
                    if (self._pendingCbs) {
                        var pending = self._pendingCbs;
                        self._pendingCbs = null;
                        pending.forEach(function (fn) { fn(); });
                    }
                };
                s.onerror = function () {
                    console.warn('[Monit] rrweb script failed to load:', s.src);
                    self.loading = false;
                    cb();
                    // 触发排队回调
                    if (self._pendingCbs) {
                        var pending = self._pendingCbs;
                        self._pendingCbs = null;
                        pending.forEach(function (fn) { fn(); });
                    }
                };
                document.head.appendChild(s);
            };

            tryLoad();
        },

        // Start rrweb recording if replay OR heatmap needs it.
        // After heatmap snapshot is captured, if replay is not enabled,
        // recording will be stopped to save resources.
        start: function (onReady) {
            if (this.started) {
                if (onReady) onReady();
                return;
            }

            // Need rrweb if replay is enabled OR heatmap snapshot is needed
            var needRrweb = settings.replay || settings.heatmapId;
            if (!needRrweb) {
                if (onReady) onReady();
                return;
            }

            var self = this;
            this._heatmapOnly = !settings.replay && !!settings.heatmapId;

            this.ensureRrweb(function () {
                if (!window.rrweb || self.started) {
                    if (onReady) onReady();
                    return;
                }
                self.started = true;

                self._stopFn = window.rrweb.record({
                    emit: function (event) {
                        // Capture the first full-snapshot event (type 2) for heatmap use
                        if (!self._lastFullSnapshot && event.type === 2) {
                            self._lastFullSnapshot = event;
                        }
                        // Only buffer events for replay if replay is enabled
                        if (settings.replay) {
                            if (!self._buffer) self._buffer = [];
                            self._buffer.push(event);
                            if (!self._timer) {
                                self._timer = setTimeout(function () { self.flush(); }, 1000);
                            }
                        }
                    },
                    checkoutEveryNms: 10000
                });

                // rrweb.record synchronously emits the initial full snapshot,
                // so _lastFullSnapshot is already set by this point.
                if (onReady) onReady();
            });
        },

        // Stop rrweb recording (called after heatmap snapshot if replay not needed)
        stopForHeatmap: function () {
            if (!this._heatmapOnly || !this.started) return;
            if (typeof this._stopFn === 'function') {
                try { this._stopFn(); } catch (e) {}
            }
            this._stopFn = null;
            this._heatmapOnly = false;
            // Don't set started=false; we don't want to restart accidentally
        },

        flush: function () {
            clearTimeout(this._timer);
            this._timer = null;
            if (!this._buffer || !this._buffer.length) return;
            // Only send replay data if replay is enabled
            if (!settings.replay) { this._buffer = []; return; }

            // 按 payload 字节预算分批：rrweb 每 10s 的全量快照（checkoutEveryNms）
            // 单事件可达数十 KB，整包一次性发送会撞上 sendBeacon / keepalive fetch
            // 共享的 64KB 队列配额而被浏览器整体丢弃。分批后由 send() 自动为
            // 超限批次选择普通 fetch 通道。批次串行发送：后端按"读取→合并→写回"
            // 追加事件，乱序并发到达会互相覆盖（丢整批）。
            var events = this._buffer.splice(0);
            var batches = [];
            var batch = [];
            var batchBytes = 0;
            for (var i = 0; i < events.length; i++) {
                var bytes;
                try { bytes = JSON.stringify(events[i]).length; } catch (e) { bytes = 0; }
                if (batch.length && batchBytes + bytes > 48 * 1024) {
                    batches.push(batch);
                    batch = [];
                    batchBytes = 0;
                }
                batch.push(events[i]);
                batchBytes += bytes;
            }
            if (batch.length) batches.push(batch);

            var pending = Promise.resolve();
            batches.forEach(function (b) {
                pending = pending.then(function () {
                    return send({ type: 'replays', data: { events: b } }, false) || Promise.resolve();
                });
            });
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
            // 先自动检测热图 → 拿到 heatmap_id 后再开始回放录制 + 发快照
            autoDetectHeatmap(function () {
                // Start replay recording FIRST — rrweb.record emits a full-snapshot
                // event (type 2) synchronously on start. We capture it in
                // replays._lastFullSnapshot so heatmaps.snapshot() can send it
                // as the heatmap's DOM snapshot for rrweb-player rendering.
                replays.start(function () {
                    heatmaps.snapshot();
                });
            });
        }, 300);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else if (!settings.manual) {
        init();
    }

    window.MonitPixel = { init: init };
})();
