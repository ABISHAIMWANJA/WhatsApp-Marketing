<?php

/**
 *  YesFileStorage Configurations
 *
 *  This configuration file is part of YesFileStorage
 *
 *------------------------------------------------------------------------------------------------*/

return [

    /**  Restrictions for elements to be uploaded
     *--------------------------------------------------------------------------------------------*/
    'element_config' => [
        'all' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/jpg', 'image/JPG', 'image/jpeg', 'image/JPEG'],
                'allowedFileExtensions' => ['jpg', 'png', 'jpeg'],
            ],
        ],
        'logo' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/png', 'image/PNG', 'image/svg', 'image/svg+xml'],
                'allowedFileExtensions' => ['png', 'svg'],
            ],
        ],
        'small_logo' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/png', 'image/PNG', 'image/svg', 'image/svg+xml'],
                'allowedFileExtensions' => ['png', 'svg'],
            ],
        ],
        'favicon' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/ico', 'image/vnd.microsoft.icon', 'image/png', 'image/PNG'],
                'allowedFileExtensions' => ['png', 'ico'],
            ],
        ],
        'dark_theme_logo' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/png', 'image/PNG', 'image/svg', 'image/svg+xml'],
                'allowedFileExtensions' => ['png', 'svg'],
            ],
        ],
        'dark_theme_small_logo' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/png', 'image/PNG', 'image/svg', 'image/svg+xml'],
                'allowedFileExtensions' => ['png', 'svg'],
            ],
        ],
        'dark_theme_favicon' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/ico', 'image/vnd.microsoft.icon', 'image/png', 'image/PNG'],
                'allowedFileExtensions' => ['png', 'ico'],
            ],
        ],
        'profile' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/jpg', 'image/JPG', 'image/jpeg', 'image/JPEG', 'image/png', 'image/gif', 'image/svg', 'image/svg+xml'],
                'allowedFileExtensions' => ['jpg', 'png', 'jpeg', 'svg', 'gif'],
            ],
        ],
        'cover_image' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/jpg', 'image/JPG', 'image/jpeg', 'image/JPEG', 'image/png'],
                'allowedFileExtensions' => ['jpg', 'png', 'jpeg'],
            ],
        ],
        'photos' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/jpg', 'image/JPG', 'image/jpeg', 'image/JPEG', 'image/png', 'image/gif', 'image/svg', 'image/svg+xml'],
                'allowedFileExtensions' => ['jpg', 'png', 'jpeg', 'svg', 'gif'],
            ],
        ],
        'language' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                'allowedFileExtensions' => ['xlsx'],
            ],
        ],
        'whatsapp_image' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/jpeg', 'image/png'],
                'allowedFileExtensions' => ['jpg', 'png', 'jpeg'],
            ],
        ],
        'whatsapp_sticker' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/webp'],
                'allowedFileExtensions' => ['webp'],
            ],
        ],
        'whatsapp_video' => [
            'restrictions' => [
                'allowedFileTypes' => [
                    'video/mp4',
                    'video/3gp'
                ],
                'allowedFileExtensions' => ['mp4', '3gp'],
            ],
        ],
        'whatsapp_audio' => [
            'restrictions' => [
                'allowedFileTypes' => [
                    'audio/aac',
                    'audio/mp4',
                    'audio/mpeg',
                    'audio/amr',
                    'audio/ogg',
                    'video/mp4',
                    'audio/webm',
                    'video/webm'
                ],
                'allowedFileExtensions' => ['aac', 'm4a', 'mp4', 'mp3', 'mpga', 'amr', 'ogg', 'oga', 'webm'],
            ],
        ],
        'whatsapp_document' => [
            'restrictions' => [
                'allowedFileTypes' => [
                    'text/plain',
                    'text/xml',
                    'application/vnd.ms-powerpoint',
                    'application/msword',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    // pdf specific types
                    'application/pdf',               // Standard PDF
                    'application/x-pdf',             // Older alias
                    'application/acrobat',           // Adobe-specific
                    'applications/vnd.pdf',          // Vendor-specific
                    'text/pdf',                      // Misclassified occasionally
                    'text/x-pdf',                    // Also seen in misconfigured servers
                ],
                'allowedFileExtensions' => ['txt', 'pdf', 'ppt', 'pps', 'doc', 'xls', 'docx', 'xlsx', 'pptx', 'xml'],
            ],
        ],
        'vendor_logo' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/png', 'image/PNG', 'image/svg', 'image/svg+xml'],
                'allowedFileExtensions' => ['png', 'svg'],
            ],
        ],
        'vendor_favicon' => [
            'restrictions' => [
                'allowedFileTypes' => ['image/ico', 'image/vnd.microsoft.icon', 'image/png', 'image/PNG'],
                'allowedFileExtensions' => ['png', 'ico'],
            ],
        ],
        'vendor_contact_import' => [
            'restrictions' => [
                'allowedFileTypes' => [
                    'text/csv', // 🔹 Standard MIME type for CSV files.
                    'text/csv',                 // ✅ Official standard MIME type for CSV files.
                    'text/plain',               // ✅ Common fallback MIME type for plain text (many CSVs are uploaded as text/plain).
                    'application/csv',          // ⚠️ Non-standard, but used by some older tools and servers.
                    'text/x-csv',               // ⚠️ Used by some browsers or systems (not officially standardized).
                    'application/x-csv',        // ⚠️ Similar to above — non-standard but appears in the wild.
                    'text/comma-separated-values', // ⚠️ Descriptive variant, sometimes used by older systems.
                    'inode/x-empty',            // ⚠️ Indicates a 0-byte (empty) file — may still be a .csv file with no data.
                    'application/vnd.ms-excel', // ✅ Needed for Firefox on Windows
                ],
                'allowedFileExtensions' => ['csv'],
            ],
        ],
        'addon_upload_file' => [
            'restrictions' => [
                'allowedFileTypes' => ['zip', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream', 'application/x-zip'],
            ],
        ],
    ],

    /** Uploaded media path
     *--------------------------------------------------------------------------------------------*/
    'storage_paths' => [
        'temp' => 'key@language_file', // it will be in local storage
        'internal_temp' => 'key@internal_temp', // it will be in local storage
        config('__misc.storage_base_folder', '') . 'media-storage' => [
            'users-temp-uploads' => [
                '' => 'key@vendor_temp_uploads',
                '{_uid}' => [
                    'temp_uploads' => 'key@user_temp_uploads',
                ],
            ],
            'logo' => 'key@logo',
            'small_logo' => 'key@small_logo',
            'favicon' => 'key@favicon',
            'dark_theme_logo' => 'key@dark_theme_logo',
            'dark_theme_small_logo' => 'key@dark_theme_small_logo',
            'dark_theme_favicon' => 'key@dark_theme_favicon',
            'vendors' => [
                '' => 'key@vendor_media',
                '{_uid}' => [
                    '' => 'key@vendor',
                    'logo' => 'key@vendor_logo',
                    'small_logo' => 'key@vendor_small_logo',
                    'favicon' => 'key@vendor_favicon',
                    'whatsapp_media' => [
                        'images' => 'key@whatsapp_image',
                        'videos' => 'key@whatsapp_video',
                        'documents' => 'key@whatsapp_document',
                        'audios' => 'key@whatsapp_audio',
                        'stickers' => 'key@whatsapp_sticker',
                    ],
                ],
            ],
            'users' => [
                '{_uid}' => [
                    '' => 'key@user',
                    // 'temp_uploads' => 'key@user_temp_uploads',
                    'profile' => 'key@profile_photo',
                    'cover' => 'key@cover_photo',
                    'photos' => 'key@user_photos',
                ],
            ],
        ],
    ],
];
