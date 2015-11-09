<?php

include_once __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$http = new React\Http\Server($socket);
$http->on('request', function (\React\Http\Request $request, \React\Http\Response $response) use($client) {
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $httpRequest = $client->request('GET', 'https://api.dmtry.me/api/users/1');
    $httpRequest->on('response', function (\React\HttpClient\Response $httpResponse) use($response) {
        $httpResponse->on('data', function ($data, \React\HttpClient\Response $httpResponse) use($response) {
            if ($data instanceof \GuzzleHttp\Psr7\Stream && $httpResponse->getCode() == 200) {
                $response->write('User');
                $response->write('Code' . $httpResponse->getCode());
                $response->write('Data' . $data->getContents());
            } else {
                $response->write('Bad request' . $httpResponse->getCode());
            }

            $response->end("\n");
//            $response->close();
        });
    });
    $httpRequest->end();
});

$socket->listen(8080);
$loop->run();
