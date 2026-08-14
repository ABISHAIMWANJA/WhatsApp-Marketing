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
 * UserEngine.php - Main component file
 *
 * This file is part of the User component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\User;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\User\Repositories\UserRepository;
use App\Yantrana\Components\UserDevice\Repositories\UserDeviceRepository;
use App\Yantrana\Components\User\Interfaces\UserEngineInterface;
use Illuminate\Support\Facades\Auth;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class UserEngine extends BaseEngine implements UserEngineInterface
{
    /**
     * @var UserRepository - User Repository
     */
    protected $userRepository;

    /**
     * @var UserDeviceRepository - User Device Repository
     */
    protected $userDeviceRepository;

    /**
     * Constructor
     *
     * @param  UserRepository  $userRepository  - User Repository
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(UserRepository $userRepository, UserDeviceRepository $userDeviceRepository)
    {
        $this->userRepository = $userRepository;
        $this->userDeviceRepository = $userDeviceRepository;
    }

    /**
     * Prepare 2FA QR Code with customize style
     *
     * @param  array  $requestData
     * @return array|mixed
     */
    public function prepare2FAQrCode()
    {
        $user = $this->userRepository->fetchIt(getUserID());
        $qrCodeSvg = '';

        // Check 2FA enable
        if ($user->two_factor_secret) {
            $otpUrl = $user->twoFactorQrCodeUrl();

            $renderer = new ImageRenderer(
                new RendererStyle(500),
                new SvgImageBackEnd()
            );

            $writer = new Writer($renderer);
            $qrCodeSvg = $writer->writeString($otpUrl);
        }

        return $this->engineSuccessResponse([
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    /**
     * Process confirm 2FA Confirm
     *
     * @param  array  $inputData
     * @return array|mixed
     */
    public function process2FAuthenticationConfirm($inputData)
    {
        $user = $this->userRepository->fetchIt(getUserID());

        // Check 2FA enable
        if ($user->two_factor_secret) {
            // Check if entered code is valid or not
            if ($user->verifyTwoFactorAuth($inputData['confirm_code'])) {
                if ($this->userRepository->updateIt($user, [
                    'two_factor_confirmed_at' => now()
                ])) {
                    return $this->engineSuccessResponse([], __tr('Verification successful.'));
                }
            }

            return $this->engineFailedResponse([], __tr('Entered code is invalid.'));
        }

        return $this->engineFailedResponse([], __tr('2FA not enabled.'));
    }

    /**
     * Process profile update request
     *
     * @param  array  $requestData
     * @return array|mixed
     */
    public function processUpdateProfile($requestData)
    {
        //fetch active user by email
        if ($this->userRepository->updateLoggedInUserProfile($requestData)) {
            return $this->engineResponse(21, [
                'messageType' => 'success',
                'reloadPage' => true,
            ], __tr('Your Profile has been updated successfully'));
        }

        return $this->engineResponse(14, null, __tr('Nothing to update'));
    }


    /**
      * User datatable source
      *
      * @return  array
      *---------------------------------------------------------------- */
    public function prepareUserDataTableSource()
    {
        $userCollection = $this->userRepository->fetchUserDataTableSource();
      
        // required columns for DataTables
        $requireColumns = [
            '_id',
            '_uid',
            'first_name',
            'last_name',
            'username',
            'email' => function ($rowData) {
                return maskString($rowData['email'], 'email');
            },
            'mobile_number' => function ($rowData) {
                return maskString($rowData['mobile_number'], 'phone');
            },
            'status'=>function ($data) {
                if($data['status']==0){
                 return 'Inactive';
                }else{
                    return configItem('status_codes', $data['status']);
                }
            },
            'user_roles__id',
            'user_role' => function ($row) {
                return $row['role']['title'];
            },
            'created_at' => function ($row) {
                return formatDate($row['created_at']);
            },
        ];
        // prepare data for the DataTables
        return $this->dataTableResponse($userCollection, $requireColumns);
    }


    /**
      * User delete process
      *
      * @param  mix $userIdOrUid
      *
      * @return  array
      *---------------------------------------------------------------- */

    public function processUserDelete($userIdOrUid)
    {
        // fetch the record
        $user = $this->userRepository->fetchIt($userIdOrUid);
        // check if the record found
        if (__isEmpty($user)) {
            // if not found
            return $this->engineResponse(18, null, __tr('User not found'));
        }
        $vendorId = getVendorId();
        // check if the user belongs to the current vendor
        if(!$this->userRepository->isVendorUser($user->_id, $vendorId)) {
            return $this->engineFailedResponse([], __tr('Invalid user'));
        }
        // ask to delete the record
        if ($this->userRepository->deleteIt($user)) {
            // if successful
            return $this->engineResponse(1, null, __tr('User deleted successfully'));
        }
        // if failed to delete
        return $this->engineResponse(2, null, __tr('Failed to delete User'));
    }

    /**
      * Process logout as for Team Member
      *
      *
      * @return  EngineResponse
      *---------------------------------------------------------------- */

    public function processLogoutAs()
    {
        Auth::logout();
        Auth::loginUsingId(session('loggedByVendor.id'));
        $hasSuperAdminLogin = session('loggedBySuperAdmin');
        session()->forget('loggedByVendor');
        if($hasSuperAdminLogin) {
            session([
                'loggedBySuperAdmin' => $hasSuperAdminLogin
            ]);
        }
        return $this->engineSuccessResponse([
            'show_message' => true,
        ], __tr('Welcome, back to your account.'));
    }
    /**
      * Process login as for Team Member
      *
      * @param  string $userIdOrUid
      *
      * @return  EngineResponse
      *---------------------------------------------------------------- */

    public function processLoginAs($userIdOrUid)
    {
        // demo
        if(isDemo() and isDemoVendorAccount()) {
            return $this->engineFailedResponse([], __tr('Functionality is disabled for demo'));
        }

        $vendorId = getVendorId();
        $user = $this->userRepository->fetchIt($userIdOrUid);
        // check if the user belongs to the current vendor
        if(!$this->userRepository->isVendorUser($user->_id, $vendorId)) {
            return $this->engineFailedResponse([], __tr('Invalid user'));
        }

        if($user->_id == getUserID()) {
            return $this->engineFailedResponse([], __tr('You can not logged in to your own account.'));
        }
        session([
            'loggedByVendor' => [
                'id' => getUserID(),
                'name' => getUserAuthInfo('profile.full_name'),
            ]
        ]);
        Auth::loginUsingId($user->_id);
        return $this->engineSuccessResponse([
            'show_message' => true,
        ], __tr('Welcome, you are logged as __userName__ successfully.', [
            '__userName__' => $user->full_name
        ]));
    }
    /**
      * User create
      *
      * @param  array $inputData
      *
      * @return  array
      *---------------------------------------------------------------- */

    public function processUserCreate($inputData)
    {
        $vendorId = getVendorId();
        // check the feature limit
        $vendorPlanDetails = vendorPlanDetails('system_users', $this->userRepository->countVendorUsers($vendorId), $vendorId);
        if (!$vendorPlanDetails['is_limit_available']) {
            return $this->engineResponse(22, null, $vendorPlanDetails['message']);
        }

        $inputData['status'] = 1; // Active
        $inputData['user_roles__id'] = 3; //vendor agent
        $inputData['vendors__id'] = $vendorId;
        $inputData['permissions'] = $inputData['permissions'] ?? [];
        $permissions = [];
        // assign permissions
        foreach (getListOfPermissions() as $permissionKey => $permission) {
            if(array_key_exists($permissionKey, $inputData['permissions'])) {
                $permissions[$permissionKey] = 'allow';
                $subPermissionData = data_get($inputData, 'subPermissions', []);
                if (!__isEmpty(data_get($permission, 'permissions'))) {
                    foreach ($permission['permissions'] as $subPermissionKey => $subPermission) {
                        if (array_key_exists($subPermissionKey, $subPermissionData)) {
                            $permissions[$permissionKey.'@'.$subPermissionKey] = 'allow';
                        } else {
                            $permissions[$permissionKey.'@'.$subPermissionKey] = 'deny';
                        }
                    }
                }

            } else {
                $permissions[$permissionKey] = 'deny';

                if (!__isEmpty(data_get($permission, 'permissions'))) {
                    foreach ($permission['permissions'] as $subPermissionKey => $subPermission) {
                        $permissions[$permissionKey.'@'.$subPermissionKey] = 'deny';
                    }
                }
            }
        }
        
        $inputData['permissions'] = $permissions;
        $transactionResponse = $this->userRepository->processTransaction(function () use ($inputData) {
            // ask to add record
            if ($newUser = $this->userRepository->storeUser($inputData, true)) {
                return $this->userRepository->transactionResponse(1, ['show_message' => true], __tr('User created'));
            }
            return $this->userRepository->transactionResponse(2, ['show_message' => true], __tr('Failed to create user'));
        });
        return $this->engineResponse($transactionResponse);
    }

    
    /**
      * User process update
      *
      * @param  mixed $userIdOrUid
      * @param  array $inputData
      *
      * @return  array
      *---------------------------------------------------------------- */
    public function processUserUpdate($userIdOrUid, $request)
    {
        $user = $this->userRepository->fetchIt($userIdOrUid);
        $request->validate([
            'email' => Rule::unique('users', 'email')->ignore($user, 'email'),
        ]);
        $inputData = $request->all();
        // Check if $user not exist then throw not found
        // exception
        if (__isEmpty($user)) {
            return $this->engineResponse(18, null, __tr('User not found.'));
        }

        $vendorId = getVendorId();
        // check if the user belongs to the current vendor
        if(!$this->userRepository->isVendorUser($user->_id, $vendorId)) {
            return $this->engineFailedResponse([], __tr('Invalid user'));
        }

        $updateData = [
            'first_name' => $inputData['first_name'],
            'last_name' => $inputData['last_name'],
            'mobile_number' => $inputData['mobile_number'] ?? $user->mobile_number,
            'email' => $inputData['email'] ?? $user->email,
            'status'=>formSwitchValue($inputData['status']),
        ];
        if($inputData['password']) {
            $updateData['password'] = $inputData['password'];
        }

        $inputData['permissions'] = $inputData['permissions'] ?? [];
        $permissions = [];
        // assign permissions
        foreach (getListOfPermissions() as $permissionKey => $permission) {
            if(array_key_exists($permissionKey, $inputData['permissions'])) {
                $permissions[$permissionKey] = 'allow';
                $subPermissionData = data_get($inputData, 'subPermissions', []);
                if (!__isEmpty(data_get($permission, 'permissions'))) {
                    foreach ($permission['permissions'] as $subPermissionKey => $subPermission) {
                        if (array_key_exists($subPermissionKey, $subPermissionData)) {
                            $permissions[$permissionKey.'@'.$subPermissionKey] = 'allow';
                        } else {
                            $permissions[$permissionKey.'@'.$subPermissionKey] = 'deny';
                        }
                    }
                }

            } else {
                $permissions[$permissionKey] = 'deny';

                if (!__isEmpty(data_get($permission, 'permissions'))) {
                    foreach ($permission['permissions'] as $subPermissionKey => $subPermission) {
                        $permissions[$permissionKey.'@'.$subPermissionKey] = 'deny';
                    }
                }
            }
        }
        $inputData['permissions'] = $permissions;
        $inputData['vendors__id'] = getVendorId();
        $transactionResponse = $this->userRepository->processTransaction(function () use ($user, $updateData, $inputData) {
            // ask to add record
            if ($this->userRepository->updateUser($user, $updateData, $inputData)) {
                return $this->userRepository->transactionResponse(1, ['show_message' => true], __tr('User updated'));
            }
            return $this->userRepository->transactionResponse(14, ['show_message' => true], __tr('No updates'));
        });
        return $this->engineResponse($transactionResponse);

        // Check if User updated
        if ($this->userRepository->updateIt($user, $updateData)) {

            return $this->engineResponse(1, null, __tr('User updated.'));
        }

        return $this->engineResponse(14, null, __tr('User not updated.'));
    }

          /**
      * User prepare Team member update data
      *
      * @param  mix $userIdOrUid
      *
      * @return  array
      *---------------------------------------------------------------- */
      public function prepareUserUpdateData($userIdOrUid)
      {
          $user = $this->userRepository->with('vendorUserDetails')->fetchIt($userIdOrUid);
          // Check if $user not exist then throw not found
          // exception
          if (__isEmpty($user)) {
              return $this->engineResponse(18, null, __tr('User not found.'));
          }
          $user->mobile_number = maskString($user->mobile_number, 'phone');
          $user->email = maskString($user->email, 'email');
          $vendorId = getVendorId();
          // check if the user belongs to the current vendor
          if(!$this->userRepository->isVendorUser($user->_id, $vendorId)) {
              return $this->engineFailedResponse([], __tr('Invalid user'));
          }
  
          return $this->engineResponse(1, $user->toArray());
      }

    /**
      * User prepare update data
      *
      * @param  mix $userIdOrUid
      *
      * @return  array
      *---------------------------------------------------------------- */

      public function prepareVendorUpdateData($userIdOrUid)
      {
          $user = $this->userRepository->with('vendorUserDetails')->fetchIt($userIdOrUid);
          // Check if $user not exist then throw not found
          // exception
          if (__isEmpty($user)) {
              return $this->engineResponse(18, null, __tr('User not found.'));
          }
  
          $vendorId = getVendorId();
  
          return $this->engineResponse(1, $user->toArray());
      }
      
    /**
     * Process store user device token.
     *
     *-----------------------------------------------------------------------*/
    public function processStoreUserDeviceToken($inputData)
    {
        $userId = $inputData['users__id'] = getUserID();
        $inputData['vendors__id'] = getVendorId();
        // Check if device token for particular user exists
        $existingDeviceToken = $this->userDeviceRepository->fetchIt([
            'device_token' => $inputData['device_token'],
            'users__id' => $userId
        ]);

        // Check if device token exists
        if (!__isEmpty($existingDeviceToken)) {
            return $this->engineReaction(2, [ 'show_message' => false], __tr('Device token already exists.'));
        }

        if ($this->userDeviceRepository->storeIt($inputData)) {
            return $this->engineReaction(1, null, __tr('Device token stored successfully.'));
        }

        return $this->engineReaction(2, __tr('Failed to store device token.'));
    }
}
