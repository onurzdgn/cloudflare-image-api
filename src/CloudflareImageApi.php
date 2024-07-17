<?php

namespace onurozdogan\CloudflareImageApi;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;

class CloudflareImageApi
{
    public function controlApiToken()
    {
        // Control Cloudflare API key
        $apiKey = config('cloudflareimageapi.api_key');

        if (empty($apiKey)) {
            return response()->json('Cloudflare API key is missing', 500);
        }

        try {
            $client = new Client();
            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
            ];
            $request = new Request('GET', 'https://api.cloudflare.com/client/v4/user/tokens/verify', $headers);
            $res = $client->sendAsync($request)->wait();

            if ($res->getStatusCode() !== 200) {
                return response()->json('Cloudflare API key is invalid', 500);
            }

            return response()->json(['message' => 'Cloudflare API key is valid'], 200);
        } catch (\Exception $e) {
            return response()->json('Cloudflare API key is invalid', 500);
        }
    }

    public function createTmpUrl()
    {
        // Create temporary URL for upload photo to Cloudflare
        if ($this->controlApiToken()->getStatusCode() !== 200) {
            return response()->json('Cloudflare API key is invalid', 500);
        }

        $client = new Client();

        $account_id = config('cloudflareimageapi.account_id');
        $api_token = config('cloudflareimageapi.api_key');

        try {
            $response = $client->request('POST', 'https://api.cloudflare.com/client/v4/accounts/' . $account_id . '/images/v2/direct_upload', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_token,
                ],
            ]);

            // If the request is successful, get the response
            $result = json_decode($response->getBody(), true);
            $tmpUrl = $result['result']['uploadURL'];

            return $tmpUrl;
        } catch (\Exception $e) {
            // If the request fails, show the error message
            echo $e->getMessage();
            return false;
        }
    }

    public function upload($photo, $name)
    {
        if ($this->controlApiToken()->getStatusCode() !== 200) {
            return response()->json('Cloudflare API key is invalid', 500);
        }
        // Download photo to Cloudflare

        $tmpUrl = $this->createTmpUrl();

        if ($tmpUrl === false) {
            return response()->json(['error' => 'Temporary URL could not be created'], 500);
        }

        $client = new Client();

        try {
            $response = $client->request('POST', $tmpUrl, [
                'multipart' => [
                    [
                        'name' => 'file',
                        'filename' => config('cloudflareimageapi.app_name') . '-' . $name ,
                        'contents' => Psr7\Utils::tryFopen($photo, 'r'),
                    ],
                ]
            ]);

            // If the request is successful, get the response
            $result = json_decode($response->getBody(), true);
            $photoId = $result['result']['id'];

            return response()->json(['photoId' => $photoId], 200);
        } catch (\Exception $e) {
            // If the request fails, show the error message
            echo $e->getMessage();
            return false;
        }
    }

    public function update($photoId, $newPhoto, $name)
    {
        // Cloudflare'daki fotoğrafı güncelleme işlemi
        $deleteStatus = $this->delete($photoId);

        if ($deleteStatus->getStatusCode() !== 200) {
            return false;
        }

        $newPhotoId = $this->upload($newPhoto, $name);

        if ($newPhotoId->getStatusCode() !== 200) {
            return false;
        }

        $updatedPhotoId = $newPhotoId->getOriginalContent()['photoId'];

        return response()->json(['photoId' => $updatedPhotoId], 200);
    }

    public function delete($photoId)
    {
        // Delete photo from Cloudflare
        $client = new Client();

        $account_id = config('cloudflareimageapi.account_id');
        $api_token = config('cloudflareimageapi.api_key');

        try {
            $response = $client->request('DELETE', 'https://api.cloudflare.com/client/v4/accounts/' . $account_id . '/images/v1/' . $photoId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_token,
                ],
            ]);

            return response()->json(['message' => 'Photo deleted successfully'], 200);
        } catch (\Exception $e) {
            echo $e->getMessage();
            return response()->json(['error' => 'Photo could not be deleted'], 500);
        }
    }
}
