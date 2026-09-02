<?php

namespace App\Http\Controllers;

use App\Support\Captcha;
use Illuminate\Http\JsonResponse;

/**
 * 人机验证前端辅助端点
 *
 * GET /captcha/geetest/register：Geetest v3 服务端预注册 challenge
 * （加盐 md5 下发前端 initGeetest；极验不可达时返回 fail-back 降级负载）
 */
class CaptchaController extends Controller
{
    public function geetestRegister(): JsonResponse
    {
        return response()->json(Captcha::geetestRegisterPayload());
    }
}
