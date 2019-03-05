<?php
// my-plugin/MyUserAdmin.php

class MyUserAdmin
{
    private $userRequest;
    private $currentUser;

    public function __construct()
    {
        $this->userRequest = new MyUserRequest();
        add_action('user_register', array($this, 'create_user'), 10, 4);
        add_action('user_profile_update_errors', array($this, 'my_profile_update'), 10, 3);
        add_action('password_reset', array($this, 'my_user_reset_password'), 10, 2);
    }

    /**
     * Function whic is called when an user update his profile
     * @param $errors
     * @param $update
     * @param $user
     */
    public function my_profile_update($errors, $update, $user)
    {
        $currentUser = wp_get_current_user();
        $currentUserRole = $currentUser->roles;
        $currentUserRole = $currentUserRole[0];
        $role = null;
        // if the user edit his profile
        if ($currentUser->ID == $user->ID) {
            // role'll be currentUser->role because user can't edit his own role
            $role = $currentUser->roles;
            $role = $role[0];
        } else {
            $role = $user->role;
        }
        if ($update == true && in_array($role, ['yith_vendor', 'shop_manager', 'administrator'])) {
            // if the user changed his password
            $oldUserData = get_user_by('ID', $user->ID);
            /*:::::::::::::::::::::: BEGIN: change password ::::::::::::::::::::::::::*/
            if (isset($user->user_pass)) {
                // the user's password in community db will be changed
                // if the current user is a manager the current_pass to user'll be his pass
                // in order to match with the token owner
                if (in_array($currentUserRole, ['shop_manager', 'administrator'])) {
                    $current_password = $currentUser->user_pass;
                } else {
                    // if not, $current_pass will be old_pass
                    $current_password = $oldUserData->user_pass;
                }
                $response = $this->userRequest->changePassword(
                    $user->ID,
                    $current_password,
                    $user->user_pass,
                    $user->user_pass
                );
                // if the user isn't not yet in community, it means that it's
                // his first promotion, so an account'll be create
                if ($response != false && $response['code'] == 404) {
                    unset($_SESSION['my-message']); // because my-message contains 404 error
                    $this->create_user($user->ID, $errors, 1, $role);
                    return; // stop the action
                }
                // if the request failed
                if ($response == false || $response['code'] != 204) {
                    $errors->add('Password error', __($_SESSION['my-message']));
                }
            }
            /*:::::::::::::::::::::::: END: change password ::::::::::::::::::::::::::::*/
            $data = [
                'username' => $user->user_login,
                'email' => $user->user_email,
                'current_password' => $user->user_pass
            ];
            // if the current user is a manager the current_pass to user'll be his pass
            // in order to match with the token owner
            if (in_array($currentUserRole, ['shop_manager', 'administrator'])) {
                $data['current_password'] = $currentUser->user_pass;
            }
            $response = $this->userRequest->edit($user->ID, $data);
            // if the user isn't not yet in community, it means that it's
            // his first promotion, so an account'll be create
            if ($response != false && $response['code'] == 404) {
                unset($_SESSION['my-message']); // because my-message contains 404 error
                $this->create_user($user->ID, $errors, 1, $role);
                return; // stop the action
            }
            // if the request failed
            if ($response == false || $response['code'] != 204) {
                $errors->add('Edit error', __($_SESSION['my-message']));
            }
        }
    }

    /**
     * Function which is called when a new user is created
     * @param $user_id
     * @param null $errors
     * @param $userEdit
     * @param null $newRole
     */
    public function create_user($user_id, &$errors = null, $userEdit = null, $newRole = null)
    {
        $user = get_user_by('ID', $user_id);
        $role = (array)$user->roles;
        $role = $role[0];
        if (isset($userEdit) && $newRole) {
            $role = $newRole;
        }
        if (in_array($role, ['shop_manager', 'administrator']) && is_user_logged_in()) {
            $data = [
                'user_id' => $user_id,
                'email' => $user->user_email,
                'username' => $user->user_login,
                'plainPassword' => [
                    'first' => $user->user_pass,
                    'second' => $user->user_pass
                ]
            ];
            if ($role == 'shop_manager') {
                $data['role'] = 'ROLE_ADMIN';
            }
            if ($role == 'administrator') {
                $data['role'] = 'ROLE_SUPER_ADMIN';
            }
            $response = $this->userRequest->newUser($data); // insert the user in community db
            // if the request fail
            if ($response == false) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                // on edit action
                if (isset($userEdit)) {
                    $errors->add(
                        'Edit error',
                        __('<div class="notice notice-error is-dismissible">
                                <p>
                                    Une erreur est survenue lors de la modification. Veuillez ressayer !
                                </p>
                            </div>')
                    );
                }
                // I choose to delete the user then notify
                $delete = wp_delete_user($user_id);
                $_SESSION['my-message'] = '<div class="notice notice-error is-dismissible">
                                                <p>
                                                    Une erreur est survenue lors de la creation du compte. Veuillez ressayer !
                                                </p>
                                           </div>';
                wp_redirect(home_url('/wp-admin/user-new.php'));
                exit();
            }
        }
    }

    /**
     * Function which is called when a user reset his password
     * @param $user
     * @param $new_pass
     * @return bool
     */
    public function my_user_reset_password($user, $new_pass)
    {
        $new_pass = wp_hash_password($new_pass);
        $token = $this->userRequest->requestResetPassword($user->user_login);
        if ($token == false) {
            // stop the action before the password is totaly reseted
            return false;
        }
        $response = $this->userRequest->confirmResetPassword($token, $new_pass);
        if ($response == false || $response['code'] != 200) {
            // stop the action before the password is totaly reseted
            return false;
        }
    }
}
