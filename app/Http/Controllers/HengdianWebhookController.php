<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class HengdianWebhookController extends Controller
{
    /**
     * Handle room status updates from Hengdian.
     * This is the NotifyUrl endpoint.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleRoomStatus(Request $request)
    {
        $xmlBody = $request->getContent(); // Get raw XML body

        Log::channel('hengdian')->info('Hengdian Room Status Webhook Received:', ['xml' => $xmlBody]);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlBody);

        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->message;
            }
            Log::channel('hengdian')->error('Failed to parse incoming Room Status XML.', ['body' => $xmlBody, 'errors' => $errorMessages]);
            libxml_clear_errors();

            // Return an error response if XML is malformed
            return response('<?xml version="1.0" encoding="UTF-8"?><Result><Message>Invalid XML</Message><ResultCode>-1</ResultCode></Result>', 400)
                ->header('Content-Type', 'text/xml');
        }

        // Process the room status update
        // Example: Log the parsed data
        Log::channel('hengdian')->info('Parsed Hengdian Room Status:', json_decode(json_encode($xml), true));

        // Here you would typically update your local database with the new room status.
        // For example:
        // $hotelNo = (string)$xml->RoomQuotaMap->hotelNo;
        // $roomType = (string)$xml->RoomQuotaMap->roomType;
        // $roomQuota = json_decode((string)$xml->RoomQuotaMap->roomQuota, true); // Assuming it's a JSON string

        // Return a success response
        return response('<?xml version="1.0" encoding="UTF-8"?><Result><Message>Success</Message><ResultCode>0</ResultCode></Result>', 200)
            ->header('Content-Type', 'text/xml');
    }
}
