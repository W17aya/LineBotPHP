<?php
require __DIR__ . '/../vendor/autoload.php';


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;


use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;


$pass_signature = true;


// set LINE channel_access_token and channel_secret
$channel_access_token = "jstYcaIgYqkFOHHTAKa8ewCUg9fa7yizwQVcbhzxGmnL5TNQXam6vWyxXHFrSyMwGM03imcNaIy1JX4sNXQEhZ6r0UDZPeLdCcpjwlO5ncjcAnvDULhyBUHWbHmp48tofwgwGLUboZ4/rY0+20+tQQdB04t89/1O/w1cDnyilFU=";
$channel_secret = "f8d98388fa6f06ba4cd71c05e4efa630";


// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);




$app = AppFactory::create();
$app->setBasePath("/public");




$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello World!");
    return $response;
});


// buat route untuk webhook
$app->post('/webhook', function (Request $request, Response $response) use ($channel_secret, $bot, $httpClient, $pass_signature) {
    // get request body and line signature header
    $body = $request->getBody();
    $signature = $request->getHeaderLine('HTTP_X_LINE_SIGNATURE');


    // log body and signature
    file_put_contents('php://stderr', 'Body: ' . $body);


    if ($pass_signature === false) {
        // is LINE_SIGNATURE exists in request header?
        if (empty($signature)) {
            return $response->withStatus(400, 'Signature not set');
        }


        // is this request comes from LINE?
        if (!SignatureValidator::validateSignature($body, $channel_secret, $signature)) {
            return $response->withStatus(400, 'Invalid signature');
        }
    }


    $data = json_decode($body, true);
    if (is_array($data['events'])) {
        foreach ($data['events'] as $event) {
            if ($event['type'] == 'message') {
                if ($event['message']['type'] == 'text') {
                    switch (strtolower($event['message']['text'])) {
                        case 'start':
                            $text = "Hi, Nama saya Paijo \n\nSaya adalah BOT yang akan membantu kamu untuk memesan \n\nKetik 'Menu' untuk memunculkan menu";
                            $result = $bot->replyText($event['replyToken'], $text);
                            break;
                        case 'menu':
                            $flexTemplate = file_get_contents("../flex_message.json"); // template flex message
                            $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                                'replyToken' => $event['replyToken'],
                                'messages'   => [
                                    [
                                        'type'     => 'flex',
                                        'altText'  => 'Daftar Menu',
                                        'contents' => json_decode($flexTemplate)
                                    ]
                                ],
                            ]);
                            break;
                        case 'order':
                            $text = "Terima Kasih \n\nPesanan anda sedang diproses";
                            $result = $bot->replyText($event['replyToken'], $text);
                            break;
                        case 'order - Tahu kupat':
                            $text = "Terima Kasih \n\nPesanan Tahu kupat anda sedang diproses";
                            $result = $bot->replyText($event['replyToken'], $text);
                            break;
                        case 'order - Soto ayam':
                            $text = "Terima Kasih \n\nPesanan Soto ayam sedang diproses";
                            $result = $bot->replyText($event['replyToken'], $text);
                            break;
                        case 'order - Ayam penyet':
                            $text = "Terima Kasih \n\nPesanan Ayam penyet sedang diproses";
                            $result = $bot->replyText($event['replyToken'], $text);
                            break;
                        default:
                            $text = "KEYWORD yang anda masukkan salah \n\nKetik START untuk menggunakan BOT";
                            $result = $bot->replyText($event['replyToken'], $text);
                            break;
                    }

                    $response->getBody()->write(json_encode($result->getJSONDecodedBody()));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($result->getHTTPStatus());
                } else {
                    $text = "KEYWORD yang anda masukkan salah \n\nKetik START untuk menggunakan BOT";
                    $result = $bot->replyText($event['replyToken'], $text);
                }
            }
        }
        return $response->withStatus(200, 'for Webhook!');
    }
    return $response->withStatus(400, 'No event sent!');
});




$app->run();
