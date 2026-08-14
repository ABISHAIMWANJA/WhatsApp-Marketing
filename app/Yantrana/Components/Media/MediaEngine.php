<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */


/**
 * MediaEngine.php - Main component file
 *
 * This file is part of the Media component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Media;

use App\Yantrana\Base\BaseMediaEngine;
use App\Yantrana\Components\Media\Interfaces\MediaEngineInterface;
use Exception;
use File;
use Illuminate\Filesystem\Filesystem;
use YesFileStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Yantrana\Components\Vendor\Repositories\VendorRepository;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MediaEngine extends BaseMediaEngine implements MediaEngineInterface
{
    protected $elements;

    protected $currentDisk;

    protected $disk;

    protected $localDisk;

    /**
     * Constructor.
     *
     * @param  MediaRepository  $mediaRepository  - Media Repository
     *-----------------------------------------------------------------------*/
    public function __construct()
    {
        $this->currentDisk = config('filesystems.default', 'public-media-storage'); //configItem('current_filesystem_driver');
        $this->disk = YesFileStorage::on($this->currentDisk); // do_s3_space, local
        $this->elements = config('yes-file-storage.element_config');
    }

    /**
     * Process Upload Logo
     *
     * @return array|object
     *---------------------------------------------------------------- */
    public function processUploadLogo($inputFile, $requestFor)
    {
        $logoFolderPath = getPathByKey('logo');

        return $this->processUpload($inputFile, $logoFolderPath, $requestFor);
    }
      /**
     * Process Upload Dark Theme Logo
     *
     * @return array|object
     *---------------------------------------------------------------- */
    public function processUploadDarkThemeLogo($inputFile, $requestFor)
    {
        $logoFolderPath = getPathByKey('dark_theme_logo');

        return $this->processUpload($inputFile, $logoFolderPath, $requestFor);
    }

    /**
     * Process Upload Logo
     *
     * @return array|object
     *---------------------------------------------------------------- */
    public function processVendorUpload($inputFile, $requestFor, $allowedItems = [])
    {
        if (! array_key_exists($requestFor, $allowedItems)) {
            return $this->engineFailedResponse([], __tr('Invalid Request'));
        }

        $logoFolderPath = getPathByKey($requestFor, ['{_uid}' => getVendorUid()]);

        return $this->processUpload($inputFile, $logoFolderPath, $requestFor, true, getVendorSettings('logo_name'));
    }

    /**
     * Process Upload Logo
     *
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function processUploadSmallLogo($inputFile, $requestFor)
    {
        $logoFolderPath = getPathByKey('small_logo');

        return $this->processUpload($inputFile, $logoFolderPath, $requestFor);
    }

     /**
     * Process Upload Dark Theme Logo
     *
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function processUploadDarkThemeSmallLogo($inputFile, $requestFor)
    {
        $logoFolderPath = getPathByKey('dark_theme_small_logo');

        return $this->processUpload($inputFile, $logoFolderPath, $requestFor);
    }

    /**
     * Process Upload Logo
     *
     * @return array|object
     *---------------------------------------------------------------- */
    public function processUploadFavicon($inputFile, $requestFor)
    {
        $logoFolderPath = getPathByKey('favicon');

        return $this->processUpload($inputFile, $logoFolderPath, $requestFor);
    }

     /**
     * Process Upload Dark Theme Favicon
     * @return array|object
     *---------------------------------------------------------------- */
    public function processUploadDarkThemeFavicon($inputFile, $requestFor)
    {
        $logoFolderPath = getPathByKey('dark_theme_favicon');

        return $this->processUpload($inputFile, $logoFolderPath, $requestFor);
    }

    /**
     * Process Upload Profile Image
     *
     * @return array|object
     *---------------------------------------------------------------- */
    public function processUploadProfile($inputFile, $requestFor)
    {
        $uploadedFileOnLocalServer = $this->processUploadFileOnLocalServer($inputFile, $requestFor);

        if ($uploadedFileOnLocalServer['reaction_code'] == 1) {
            $fileName = $uploadedFileOnLocalServer['data']['fileName'];
            $profileImageFolderPath = getPathByKey('profile_photo', ['{_uid}' => authUID()]);

            return $this->resizeImageAndUpload($profileImageFolderPath, $fileName, [
                'height' => 360,
                'width' => 360,
            ]);

            return $this->engineFailedResponse([], __tr('Something went wrong while file moving.'));
        }

        return $uploadedFileOnLocalServer;
    }

    /**
     * Process Upload Profile Image
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processUploadCoverPhoto($inputFile, $requestFor)
    {
        $uploadedFileOnLocalServer = $this->processUploadFileOnLocalServer($inputFile, $requestFor);

        if ($uploadedFileOnLocalServer['reaction_code'] == 1) {
            $fileName = $uploadedFileOnLocalServer['data']['fileName'];
            $coverPhotoFolderPath = getPathByKey('cover_photo', ['{_uid}' => authUID()]);

            return $this->resizeImageAndUpload($coverPhotoFolderPath, $fileName, [
                'height' => 312,
                'width' => 820,
            ]);

            return $this->engineFailedResponse([], __tr('Something went wrong while file moving.'));
        }

        return $uploadedFileOnLocalServer;
    }

    /**
     * Process Upload Profile Image
     *
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function whatsappMediaUploadProcess($inputFile, $requestFor)
    {
        $uploadedFileOnLocalServer = $this->processUploadFileOnLocalServer($inputFile, $requestFor);

        if ($uploadedFileOnLocalServer['reaction_code'] == 1) {
            $fileName = $uploadedFileOnLocalServer['data']['fileName'];
            $itemImageFolderPath = getPathByKey($requestFor, ['{_uid}' => getVendorUid()]);

            return $this->resizeImageAndUpload($itemImageFolderPath, $fileName);
        }

        return $uploadedFileOnLocalServer;
    }

    /**
     * Download file and store
     *
     * @return array
     *---------------------------------------------------------------- */
    public function downloadAndStoreMediaFile($fileValue, $vendorUid, $mediaType = 'image')
    {
        $mimeTypesToExtension = [
            // audio
            'audio/aac' => 'aac',
            'audio/mp4' => 'm4a', // or 'mp4' if you are not distinguishing between audio-only and video
            'audio/mpeg' => 'mp3',
            'audio/amr' => 'amr',
            'audio/ogg' => 'ogg',
            // videos
            'video/mp4' => 'mp4',
            'video/3gp' => '3gp',
            'video/mpeg'      => 'mpeg',
            // images
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            // documents
            'text/plain' => 'txt',
            'application/pdf' => 'pdf',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            // Add more MIME types and their corresponding extensions as needed.
            'application/zip' => 'zip',
        ];
        $filesStored = [];
        $tempUploadFolderPath = '';
        $filename = '';
        try {
            if (isset($fileValue['media_url']) and !__isEmpty($fileValue['media_url'])) {
                $fileData = Http::get($fileValue['media_url']);
                if (!isset($fileValue['mime_type'])) {
                    $fileValue['mime_type'] = $fileData->header('Content-Type');
                }
            } else {
                $fileData = $fileValue['body'];
            }

            if ($fileData) {
                $permanentFolderPath = getPathByKey("whatsapp_$mediaType", ['{_uid}' => $vendorUid]);
                $tempUploadFolderPath = getPathByKey('user_temp_uploads', ['{_uid}' => $vendorUid]);
                $filename = uniqid().'.'.$mimeTypesToExtension[$fileValue['mime_type']];
                // temp file storage
                $writtenFile = $this->disk->writeFile($tempUploadFolderPath.'/'.$filename, $fileData);
                // move to permanent storage
                $storedInfo = $this->processMoveFile($permanentFolderPath, $filename, [], [
                    'setVisibility' => 'public',
                    'publicMediaStorage' => false,
                    'pathParameters' => [
                        '{_uid}' => $vendorUid,
                    ],
                ]);
                $filesStored = $storedInfo->data();
            }
        } catch (Exception $e) {
            __logDebug($e->getMessage());
            // remove temp and permanent file if exists
            if ($filename and $this->disk->isExists($tempUploadFolderPath.'/'.$filename)) {
                $this->disk->deleteFile($tempUploadFolderPath.'/'.$filename);
            }
            if ($filename and $this->disk->isExists($permanentFolderPath.'/'.$filename)) {
                $this->disk->deleteFile($permanentFolderPath.'/'.$filename);
            }
        }

        return $filesStored;
    }

    /**
     * Delete temp file
     *
     * @param  string  $filename
     * @return bool
     *---------------------------------------------------------------- */
    public function deleteLocalTempFile($filename)
    {
        $path = getPathByKey('user_temp_uploads', ['{_uid}' => authUID()]);

        return $this->processDeleteFile($path, $filename);
    }

    /**
     * Delete media image
     *
     * @param  number  $productID
     * @return bool
     *---------------------------------------------------------------- */
    public function processDeleteFile($destinationPath, $filename = null)
    {
        $imageMediaPath = $destinationPath.'/'.$filename;
        // Check if image media exist & is deleted successfully
        if ($this->disk->isExists($imageMediaPath) and $this->disk->deleteFile($imageMediaPath)) {
            return true;
        }

        return false;
    }

    /**
     * Delete user all account data
     *
     * @return array
     *---------------------------------------------------------------- */
    public function deleteUserVendor()
    {
        $userVendorFolderPath = getPathByKey('user', ['{_uid}' => getUserUID()]);

        return $this->disk->deleteFolder($userVendorFolderPath);
    }

    /**
     * Delete all vendors temp media
     *
     * @return array
     *---------------------------------------------------------------- */
    public function deleteAllVendorTempMedia()
    {
        $disk = $this->disk;
        $threshold = now()->subDay()->timestamp;

        collect($disk->getAllFiles(getPathByKey('vendor_temp_uploads')))
            ->chunk(200)
            ->each(function ($files) use ($disk, $threshold) {
                foreach ($files as $file) {
                    try {
                        if ($disk->fileModifiedAt($file) <= $threshold) {
                            $disk->deleteFile($file);
                        }
                    } catch (\Throwable $e) {
                        __logDebug("Failed deleting file: {$file}, message: {$e->getMessage()}");
                    }
                }
            });
    }

    /**
     * Delete vendor media files
     *
     * @return array
     *---------------------------------------------------------------- */
    public function deleteVendorMediaFiles($vendorUid)
    {
        $userVendorFolderPath = getPathByKey('vendor', ['{_uid}' => $vendorUid]);
        $allVendorFoldersPath = getPathByKey('vendor_media');
        
        // Check if vendor folder path and base path are same
        if ($userVendorFolderPath == $allVendorFoldersPath) {
            return false;
        }

        // Check if correct folder uid are there
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $vendorUid)) {
            return false;
        }

        $vendorsTempFolderPath = getPathByKey('vendor_temp_uploads');
        $tempUploadFolderPath = getPathByKey('user_temp_uploads', ['{_uid}' => $vendorUid]);

        // Check if vendor folder path and base path are same
        if ($vendorsTempFolderPath == $tempUploadFolderPath) {
            return false;
        }

        // Check if correct folder uid are there
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $vendorUid)) {
            return false;
        }

        // delete vendor temp folder
        $this->disk->deleteFolder($tempUploadFolderPath);

        // Delete vendor media and files folder
        return $this->disk->deleteFolder($userVendorFolderPath);
    }

    /**
     * Process Upload Logo
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processUploadTranslationFile($inputFile, $requestFor)
    {
        $logoFolderPath = getPathByKey('language_file');
        $this->disk = YesFileStorage::on('local');
        $uploadResult = $this->processUpload($inputFile, $logoFolderPath, $requestFor);
        $this->disk = YesFileStorage::on($this->currentDisk);

        return $uploadResult;
    }
    /**
     * Process Import Contacts
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processUploadImportContactFile($inputFile)
    {
        $logoFolderPath = getPathByKey('vendor_contact_import');
        $this->disk = YesFileStorage::on('local');
        $uploadResult = $this->processUpload($inputFile, $logoFolderPath, 'vendor_contact_import');
        $this->disk = YesFileStorage::on($this->currentDisk);
        return $uploadResult;
    }

    /**
     * Delete older files
     *
     * @param  string  $dir
     * @param  int  $max_age  - default is 24 hours
     * @return void
     */
    public function deleteOldFiles($dir, $max_age = 3600) // 1 hours
    {
        $list = [];

        $limit = time() - $max_age;

        $dir = realpath($dir);

        if (! is_dir($dir)) {
            return;
        }

        $dh = opendir($dir);
        if ($dh === false) {
            return;
        }

        while (($file = readdir($dh)) !== false) {
            $file = $dir.'/'.$file;
            if (! is_file($file)) {
                continue;
            }

            if (filemtime($file) < $limit) {
                $list[] = $file;
                unlink($file);
            }
        }
        closedir($dh);

        return $list;
    }

    /**
     * Process Upload Profile Image
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processUploadProfilePicture($inputFile, $requestFor, $pathValues = [])
    {
        $uploadedFileOnLocalServer = $this->processUploadFileOnLocalServer($inputFile, $requestFor);
        if ($uploadedFileOnLocalServer['reaction_code'] == 1) {
            $fileName = $uploadedFileOnLocalServer['data']['fileName'];
            $profileImageFolderPath = getPathByKey('profile_picture', $pathValues);

            return $this->resizeImageAndUpload($profileImageFolderPath, $fileName, [
                'height' => 360,
                'width' => 360,
            ]);

            return $this->engineFailedResponse([], __tr('Something went wrong while file moving.'));
        }

        return $uploadedFileOnLocalServer;
    }

    /**
     * Common Process Upload Image
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processUploadedFile($inputFile, $requestFor, $pathValues = [], $options = [])
    {
        $options = array_merge([
            'resize' => null,
        ], $options);
        $uploadedFileOnLocalServer = $this->processUploadFileOnLocalServer($inputFile, $requestFor);
        if ($uploadedFileOnLocalServer['reaction_code'] == 1) {
            $fileName = $uploadedFileOnLocalServer['data']['fileName'];
            $uploadedItemFolderPath = getPathByKey($requestFor, $pathValues);

            $processReaction = $this->resizeImageAndUpload($uploadedItemFolderPath, $fileName);
            if ($processReaction['reaction_code'] == 1) {
                return $this->engineSuccessResponse([
                    'folder_path' => $uploadedItemFolderPath,
                    'file_name' => $fileName,
                    'file_url' => getMediaUrl($uploadedItemFolderPath, $fileName),
                    'file_path' => $uploadedItemFolderPath.DIRECTORY_SEPARATOR.$fileName,
                ], __tr('File Uploaded Successfully'));
            }

            return $this->engineFailedResponse([], __tr('Something went wrong while file moving.'));
        }

        return $uploadedFileOnLocalServer;
    }

    public function getMimeType($path) 
    {
        $fileData = Http::get($path);
        
        return $fileData->header('Content-Type');
    }

    public function prepareMediaAndFileSupportData()
    {
        $basePath = getPathByKey('vendor_media');

        $vendorRepository = new VendorRepository();
        $allVendors = $vendorRepository->fetchItAll(null, ['_id', '_uid', 'title']);
        $vendorData = $allVendors->pluck('title', '_uid')->all();

        $mediaTypes = collect($this->disk->getAllFiles($basePath, $vendorData))
            ->filter(function ($file) {
                // Skip hidden files/folders (any segment starting with .)
                return !collect(explode('/', $file))->contains(function ($segment) {
                    return str_starts_with($segment, '.');
                });
            })
            ->map(function ($file) {
                return strtok(explode('whatsapp_media/', $file, 2)[1] ?? '', '/');
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'vendorData' => $vendorData,
            'mediaTypes' => $mediaTypes
        ];
    }
    
    /**
     * Prepare list of media and files (Chunk Pagination - Fastest)
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareListOfMediaAndFiles($vendorUID, $mediaType)
    {
        $basePath = getPathByKey('vendor_media');

        // DataTables params
        $start  = (int) request('start', 0);
        $length = (int) request('length', 100);
        $search = request('search.value');

        $vendorRepository = new VendorRepository();
        $allVendors = $vendorRepository->fetchItAll(null, ['_id', '_uid', 'title']);
        $vendorData = $allVendors->pluck('title', '_uid')->all();

        $results = [];
        $skipped = 0;
        $collected = 0;
        if(!file_exists($basePath)) {
            return response()->json([
                "draw" => intval(request('draw')),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
            ]);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {

            if (!$file->isFile()) continue;

            $filePath = $file->getPathname();

            // Skip hidden files
            if (collect(explode('/', $filePath))
                ->contains(fn ($part) => str_starts_with($part, '.'))) {
                continue;
            }

            $vendorUid = strtok(explode('vendors/', $filePath, 2)[1] ?? '', '/');
            $media_type = strtok(explode('whatsapp_media/', $filePath, 2)[1] ?? '', '/');

            // Filters first (important for performance)
            if ($mediaType !== 'all' && $media_type !== $mediaType) continue;
            if ($vendorUID !== 'all' && $vendorUid !== $vendorUID) continue;

            $item = [
                'vendor_uid'   => $vendorUid,
                'vendor_title' => $vendorData[$vendorUid] ?? __tr('Unknown'),
                'media_type'   => $media_type,
                'file_name'    => basename($filePath),
                'path'         => $filePath,
                'directory'    => dirname($filePath),
                'url'          => getMediaUrl($filePath, ''),
                'size_kb'      => round($file->getSize() / 1024, 2),
                'created_at'   => $file->getMTime(),
                'formatted_date' => formatDateTime($file->getMTime()),
            ];

            // Search
            if (!empty($search)) {
                $searchLower = Str::lower($search);

                if (!(
                    Str::contains(Str::lower($item['file_name']), $searchLower) ||
                    Str::contains(Str::lower($item['vendor_title']), $searchLower) ||
                    Str::contains(Str::lower($item['media_type']), $searchLower)
                )) {
                    continue;
                }
            }

            // Skip previous records (pagination)
            if ($skipped < $start) {
                $skipped++;
                continue;
            }

            // 📦 Collect current page
            if ($collected < $length) {
                $results[] = $item;
                $collected++;
            } else {
                break; // STOP immediately when page full
            }
        }

        return response()->json([
            "draw" => intval(request('draw')),
            "recordsTotal" => $start + count($results) + 1, // fake but needed for DataTables
            "recordsFiltered" => $start + count($results) + 1,
            "data" => $results,
        ]);
    }

    /**
     * Process delete all media image
     *
     * @param  number  $inputData
     * @return bool
     *---------------------------------------------------------------- */
    public function processDeleteAllFiles($deletedFileData)
    {
        if (!__isEmpty($deletedFileData['selected_media'])) {
            foreach($deletedFileData['selected_media'] as $mediaOrFile) {
                $fileOrMediaPath = $mediaOrFile['filepath'].'/'.$mediaOrFile['filename'];
                $this->disk->isExists($fileOrMediaPath);
                $this->disk->deleteFile($fileOrMediaPath);
            }

            return $this->engineSuccessResponse([], __tr('__fileCount__ Files deleted successfully.', [
                '__fileCount__' => count($deletedFileData)
            ]));
        }

        return $this->engineFailedResponse([], __tr('Something went wrong, Please try again.'));
    }
}
