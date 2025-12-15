<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

/**
 * 处理所有来自飞猪的 Webhook 推送。
 */
class FliggyController extends Controller
{
    /**
     * 处理飞猪产品变更通知。
     *
     * @param Request $request
     * @return Response
     */
    public function handleProductChange(Request $request): Response
    {
        // 记录收到的原始请求，用于调试。
        Log::channel('fliggy')->info('收到产品变更Webhook:', $request->all());

        // TODO: 在这里添加您的业务逻辑。
        // 例如：可以将请求数据分发到一个队列任务中，去异步更新本地数据库的产品信息。
        // dispatch(new \App\Jobs\UpdateFliggyProduct($request->all()));

        // 根据飞猪文档，收到推送后需返回纯文本 "success"。
        return response('success', 200)
                  ->header('Content-Type', 'text/plain');
    }

    /**
     * 处理飞猪订单状态通知。
     *
     * @param Request $request
     * @return Response
     */
    public function handleOrderStatus(Request $request): Response
    {
        // 记录收到的原始请求，用于调试。
        Log::channel('fliggy')->info('收到订单状态Webhook:', $request->all());

        // TODO: 在这里添加您的业务逻辑。
        // 例如：根据 pushType（如 ORDER_STATUS_CHANGE）来更新本地数据库的订单状态。
        // dispatch(new \App\Jobs\UpdateFliggyOrder($request->all()));

        // 根据飞猪文档，收到推送后需返回纯文本 "success"。
        return response('success', 200)
                  ->header('Content-Type', 'text/plain');
    }
}
