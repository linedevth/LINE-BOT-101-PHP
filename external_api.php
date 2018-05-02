<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

define('LINE_CHANNEL_ACCESS_TOKEN', '<YOUR_CHANNEL_ACCESS_TOKEN>');
define('LINE_MESSAGING_API', 'https://api.line.me/v2/bot/message/');
define('LINE_USER_ID', '<YOUR_USER_ID>');
define('SANOOK_API', 'http://m.sanook.com/feed/weblog/info/exchange/?format=json');

if (isset($_GET['push'])) {
      push();
} else {
      reply();
}

function push() {
      $exchangeData = file_get_contents(SANOOK_API);
      $exchangeArray = json_decode($exchangeData, true);
      foreach ($exchangeArray['item'] as $item) {
            $pushMsg .= $item['title']." ".$item['description']."\n\n";
      }
      $pushMsg = substr($pushMsg, 0, -2);
      $message = [
            'type' => 'text',
            'text' => $pushMsg
      ];
      $body = [
            'to' => LINE_USER_ID,
            'messages' => [$message]
      ];
      curl('push', json_encode($body));
      echo $pushMsg;
}

function reply() {
      // get body from POST or PUT
      $content = file_get_contents('php://input');

      // convert to array
      $events = json_decode($content, true);

      // Validate parsed JSON data
      if (!is_null($events['events'])) {
            foreach ($events['events'] as $event) {
            
                  // Reply only when message type is text
                  if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
                        $text = strtoupper(trim($event['message']['text']));
                        
                        $exchangeData = file_get_contents(SANOOK_API);
                        $exchangeArray = json_decode($exchangeData, true);

                        switch ($text) {
                              case 'USD': $replyMsg = $exchangeArray['item'][0]['description']; break;
                              case 'JPY': $replyMsg = $exchangeArray['item'][1]['description']; break;
                              case 'EUR': $replyMsg = $exchangeArray['item'][2]['description']; break;
                              case 'GBP': $replyMsg = $exchangeArray['item'][3]['description']; break;
                              case 'HKD': $replyMsg = $exchangeArray['item'][4]['description']; break;
                              default: $replyMsg = 'Sorry "'.$text.'" is not supported.';
                        }

                        $message = [
                              'type' => 'text',
                              'text' => $replyMsg
                        ];
                        $body = [
                              'replyToken' => $event['replyToken'],
                              'messages' => [$message]
                        ];
                        curl('reply', json_encode($body));
                  }
            }
      }
}

function curl($apiType, $postFields) {
      $headers = array('Content-Type: application/json', 'Authorization: Bearer '.LINE_CHANNEL_ACCESS_TOKEN);
      $ch = curl_init(LINE_MESSAGING_API.$apiType);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_exec($ch);
      curl_close($ch);
}
?>