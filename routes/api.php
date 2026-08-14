<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Yantrana\Components\Auth\Controllers\ApiUserController;
use App\Yantrana\Components\Contact\Controllers\ContactController;
use App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppServiceController;
use App\Yantrana\Components\Media\Controllers\MediaController;
use App\Yantrana\Components\User\Controllers\UserController;

use App\Yantrana\Components\{
    Auth\Controllers\AuthController
};
use App\Yantrana\Components\BotReply\Controllers\BotReplyController;
use App\Yantrana\Components\Campaign\Controllers\CampaignController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// external apis
// base url
Route::any('/', function () {
    return 'api endpoint';
})->name('api.base_url');

Route::group([
    'middleware' => 'api.vendor.authenticate',
    'prefix' => '{vendorUid}/',
], function () {
    Route::post('/contact/send-message', [
        WhatsAppServiceController::class,
        'apiSendChatMessage',
    ])->name('api.vendor.chat_message.send.process');
    // Get message status
    Route::get('/contact/message-status', [
        WhatsAppServiceController::class,
        'apiGetMessageStatus',
    ])->name('api.vendor.chat_message.read.status');
    // send media message
    Route::post('/contact/send-media-message', [
        WhatsAppServiceController::class,
        'apiSendMediaChatMessage',
    ])->name('api.vendor.chat_message_media.send.process');
    // Get template list
    Route::get('/contact/template-list', [
        WhatsAppServiceController::class,
        'apiGetTemplateList',
    ])->name('api.vendor.template_list.read.list');
    // send media message
    Route::post('/contact/send-template-message', [
        WhatsAppServiceController::class,
        'apiSendTemplateChatMessage',
    ])->name('api.vendor.chat_template_message.send.process');
    // Send carousel template message
    Route::post('/contact/send-carousel-template-message', [
        WhatsAppServiceController::class,
        'apiSendTemplateChatMessage',
    ])->name('api.vendor.chat_carousel_template_message.send.process');
    // send interactive message
    Route::post('/contact/send-interactive-message', [
        WhatsAppServiceController::class,
        'apiSendInteractiveChatMessage',
    ])->name('api.vendor.chat_message_interactive.send.process');
    // create new contact
    Route::post('/contact/create', [
        ContactController::class,
        'apiProcessContactCreate',
    ])->name('api.vendor.contact.create.process');
    // update contact
    Route::post('/contact/update/{phoneNumber}', [
        ContactController::class,
        'apiProcessContactUpdate',
    ])->name('api.vendor.contact.update.process');
    // assign team member
    Route::post('/contact/assign-team-member', [
        ContactController::class,
        'apiAssignTeamMemberToContact',
    ])->name('api.vendor.contact.assign_member.update.process');
    // get contact list
    Route::get('contacts', [
        ContactController::class,
        'apiGetContactList',
    ])->name('api.vendor.contact.read.list');
    // get contact by phone number or email
    Route::get('contact', [
        ContactController::class,
        'apiGetContact',
    ])->name('api.vendor.contact.read.single_contact');
    // get contact groups
    Route::get('contact/groups', [
        ContactController::class,
        'apiGetContactGroupList',
    ])->name('api.vendor.contact.read.group_list');
    // get contact labels and tags
    Route::get('contact/labels-tags', [
        ContactController::class,
        'apiGetContactLabelsAndTagsList',
    ])->name('api.vendor.contact.read.labels_and_tags_list');
    // assign groups to contacts
    Route::post('/contact/assign-groups', [
        ContactController::class,
        'apiAssignGroupsToContact',
    ])->name('api.vendor.contact.assign_groups.update.process');
    // unassign groups to contacts
    Route::post('/contact/unassign-groups', [
        ContactController::class,
        'apiUnassignGroupsFromContact',
    ])->name('api.vendor.contact.unassign_groups.update.process');
    // assign labels/tags to contacts
    Route::post('/contact/assign-labels', [
        ContactController::class,
        'apiAssignLabelsToContact',
    ])->name('api.vendor.contact.assign_labels.update.process');
    // unassign labels/tags to contacts
    Route::post('/contact/unassign-labels', [
        ContactController::class,
        'apiUnassignLabelsFromContact',
    ])->name('api.vendor.contact.unassign_labels.update.process');
    // schedule new campaign 
    Route::post('/campaign/schedule', [
        WhatsAppServiceController::class,
        'apiScheduleCampaign',
    ])->name('api.vendor.campaign.write.schedule');
    // Get paginated list of campaign
    Route::get('campaign', [
        CampaignController::class,
        'apiGetCampaignList',
    ])->name('api.vendor.campaign.read.list');
    // Get campaign statuses
    Route::get('campaign-status/{campaignUid}', [
        CampaignController::class,
        'apiGetCampaignStatus',
    ])->name('api.vendor.campaign.read.status_details');
});

// Mobile app apis
Route::group(['middleware' => 'guest'], function () {

     //vendor registration
    Route::post('/register/vendor', [
        AuthController::class,
        'register',
    ])->name('api.auth.register.process');
     //vendor activation
    Route::post('/register/vendor/activation', [
        AuthController::class,
        'activationRequiredRegister',
    ])->name('api.activation_required.auth.register.process');

    Route::group([
        'prefix' => 'user',
    ], function () {
        // login process
        Route::post('/login-process', [
            ApiUserController::class,
            'loginProcess'
        ])->name('api.user.login.process');

        // User Registration prepare data
        Route::get('/prepare-sign-up', [
            ApiUserController::class,
            'prepareSignUp'
        ])->name('api.user.sign_up.prepare');

        // User Registration
        Route::post('/process-sign-up', [
            ApiUserController::class,
            'processSignUp'
        ])->name('api.user.sign_up.process');
       
        // Verify 2-Factor Authentication
        Route::post('/two-factor-challenge', [
            AuthController::class,
            'verifyTwoFactorAuthentication'
        ])->name('api.two_factor_authentication.verify');
    });
});
// vendor authenticated routes
Route::group([
    'middleware' => 'app_api.vendor.authenticate',
], function () {
    // broadcast private channel check
    Broadcast::routes([]);

    /*
    Media Component Routes Start from here
    ------------------------------------------------------------------- */
    Route::group([
        'prefix' => 'media',
    ], function () {
        // Temp Upload
        Route::post('/upload-temp-media/{uploadItem?}', [
            MediaController::class,
            'uploadTempMedia',
        ])->name('api.media.upload_temp_media');
    });

    /*
    User Device Component Routes Start from here 
    ------------------------------------------------------------------- */
    Route::group([
        'prefix' => 'user-device',
    ], function () {
        // Store User Device Token
        Route::post('/token', [
            ApiUserController::class,
            'storeUserDeviceToken',
        ])->name('api.user.device_token.write');
    });

    Route::group([
        'prefix' => 'vendor/',
    ], function () {
        //unread chat count
        Route::get('/whatsapp/chat/unread-count', [
            WhatsAppServiceController::class,
            'unreadCount',
        ])->name('app_api.vendor.chat_message.read.unread_count');
        // get contacts data
        Route::get('/contact/contacts-data/{contactUid?}', [
            WhatsAppServiceController::class,
            'getContactsData',
        ])->name('app_api.vendor.contacts.data.read');
        // contact chat data
        Route::get('/whatsapp/contact/chat/{contactUid?}', [
            WhatsAppServiceController::class,
            'chatView',
        ])->name('app_api.vendor.chat_message.contact.view');
        // contact chat data via append, prepend etc
        Route::get('/whatsapp/contact/chat-data/{contactUid}/{way?}', [
            WhatsAppServiceController::class,
            'getContactChatData',
        ])->name('app_api.vendor.chat_message.data.read');
        Route::post('/whatsapp/contact/chat/send', [
            WhatsAppServiceController::class,
            'sendChatMessage',
        ])->name('app_api.vendor.chat_message.send.process');

        // Contact get labels and team members data
        Route::get('/whatsapp/contact/chat-box-data/{contactUid}', [
            ContactController::class,
            'getLabelsForApi',
        ])->name('app_api.chat.box.base.data');
            // Contact get the data
        Route::get('/contacts/{contactIdOrUid}/get-update-data', [
            ContactController::class,
            'updateContactData',
        ])->name('app_api.vendor.contact.read.update.data');
        //media type api
        Route::get('/whatsapp/contact/chat/prepare-send-media/{mediaType?}', [
            WhatsAppServiceController::class,
            'prepareSendMediaUploader',
        ])->name('app_api.vendor.chat_message_media.upload.prepare');
        Route::post('/whatsapp/contact/chat/send-media', [
            WhatsAppServiceController::class,
            'sendChatMessageMedia',
        ])->name('app_api.vendor.chat_message_media.send.process');
        Route::post('/whatsapp/contact/chat/update-notes', [
            ContactController::class,
            'updateNotes',
        ])->name('app_api.vendor.chat.update_notes.process');
        Route::post('/whatsapp/contact/chat/assign-user', [
            ContactController::class,
            'assignChatUser',
        ])->name('app_api.vendor.chat.assign_user.process');
        Route::post('/whatsapp/contact/chat/assign-labels', [
            ContactController::class,
            'assignContactLabels',
        ])->name('app_api.vendor.chat.assign_labels.process');
        //clear chat history
        Route::post('/whatsapp/contact/chat/clear-history/{contactUid}', [
            WhatsAppServiceController::class,
            'clearChatHistory',
        ])->name('app_api.vendor.chat_message.delete.process');
            //create whatsapp contact label
         Route::post('/whatsapp/contact/create-label', [
            ContactController::class,
            'createLabel',
        ])->name('app_api.vendor.chat.label.create.write');
        //whatsapp contact edit lable
            Route::post('/whatsapp/contact/chat/edit-label', [
            ContactController::class,
            'updateLabel',
        ])->name('app_api.vendor.chat.label.update.write');
            //whatsapp contact delete lable
            Route::post('/whatsapp/contact/chat/delete-label/{labelUid}', [
            ContactController::class,
            'deleteLabelProcess',
        ])->name('app_api.vendor.chat.label.delete.write');
        // Block contact
        Route::post('/whatsapp/contact/{contactIdOrUid}/block-process', [
            ContactController::class,
            'processContactBlock',
        ])->name('app_api.vendor.contact.write.block');
        // unblock contact
        Route::post('/whatsapp/contact/{contactIdOrUid}/unblock-process', [
            ContactController::class,
            'processContactUnblock',
        ])->name('app_api.vendor.contact.write.unblock');
        // Contact datatable list
        Route::get('/contacts/list-data', [
            ContactController::class,
            'prepareContactList',
        ])->name('app_api.vendor.contact.read.list');
        // Contact add support data
        Route::get('/contacts/add-support-data', [
            ContactController::class,
            'appApiPrepareContactAddSupportData',
        ])->name('app_api.vendor.contact.read.data');
        // Contact create process
        Route::post('/contacts/add-process', [
            ContactController::class,
            'processContactCreate',
        ])->name('app_api.vendor.contact.write.create');
        // Contact get edit data
        Route::get('/contacts/{contactIdOrUid}/get-edit-support-data', [
            ContactController::class,
            'apiGetContactEditData',
        ])->name('app_api.vendor.contact.read.edit.data');
        // Contact update process
        Route::post('/contacts/update-process', [
            ContactController::class,
            'processContactUpdate',
        ])->name('app_api.vendor.contact.write.update');
        // Contact delete process
        Route::post('/contacts/{contactIdOrUid}/delete-process', [
            ContactController::class,
            'processContactDelete',
        ])->name('app_api.vendor.contact.write.delete');
        // delete selected contacts
        Route::post('/contacts/delete-selected-process', [
            ContactController::class,
            'selectedContactsDelete',
        ])->name('app_api.vendor.contacts.selected.write.delete');
        // assign group to selected contacts
        Route::post('/contacts/assign-groups-selected-process', [
            ContactController::class,
            'assignGroupsToSelectedContacts',
        ])->name('app_api.vendor.contacts.selected.write.assign_groups');
        // Get contact filter support data
        Route::get('/contacts/filter-support-data', [
            ContactController::class,
            'getContactFilterSupportData',
        ])->name('app_api.vendor.contact.read.filter_support_data');
        // Contact store filter data
        Route::post('/contacts/filter-store-process', [
            ContactController::class,
            'processStoreContactFilter',
        ])->name('app_api.vendor.contact.write.store_contact_filter');
        // All Active Bots
        Route::get("/bot-replies/{contactUid}/all-active-bots", [
            BotReplyController::class,
            'getAllActiveBots'
        ])->name('app_api.vendor.bot_reply.read.all.active.bots');
        // Get bot preview
        Route::get("/bot-replies/{botUid}/{contactIdOrUid}/bot-preview", [
            BotReplyController::class,
            'getBotPreview'
        ])->name('app_api.vendor.bot_reply.read.bot_preview');
        // BotReply update process
        Route::post("/bot-replies/quick-reply-process", [
            BotReplyController::class,
            'processBotQuickReply'
        ])->name('app_api.vendor.bot_reply.write.quick-reply');
        // Campaign list request
        Route::get('/whatsapp/campaign/{status}/list-data', [
            CampaignController::class,
            'prepareCampaignList',
        ])->name('app_api.vendor.campaign.read.list');
        // Get list of non template message preset
        Route::get('/whatsapp/campaign/non-template-message-presets/{status}/list-data', [
            CampaignController::class,
            'nonTemplateCampaignMessagePresetsList',
        ])->name('app_api.vendor.campaign.read.non_template_message_preset_list');
        // Get campaign dashboard data
        Route::get('/whatsapp/campaign/dashboard/{campaignUid}/status', [
            CampaignController::class,
            'campaignStatusView',
        ])->name('app_api.vendor.campaign.read.dashboard_status');
    });

    // logout
    Route::post('/user/logout', [
        ApiUserController::class,
        'logout'
    ])->name('api.user.logout');
    Route::post('/update-password', [
        AuthController::class,
        'updatePassword',
    ])->name('api.auth.password.update.process');
  
    // profile update request
    Route::post('/user/profile-update', [
        UserController::class,
        'updateProfile',
    ])->name('api.user.profile.update');
   
    // Account Activation
    Route::get('/{userUid}/account-activation', [
        AuthController::class,
        'accountActivation',
    ])->name('api.user.account.activation');
});
