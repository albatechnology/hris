<?php

namespace App\Services\Aws;

use App\Enums\MediaCollection;
use App\Models\User;
use Aws\Credentials\Credentials;
use Aws\Rekognition\RekognitionClient;
use Exception;
use Illuminate\Http\UploadedFile;

class Rekognition
{
    static float $similarityThreshold = 90;

    public static function compareFace(User $user, UploadedFile $file)
    {
        $client = new RekognitionClient([
            'region' => config('filesystems.disks.s3.region', 'ap-southeast-1'),
            'version' => 'latest',
            'credentials' => new Credentials(config('filesystems.disks.s3.key', ''), config('filesystems.disks.s3.secret', ''))
        ]);

        $sourceImage = $user->getFirstMediaPath(MediaCollection::USER->value);
        if (empty($sourceImage)) {
            throw new Exception('User photo not found', 400);
        }

        try {
            // $image = fopen($file->getPathName(), 'r');
            // $bytes = fread($image, $file->getSize());
            // fclose($image);

            $result = $client->compareFaces([
                'SimilarityThreshold' => self::$similarityThreshold,
                'SourceImage' => [
                    'S3Object' => [
                        'Bucket' => config('filesystems.disks.s3.bucket', ''),
                        'Name' => $sourceImage
                    ]
                ],
                'TargetImage' => [
                    'Bytes' => file_get_contents($file),
                    // 'Bytes' => $bytes,
                    // 'S3Object' => [
                    //     'Bucket' => 'alba-hris-development',
                    //     // 'Name' => 'foto-nikko.png'
                    //     'Name' => '6/nikkoooo.jpg'
                    // ]
                ],
            ]);
        } catch (Exception $e) {
            if ($e instanceof \Aws\Rekognition\Exception\RekognitionException) {
                throw new Exception('Photo is invalid', 400);
            }
            throw $e;
        }

        if (
            isset($result['FaceMatches']) &&
            count($result['FaceMatches']) > 0 &&
            collect($result['FaceMatches'])->contains(fn($match) => $match['Similarity'] >= self::$similarityThreshold)
        ) return true;

        return false;
    }
}
