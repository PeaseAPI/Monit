<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置外部对象存储与 CDN（规格书 §14.8：Offload 插件；M17 扩展阿里云 OSS / 腾讯云 COS）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="offload_is_enabled" value="1" {{ ($settings['offload.offload_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用 Offload
        </label>
        <div>
            <label class="form-label">存储驱动</label>
            <select name="offload_storage_driver" class="form-select">
                <option value="s3" {{ ($settings['offload.offload_storage_driver'] ?? 's3') === 's3' ? 'selected' : '' }}>AWS S3</option>
                <option value="minio" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'minio' ? 'selected' : '' }}>MinIO</option>
                <option value="custom" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'custom' ? 'selected' : '' }}>自定义 S3 兼容</option>
                <option value="aliyun_oss" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'aliyun_oss' ? 'selected' : '' }}>阿里云 OSS</option>
                <option value="tencent_cos" {{ ($settings['offload.offload_storage_driver'] ?? '') === 'tencent_cos' ? 'selected' : '' }}>腾讯云 COS</option>
            </select>
        </div>
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
        {{-- 阿里云 OSS（M17 §14.8） --}}
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">阿里云 OSS</legend>
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
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">腾讯云 COS</legend>
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
                    <label class="form-label">Bucket（含 APPID，如 monit-1250000000）</label>
                    <input type="text" name="offload_cos_bucket" class="form-input" value="{{ old('offload_cos_bucket', $settings['offload.offload_cos_bucket'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">地域（如 ap-guangzhou）</label>
                    <input type="text" name="offload_cos_region" class="form-input" value="{{ old('offload_cos_region', $settings['offload.offload_cos_region'] ?? 'ap-guangzhou') }}">
                </div>
            </div>
        </fieldset>

        <div>
            <label class="form-label">CDN 前缀 URL</label>
            <input type="url" name="offload_cdn_url" class="form-input" value="{{ old('offload_cdn_url', $settings['offload.offload_cdn_url'] ?? '') }}" placeholder="https://cdn.example.com">
        </div>
    </div>
</div>

