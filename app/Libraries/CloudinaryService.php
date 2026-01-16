<?php

namespace App\Libraries;

use Cloudinary\Cloudinary;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_NAME'),
                'api_key'    => env('CLOUDINARY_KEY'),
                'api_secret' => env('CLOUDINARY_SECRET'),
            ],
        ]);
    }

    public function upload($source, $folder = 'products')
    {
        if (empty($source)) return null;

        try {
            $uploadResult = $this->cloudinary->uploadApi()->upload($source, [
                'folder' => 'my_store/' . $folder,
            ]);
             return json_decode(json_encode($uploadResult), true);
        } catch (\Exception $e) {
            throw new \Exception("Cloudinary error:" . $e->getMessage());
        }
    }

    public function destroy($publicId)
    {
        if (empty($publicId)) return false;
        
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return json_decode(json_encode($result), true);
        } catch (\Exception $e) {
            throw new \Exception("Cloudinary error: " . $e->getMessage());
        }
    }
}
