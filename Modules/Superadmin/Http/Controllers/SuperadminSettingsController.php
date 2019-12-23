<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Utils\BusinessUtil;

use App\System;

class SuperadminSettingsController extends BaseController
{
    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;
    protected $mailDrivers;
    protected $backupDisk;

    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;

        $this->mailDrivers = [
                        'smtp' => 'SMTP',
                        'sendmail' => 'Sendmail',
                        'mailgun' => 'Mailgun',
                        'mandrill' => 'Mandrill',
                        'ses' => 'SES',
                        'sparkpost' => 'Sparkpost'
                    ];

        $this->backupDisk = ['local' => 'Local', 'dropbox' => 'Dropbox'];
    }
 
    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $settings = System::pluck('value', 'key');
        $currencies = $this->businessUtil->allCurrencies();

        $superadmin_version = System::getProperty('superadmin_version');
        $is_demo = env('APP_ENV') == 'demo' ? true : false;

        $default_values = [
            'APP_NAME' => env('APP_NAME'), 
            'APP_TITLE' => env('APP_TITLE'), 
            'APP_LOCALE' => env('APP_LOCALE'),
            'MAIL_DRIVER' => $is_demo ? null : env('MAIL_DRIVER'), 
            'MAIL_HOST' => $is_demo ? null : env('MAIL_HOST'), 
            'MAIL_PORT' => $is_demo ? null : env('MAIL_PORT'), 
            'MAIL_USERNAME' => $is_demo ? null : env('MAIL_USERNAME'), 
            'MAIL_PASSWORD' => $is_demo ? null : env('MAIL_PASSWORD'), 
            'MAIL_ENCRYPTION' => $is_demo ? null : env('MAIL_ENCRYPTION'), 
            'MAIL_FROM_ADDRESS' => $is_demo ? null : env('MAIL_FROM_ADDRESS'), 
            'MAIL_FROM_NAME' => $is_demo ? null : env('MAIL_FROM_NAME'), 
            'STRIPE_PUB_KEY' => $is_demo ? null : env('STRIPE_PUB_KEY'), 
            'STRIPE_SECRET_KEY' => $is_demo ? null : env('STRIPE_SECRET_KEY'), 
            'PAYPAL_MODE' => env('PAYPAL_MODE'), 
            'PAYPAL_SANDBOX_API_USERNAME' => $is_demo ? null : env('PAYPAL_SANDBOX_API_USERNAME'), 
            'PAYPAL_SANDBOX_API_PASSWORD' => $is_demo ? null : env('PAYPAL_SANDBOX_API_PASSWORD'), 
            'PAYPAL_SANDBOX_API_SECRET' => $is_demo ? null : env('PAYPAL_SANDBOX_API_SECRET'), 
            'PAYPAL_LIVE_API_USERNAME' =>$is_demo ? null : env('PAYPAL_LIVE_API_USERNAME'), 
            'PAYPAL_LIVE_API_PASSWORD' => $is_demo ? null : env('PAYPAL_LIVE_API_PASSWORD'), 
            'PAYPAL_LIVE_API_SECRET' => $is_demo ? null : env('PAYPAL_LIVE_API_SECRET'), 
            'BACKUP_DISK' => env('BACKUP_DISK'), 
            'DROPBOX_ACCESS_TOKEN' => $is_demo ? null : env('DROPBOX_ACCESS_TOKEN'), 
        ];
        $mail_drivers = $this->mailDrivers;

        $languages = config('constants.langs');
        $backup_disk = $this->backupDisk;

        return view('superadmin::superadmin_settings.edit')
            ->with(compact('currencies', 'settings', 
                'superadmin_version', 'mail_drivers', 'languages', 'default_values', 'backup_disk'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }
        
        try{

            //Disable .ENV settings in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                                'msg' => 'Feature disabled in demo!!'
                            ];
                return back()->with('status', $output);
            }
            
            $system_settings = $request->only(['app_currency_id', 'invoice_business_name', 'email', 'invoice_business_landmark', 'invoice_business_zip', 'invoice_business_state', 'invoice_business_city', 'invoice_business_country', 'package_expiry_alert_days']);

            //Checkboxes
            $checkboxes = ['enable_business_based_username'];
            $input = $request->input();
            foreach ($checkboxes as $checkbox) {
               $system_settings[$checkbox] = !empty($input[$checkbox]) ? 1 : 0;
            }

            foreach( $system_settings as $key => $setting)
            {
                System::where('key', $key)
                        ->update(['value' => $setting]);
            }

            $env_settings =  $request->only(['APP_NAME', 'APP_TITLE', 
                'APP_LOCALE', 'MAIL_DRIVER', 'MAIL_HOST', 'MAIL_PORT', 
                'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 
                'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'STRIPE_PUB_KEY',
                'STRIPE_SECRET_KEY', 'PAYPAL_MODE', 
                'PAYPAL_SANDBOX_API_USERNAME', 
                'PAYPAL_SANDBOX_API_PASSWORD', 
                'PAYPAL_SANDBOX_API_SECRET', 'PAYPAL_LIVE_API_USERNAME',
                'PAYPAL_LIVE_API_PASSWORD', 'PAYPAL_LIVE_API_SECRET', 
                'BACKUP_DISK', 'DROPBOX_ACCESS_TOKEN'
            ]);

            $found_envs = [];
            $env_path = base_path('.env');
            $env_lines = file($env_path);
            foreach ($env_settings as $index => $value) {
                foreach ($env_lines as $key => $line) {
                    //Check if present then replace it.
                    if (strpos($line, $index) !== false) {
                        $env_lines[$key] = $index . '="' . $value . '"' . PHP_EOL;

                        $found_envs[] = $index;
                    }
                }
            }

            //Add the missing env settings
            $missing_envs = array_diff(array_keys($env_settings), $found_envs);
            if(!empty($missing_envs)){
                $missing_envs = array_values($missing_envs);
                foreach ($missing_envs as $k => $key) {
                    if($k == 0){
                        $env_lines[] = PHP_EOL . $key . '="' . $env_settings[$key] . '"' . PHP_EOL;
                    } else {
                        $env_lines[] = $key . '="' . $env_settings[$key] . '"' . PHP_EOL;
                    }
                }
            }

            $env_content = implode('', $env_lines);

            if(is_writable($env_path) && file_put_contents($env_path, $env_content)){
                $output = ['success' => 1, 
                            'msg' => __('lang_v1.success')
                        ];
            } else {
                $output = ['success' => 0, 'msg' => 'Some setting could not be saved, make sure .env file has 644 permission & owned by www-data user'];
            }

        }catch(\Exception $e){
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = array('success' => 0, 
                            'msg' => __('messages.something_went_wrong')
                        );
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@edit')
            ->with('status', $output);
    }
}
