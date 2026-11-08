<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SharePointService
{
    private $tenantId;
    private $clientId;
    private $clientSecret;
    private $siteId;
    private $driveId;
    private $isConfigured = false;

    public function __construct()
    {
        $settings = Setting::getSettings();
        
        $this->tenantId = $settings->sharepoint_tenant_id;
        $this->clientId = $settings->sharepoint_client_id;
        $this->clientSecret = $settings->sharepoint_client_secret ? Crypt::decrypt($settings->sharepoint_client_secret) : null;
        $this->siteId = $settings->sharepoint_site_id;
        $this->driveId = $settings->sharepoint_drive_id;

        if ($this->tenantId && $this->clientId && $this->clientSecret && $this->siteId && $this->driveId) {
            $this->isConfigured = true;
        }
    }

    private function getAccessToken()
    {
        if (!$this->isConfigured) return null;

        $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials'
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error('SharePoint Auth Error: ' . $response->body());
        return null;
    }

    public function uploadFile($fileContent, $fileName, $mimeType)
    {
        $token = $this->getAccessToken();
        if (!$token) throw new \Exception('SharePoint is not configured or authentication failed.');

        // الرفع إلى مجلد باسم UNRWA_Signed_Notes داخل SharePoint
        $url = "https://graph.microsoft.com/v1.0/sites/{$this->siteId}/drives/{$this->driveId}/root:/UNRWA_Signed_Notes/{$fileName}:/content";

        $response = Http::withToken($token)
            ->withBody($fileContent, $mimeType)
            ->put($url);

        if ($response->successful()) {
            return $response->json()['id']; // إرجاع الـ ID الخاص بالملف في SharePoint
        }

        Log::error('SharePoint Upload Error: ' . $response->body());
        throw new \Exception('Failed to upload file to SharePoint.');
    }

    public function downloadFile($fileId)
    {
        $token = $this->getAccessToken();
        if (!$token) throw new \Exception('SharePoint is not configured or authentication failed.');

        $url = "https://graph.microsoft.com/v1.0/sites/{$this->siteId}/drives/{$this->driveId}/items/{$fileId}/content";

        $response = Http::withToken($token)->get($url);

        if ($response->successful()) {
            return [
                'content' => $response->body(),
                'type' => $response->header('Content-Type')
            ];
        }

        Log::error('SharePoint Download Error: ' . $response->body());
        throw new \Exception('Failed to download file from SharePoint.');
    }
}