<?php

include_once __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$http = new React\Http\Server($socket);

class HttpServer
{
    /**
     * @var SplFixedArray
     */
    protected $userCache;

    /**
     * @var
     */
    protected $client;

    public function __construct($client)
    {
        $this->userCache = new SplFixedArray(1000);
        $this->client = $client;
    }

    public function request(\React\Http\Request $request, \React\Http\Response $response)
    {
        $response->writeHead(200, array('Content-Type' => 'text/plain'));

        $requestParameters = $request->getQuery();
        $id = isset($requestParameters['id']) ? (int) $requestParameters['id'] : 1;

        if (isset($this->userCache[$id])) {
            $response->write(json_encode($this->userCache[$id]));
            $response->end("\n");
        } else {
            $httpRequest = $this->client->request('GET', 'https://api.dmtry.me/api/users/' . $id);
            $httpRequest->on('response', function (\React\HttpClient\Response $httpResponse) use($response) {
                $httpResponse->on('data', function ($data, \React\HttpClient\Response $httpResponse) use($response) {
                    if ($data instanceof \GuzzleHttp\Psr7\Stream && $httpResponse->getCode() == 200) {
                        $data = $data->getContents();

                        $response->write('User');
                        $response->write('Code' . $httpResponse->getCode());
                        $response->write('Data' . $data);

                        $user = json_decode($data)->result;
                        $this->userCache[(int) $user->id] = $user;
                    } else {
                        $response->write('Bad request' . $httpResponse->getCode());
                    }

                    $response->end("\n");
                });
            });
            $httpRequest->end();
        }
    }
}

$httpServer = new HttpServer($client);
$http->on('request', [$httpServer, 'request']);

$loop->addPeriodicTimer(1, function () {
    $memory = memory_get_usage() / 1024;
    $formatted = number_format($memory, 3).'K';
    echo "Current memory usage: {$formatted}\n";
});

$loop->addPeriodicTimer(60, function () {
    echo "Clean \n";
    gc_collect_cycles();
});

gc_disable();

$socket->listen(8080);
$loop->run();
