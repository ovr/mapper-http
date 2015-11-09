<?php

include_once __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$userCache = new SplFixedArray(1000);

$http = new React\Http\Server($socket);
$http->on('request', function (\React\Http\Request $request, \React\Http\Response $response) use($client, &$userCache) {
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $requestParameters = $request->getQuery();
    $id = isset($requestParameters['id']) ? (int) $requestParameters['id'] : 1;

    if (isset($userCache[$id])) {
        $response->write(json_encode($userCache[$id]));
        $response->end("\n");
    } else {
        $httpRequest = $client->request('GET', 'https://api.dmtry.me/api/users/' . $id);
        $httpRequest->on('response', function (\React\HttpClient\Response $httpResponse) use($response, &$userCache) {
            $httpResponse->on('data', function ($data, \React\HttpClient\Response $httpResponse) use($response, &$userCache) {
                if ($data instanceof \GuzzleHttp\Psr7\Stream && $httpResponse->getCode() == 200) {
                    $data = $data->getContents();

                    $response->write('User');
                    $response->write('Code' . $httpResponse->getCode());
                    $response->write('Data' . $data);

                    $user = json_decode($data)->result;
                    $userCache[(int) $user->id] = $user;
                } else {
                    $response->write('Bad request' . $httpResponse->getCode());
                }

                $response->end("\n");
//            $response->close();
            });
        });
        $httpRequest->end();
    }
});

$socket->listen(8080);
$loop->run();
