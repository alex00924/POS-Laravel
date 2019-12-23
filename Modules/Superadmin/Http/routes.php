<?php

Route::get('/pricing', 'Modules\Superadmin\Http\Controllers\PricingController@index')->name('pricing');

Route::group(['middleware' => ['web', 'auth', 'language'], 'prefix' => 'superadmin', 'namespace' => 'Modules\Superadmin\Http\Controllers'], function()
{
    Route::get('/install', 'InstallController@index');
    Route::get('/install/update', 'InstallController@update');

    Route::get('/', 'SuperadminController@index');
    Route::get('/stats', 'SuperadminController@stats');
    
    Route::get('/{business_id}/toggle-active/{is_active}', 'BusinessController@toggleActive');
    Route::resource('/business', 'BusinessController');
    Route::get('/business/{id}/destroy', 'BusinessController@destroy');

    Route::resource('/packages', 'PackagesController');
    Route::get('/packages/{id}/destroy', 'PackagesController@destroy');

    Route::get('/settings', 'SuperadminSettingsController@edit');
    Route::put('/settings', 'SuperadminSettingsController@update');
    Route::get('/edit-subscription/{id}', 'SuperadminSubscriptionsController@editSubscription');
    Route::post('/update-subscription', 'SuperadminSubscriptionsController@updateSubscription');
    Route::resource('/superadmin-subscription', 'SuperadminSubscriptionsController');

    //2019-01-07
    Route::resource('points', 'PointsController',['only' => ['index']]);
    Route::get('/points', 'PointsController@index');
    Route::get('/points/businesses_points', 'PointsController@businessesPoints');
    Route::get('/points/request-redeem/{points}', 'PointsController@requestRedeemPoints');
    Route::get('/points/requested', 'PointsController@requestedPoints');
    Route::get('/points/approved', 'PointsController@approvedPoints');
    Route::get('/points/rejected', 'PointsController@rejectedPoints');

    //Redeem points  2018-12-30 caixia
    Route::get('/approve-redeem/{request_redeem_id}', 'PointsController@approveRedeemPoints');
    Route::get('/reject-redeem/{request_redeem_id}', 'PointsController@rejectRedeemPoints');

    Route::get('/communicator', 'CommunicatorController@index');
    Route::post('/communicator/send', 'CommunicatorController@send');
    Route::get('/communicator/get-history', 'CommunicatorController@getHistory');
});

Route::group(['middleware' => ['web', 'SetSessionData', 'auth', 'language', 'timezone'], 
    'namespace' => 'Modules\Superadmin\Http\Controllers'], function()
{
	//Routes related to paypal checkout
	Route::get('/subscription/{package_id}/paypal-express-checkout', 
		'SubscriptionController@paypalExpressCheckout');

	Route::get('/subscription/{package_id}/pay', 'SubscriptionController@pay');
	Route::any('/subscription/{package_id}/confirm', 'SubscriptionController@confirm');
    Route::get('/all-subscriptions', 'SubscriptionController@allSubscriptions');

    Route::get('/subscription/{package_id}/register-pay', 'SubscriptionController@registerPay')->name('register-pay');

    Route::resource('/subscription', 'SubscriptionController');    
});