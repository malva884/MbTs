<?php

namespace App\Services;

use Exception;
use Google_Service_Slides;
use Google_Service_Slides_BatchUpdatePresentationRequest;
use Google_Service_Slides_Presentation;
use Google_Service_Slides_Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleSlide
{


    public static function create($title = 'Prova')
    {
        if (empty($disk))
            $disk = 'google';

        $driveService = Storage::disk($disk)->getAdapter()->getService();
        $client = $driveService->getClient();
        $client->useApplicationDefaultCredentials();
        $service = new Google_Service_Slides($client);
        try {
            $presentation = new Google_Service_Slides_Presentation($title);
            $presentation->title = $title;
            $presentation->layouts = [];
            //creating a presentation
            $presentation = $service->presentations->create($presentation);
            GoogleDrive::move($presentation->presentationId,'1BdrGbdXTfglPCIwtBzCDEIeyDmG9heyW');

        } catch (Exception $e) {
            Log::channel('stderr')->info($e->getMessage());
        }
        return $presentation;
    }

    public static function addPage($presentationId)
    {
        if (empty($disk))
            $disk = 'google';

        $driveService = Storage::disk($disk)->getAdapter()->getService();
        $client = $driveService->getClient();
        $client->useApplicationDefaultCredentials();
        $service = new Google_Service_Slides($client);
        $pageId = Str::uuid();
        try {
            $requests = array();
            $requests[] = new Google_Service_Slides_Request(array(
                'createSlide' => array(
                    'objectId' => $pageId,
                    'insertionIndex' => 1,
                    'slideLayoutReference' => array(
                        'predefinedLayout' => 'TITLE_AND_TWO_COLUMNS'
                    )
                )
            ));
            $batchUpdateRequest = new Google_Service_Slides_BatchUpdatePresentationRequest(array(
                'requests' => $requests
            ));

            $response = $service->presentations->batchUpdate($presentationId, $batchUpdateRequest);
            //$createSlideResponse = $response->getReplies()[0]->getCreateSlide();
            Log::channel('stderr')->info($pageId);
            return $pageId;
        } catch (Exception $e) {
            Log::channel('stderr')->info($e->getMessage());
        }
    }

    public static function addImage($presentationId, $pageId)
    {
        if (empty($disk))
            $disk = 'google';

        $driveService = Storage::disk($disk)->getAdapter()->getService();
        $client = $driveService->getClient();
        $client->useApplicationDefaultCredentials();
        $service = new Google_Service_Slides($client);
        try {

            $imageUrl = 'https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png';
            // Create a new image, using the supplied object ID, with content downloaded from imageUrl.
            $imageId = 'MyImage_01asdfsadfasdf';
            $emu4M = array('magnitude' => 4000000, 'unit' => 'EMU');
            $requests[] = new Google_Service_Slides_Request(array(
                'createImage' => array(
                    //'objectId' => $imageId,
                    'url' => $imageUrl,
                    'elementProperties' => array(
                        'pageObjectId' => $pageId,
                        'size' => array(
                            'height' => $emu4M,
                            'width' => $emu4M
                        ),
                        'transform' => array(
                            'scaleX' => 1,
                            'scaleY' => 1,
                            'translateX' => 100000,
                            'translateY' => 100000,
                            'unit' => 'EMU'
                        )
                    )
                )
            ));

            // Execute the request.
            $batchUpdateRequest = new Google_Service_Slides_BatchUpdatePresentationRequest(array(
                'requests' => $requests
            ));
            $response = $service->presentations->batchUpdate($presentationId, $batchUpdateRequest);
            $createImageResponse = $response->getReplies()[0]->getCreateImage();


            return $response;
        } catch (Exception $e) {
            Log::channel('stderr')->info($e->getMessage());
        }
    }
}
