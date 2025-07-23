<?php

namespace onurozdogan\CloudflareImageApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CloudflareImageApi
{
    /**
     * The Guzzle HTTP client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * The Cloudflare API key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The Cloudflare account ID.
     *
     * @var string
     */
    protected $accountId;

    /**
     * The application name.
     *
     * @var string
     */
    protected $appName;

    /**
     * Create a JSON response
     * 
     * @param array|string $data
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonResponse($data, int $status = 200)
    {
        // Use Laravel's response if available
        if (function_exists('response')) {
            return response()->json($data, $status);
        }

        // Create a custom response otherwise
        if (is_string($data)) {
            $data = ['message' => $data];
        }

        $response = new class ($data, $status) {
            protected $data;
            protected $status;

            public function __construct($data, $status)
            {
                $this->data = $data;
                $this->status = $status;
            }

            public function getStatusCode()
            {
                return $this->status;
            }

            public function getOriginalContent()
            {
                return $this->data;
            }

            public function __toString()
            {
                return json_encode($this->data);
            }
        };

        return $response;
    }

    /**
     * Create a new CloudflareImageApi instance.
     *
     * @param string|null $apiKey Cloudflare API key
     * @param string|null $accountId Cloudflare account ID
     * @param string|null $appName Application name
     * @return void
     */
    public function __construct(?string $apiKey = null, ?string $accountId = null, ?string $appName = null)
    {
        $this->client = new Client();

        // Try to get config values first, then fall back to constructor parameters or environment variables
        $this->apiKey = $apiKey ?? (function_exists('config') ? config('cloudflareimageapi.api_key') : env('CLOUDFLARE_API_KEY'));
        $this->accountId = $accountId ?? (function_exists('config') ? config('cloudflareimageapi.account_id') : env('CLOUDFLARE_ACCOUNT_ID'));
        $this->appName = $appName ?? (function_exists('config') ? config('cloudflareimageapi.app_name') : (env('APP_NAME') ?: 'CloudflareImageApi'));
    }

    /**
     * Control Cloudflare API token.
     *
     * @return mixed
     */
    public function controlApiToken()
    {
        if (empty($this->apiKey)) {
            return $this->jsonResponse(['error' => 'Cloudflare API key is missing'], 500);
        }

        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ];
            $request = new Request('GET', 'https://api.cloudflare.com/client/v4/user/tokens/verify', $headers);
            $res = $this->client->sendAsync($request)->wait();

            if ($res->getStatusCode() !== 200) {
                return $this->jsonResponse(['error' => 'Cloudflare API key is invalid'], 500);
            }

            return $this->jsonResponse(['message' => 'Cloudflare API key is valid'], 200);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Cloudflare API key is invalid: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create temporary URL for uploading photos to Cloudflare.
     *
     * @return mixed
     */
    public function createTmpUrl()
    {
        $tokenCheck = $this->controlApiToken();
        if ($tokenCheck->getStatusCode() !== 200) {
            return $tokenCheck;
        }

        try {
            $response = $this->client->request('POST', 'https://api.cloudflare.com/client/v4/accounts/' . $this->accountId . '/images/v2/direct_upload', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            $tmpUrl = $result['result']['uploadURL'];

            return $this->jsonResponse(['tmpUrl' => $tmpUrl], 200);
        } catch (GuzzleException $e) {
            return $this->jsonResponse(['error' => 'Temporary URL could not be created. Reason: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Temporary URL could not be created. Reason: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload a photo to Cloudflare.
     *
     * @param TemporaryUploadedFile|UploadedFile|string $photo
     * @param string $name
     * @return mixed
     */
    public function upload($photo, string $name)
    {
        $tokenCheck = $this->controlApiToken();
        if ($tokenCheck->getStatusCode() !== 200) {
            return $tokenCheck;
        }

        // Get temporary upload URL
        $tmpUrlResponse = $this->createTmpUrl();
        if ($tmpUrlResponse->getStatusCode() !== 200) {
            return $this->jsonResponse(['error' => 'Temporary URL could not be created'], 500);
        }

        $tmpUrl = $tmpUrlResponse->getOriginalContent()['tmpUrl'];

        // Get file path
        if ($photo instanceof TemporaryUploadedFile || $photo instanceof UploadedFile) {
            $path = $photo->getRealPath();
        } elseif (is_string($photo) && file_exists($photo)) {
            $path = $photo;
        } else {
            return $this->jsonResponse([
                'error' => 'Photo could not be uploaded. Cannot reach or find photo.'
            ], 500);
        }

        try {
            $response = $this->client->request('POST', $tmpUrl, [
                'multipart' => [
                    [
                        'name' => 'file',
                        'filename' => $this->appName . '-' . $name,
                        'contents' => Utils::tryFopen($path, 'r'),
                    ],
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            $photoId = $result['result']['id'];

            return $this->jsonResponse(['photoId' => $photoId], 200);
        } catch (GuzzleException $e) {
            return $this->jsonResponse(['error' => 'Photo could not be uploaded. Reason: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Photo could not be uploaded. Reason: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a photo on Cloudflare.
     *
     * @param string $photoId
     * @param TemporaryUploadedFile|UploadedFile|string $newPhoto
     * @param string $name
     * @return mixed
     */
    public function update(string $photoId, $newPhoto, string $name)
    {
        // Delete the existing photo first
        $deleteStatus = $this->delete($photoId);
        if ($deleteStatus->getStatusCode() !== 200) {
            return $this->jsonResponse([
                'error' => 'Photo could not be updated. Reason: ' .
                    ($deleteStatus->getOriginalContent()['error'] ?? 'Failed to delete the existing photo')
            ], 500);
        }

        // Upload the new photo
        $newPhotoResponse = $this->upload($newPhoto, $name);
        if ($newPhotoResponse->getStatusCode() !== 200) {
            return $this->jsonResponse([
                'error' => 'Photo could not be updated. Reason: ' .
                    ($newPhotoResponse->getOriginalContent()['error'] ?? 'Failed to upload new photo')
            ], 500);
        }

        $updatedPhotoId = $newPhotoResponse->getOriginalContent()['photoId'];

        return $this->jsonResponse(['photoId' => $updatedPhotoId], 200);
    }

    /**
     * Delete a photo from Cloudflare.
     *
     * @param string $photoId
     * @return mixed
     */
    public function delete(string $photoId)
    {
        $tokenCheck = $this->controlApiToken();
        if ($tokenCheck->getStatusCode() !== 200) {
            return $tokenCheck;
        }

        try {
            $response = $this->client->request('DELETE', 'https://api.cloudflare.com/client/v4/accounts/' . $this->accountId . '/images/v1/' . $photoId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);

            return $this->jsonResponse(['message' => 'Photo deleted successfully'], 200);
        } catch (GuzzleException $e) {
            return $this->jsonResponse(['error' => 'Photo could not be deleted. Reason: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Photo could not be deleted. Reason: ' . $e->getMessage()], 500);
        }
    }
}
