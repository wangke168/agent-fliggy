<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use SimpleXMLElement;

/**
 * 处理所有来自横店的 Webhook 推送。
 */
class HengdianController extends Controller
{
    /**
     * 处理横店推送的房态更新。
     *
     * @param Request $request
     * @return Response
     */
    public function handleRoomStatus(Request $request): Response
    {
        // 获取原始的 XML 请求体
        $xmlBody = $request->getContent();

        Log::channel('hengdian')->info('收到横店房态 Webhook:', ['xml' => $xmlBody]);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlBody);

        if ($xml === false) {
            $errors = libxml_get_errors();
            Log::channel('hengdian')->error('解析收到的房态 XML 失败。', ['body' => $xmlBody, 'errors' => $errors]);
            libxml_clear_errors();

            // 如果 XML 格式错误，返回一个错误响应
            return response('<?xml version="1.0" encoding="UTF-8"?><Result><Message>Invalid XML</Message><ResultCode>-1</ResultCode></Result>', 400)
                ->header('Content-Type', 'text/xml');
        }

        // TODO: 在此实现您的业务逻辑
        // 例如：解析 RoomQuotaMap 中的 JSON 字符串，并更新本地数据库的库存。
        if (isset($xml->RoomQuotaMap)) {
            $roomQuotaData = json_decode((string)$xml->RoomQuotaMap, true);
            Log::channel('hengdian')->info('解析后的房态数据:', $roomQuotaData);
            // dispatch(new \App\Jobs\UpdateHengdianInventory($roomQuotaData));
        }

        // 根据文档，返回一个表示成功接收的 XML 响应
        return response('<?xml version="1.0" encoding="UTF-8"?><Result><Message>Success</Message><ResultCode>0</ResultCode></Result>', 200)
            ->header('Content-Type', 'text/xml');
    }
}
