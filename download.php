<?php

require 'vendor/autoload.php';

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

if (!file_exists('data/platforms')) {
    mkdir('data/platforms', 0777, true);
}

if (!file_exists('data/games')) {
    mkdir('data/games', 0777, true);
}

$client = new Client([
    'base_uri' => 'http://thegamesdb.net/api/',
]);

$response = $client->request('GET', 'GetPlatformsList.php');

$platformsXml = simplexml_load_string($response->getBody()->__toString());

foreach ($platformsXml->Platforms->Platform as $platform) {
    $response = $client->request('GET', 'GetPlatform.php', [
        'sink' => 'data/platforms/' . $platform->id->__toString() . '.xml',
        'query' => [
            'id' => $platform->id->__toString()
        ]
    ]);

    $platformXml = simplexml_load_string($response->getBody()->__toString());

    images($platformXml->baseImgUrl->__toString(), $platformXml->Platform->Images->children());

    $response = $client->request('GET', 'GetPlatformGames.php', [
        'query' => [
            'platform' => $platform->id->__toString()
        ]
    ]);

    $platformGamesXml = simplexml_load_string($response->getBody()->__toString());

    foreach ($platformGamesXml->Game as $game) {
        $response = $client->request('GET', 'GetGame.php', [
            'sink' => 'data/games/' . $game->id->__toString() . '.xml',
            'debug' => true,
            'query' => [
                'id' => $game->id->__toString()
            ]
        ]);

        $gameXml = simplexml_load_string($response->getBody()->__toString());
        
        images($gameXml->baseImgUrl->__toString(), $gameXml->Game->Images->children());
    }
}

function images($baseUrl, $images) {
    foreach ($images as $image) {
        if ($image->count() > 0) {
            images($baseUrl, $image->children());
        } else {
            download($baseUrl . $image->__toString(), 'img/' . $image->__toString());
        }
    }
}

function download($url, $file) {
    if (!file_exists(dirname($file))) {
        mkdir(dirname($file), 0777, true);
    }

    if (file_exists($file)) {
        return true;
    }

    try {
        $client = new GuzzleHttp\Client;
        $response = $client->request('GET', $url, [
            'sink' => $file,
        ]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
