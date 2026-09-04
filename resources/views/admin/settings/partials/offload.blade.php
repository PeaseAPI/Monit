<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.offload.t_f6ef68') }}</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="offload_is_enabled" value="1" {{ filter_var($settings['offload.offload_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        {{ __('settings.offload.enable_offload') }}
        </label>
        <div>
            <label class="form-label">{{ __('settings.offload.t_dc935e') }}</label>
            <select name="offload_storage_driver" id="offload-storage-driver" class="form-select">
                <option value="s3" {{ ($settings['offload.offload_storage_driver'] ?? 's3') === 's3' ? 'selected' : '' }}>AWS S3</option>
                <option value="minio" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'minio' ? 'selected' : '' }}>MinIO</option>
                <option value="custom" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'custom' ? 'selected' : '' }}>{{ __('settings.offload.t_163063') }}</option>
                <option value="aliyun_oss" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'aliyun_oss' ? 'selected' : '' }}>{{ __('settings.offload.t_bc97e1') }}</option>
                <option value="tencent_cos" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'tencent_cos' ? 'selected' : '' }}>{{ __('settings.offload.t_b65af7') }}</option>
            </select>
            {{-- 按服务商动态显示凭据组（用户反馈 #23：不再五组字段全部平铺） --}}
            <p class="mt-1 text-xs text-zinc-400">{{ __('settings.offload.driver_switch_hint') }}</p>
        </div>
        {{-- S3 / MinIO / 自定义（S3 兼容协议共用凭据组） --}}
        <fieldset data-offload-drivers="s3,minio,custom" class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">S3 / MinIO / {{ __('settings.offload.t_163063') }}</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">S3 Endpoint</label>
            <input type="url" name="offload_s3_endpoint" class="form-input" value="{{ old('offload_s3_endpoint', $settings['offload.offload_s3_endpoint'] ?? '') }}" placeholder="https://s3.amazonaws.com">
        </div>
        <div>
            <label class="form-label">Bucket</label>
            <input type="text" name="offload_s3_bucket" class="form-input" value="{{ old('offload_s3_bucket', $settings['offload.offload_s3_bucket'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">Access Key</label>
            <input type="text" name="offload_s3_key" class="form-input" value="{{ old('offload_s3_key', $settings['offload.offload_s3_key'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">Secret Key</label>
            <input type="password" name="offload_s3_secret" class="form-input" value="{{ old('offload_s3_secret', $settings['offload.offload_s3_secret'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">Region</label>
            <input type="text" name="offload_s3_region" class="form-input" value="{{ old('offload_s3_region', $settings['offload.offload_s3_region'] ?? 'us-east-1') }}">
        </div>
            </div>
        </fieldset>

        {{-- 阿里云 OSS（M17 §14.8） --}}
        <fieldset data-offload-drivers="aliyun_oss" class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">{{ __('settings.offload.t_bc97e1') }}</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Access Key ID</label>
                    <input type="text" name="offload_oss_access_key_id" class="form-input" value="{{ old('offload_oss_access_key_id', $settings['offload.offload_oss_access_key_id'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">Access Key Secret</label>
                    <input type="password" name="offload_oss_access_key_secret" class="form-input" value="{{ old('offload_oss_access_key_secret', $settings['offload.offload_oss_access_key_secret'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">Bucket</label>
                    <input type="text" name="offload_oss_bucket" class="form-input" value="{{ old('offload_oss_bucket', $settings['offload.offload_oss_bucket'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">Endpoint</label>
                    <input type="url" name="offload_oss_endpoint" class="form-input" value="{{ old('offload_oss_endpoint', $settings['offload.offload_oss_endpoint'] ?? 'https://oss-cn-hangzhou.aliyuncs.com') }}" placeholder="https://oss-cn-hangzhou.aliyuncs.com">
                </div>
            </div>
        </fieldset>

        {{-- 腾讯云 COS（M17 §14.8） --}}
        <fieldset data-offload-drivers="tencent_cos" class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">{{ __('settings.offload.t_b65af7') }}</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">SecretId</label>
                    <input type="text" name="offload_cos_secret_id" class="form-input" value="{{ old('offload_cos_secret_id', $settings['offload.offload_cos_secret_id'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">SecretKey</label>
                    <input type="password" name="offload_cos_secret_key" class="form-input" value="{{ old('offload_cos_secret_key', $settings['offload.offload_cos_secret_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.offload.t_a690bb') }}</label>
                    <input type="text" name="offload_cos_bucket" class="form-input" value="{{ old('offload_cos_bucket', $settings['offload.offload_cos_bucket'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.offload.t_eca9e9') }}</label>
                    <input type="text" name="offload_cos_region" class="form-input" value="{{ old('offload_cos_region', $settings['offload.offload_cos_region'] ?? 'ap-guangzhou') }}">
                </div>
            </div>
        </fieldset>

        <div>
            <label class="form-label">{{ __('settings.offload.t_78f88d') }}</label>
            <input type="url" name="offload_cdn_url" class="form-input" value="{{ old('offload_cdn_url', $settings['offload.offload_cdn_url'] ?? '') }}" placeholder="https://cdn.example.com">
        </div>
    </div>

    {{-- 按驱动切换凭据组（用户反馈 #23） --}}
    <script>
    (function () {
        var driverSelect = document.getElementById('offload-storage-driver');
        if (!driverSelect) return;

        var apply = function () {
            var current = driverSelect.value;
            document.querySelectorAll('[data-offload-drivers]').forEach(function (el) {
                var drivers = el.dataset.offloadDrivers.split(',');
                el.classList.toggle('hidden', drivers.indexOf(current) === -1);
            });
        };

        driverSelect.addEventListener('change', apply);
        apply();
    })();
    </script>
</div>

