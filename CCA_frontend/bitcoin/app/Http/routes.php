<?php
/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::get('', function (){
    return redirect("bitcoin/block");
});

Route::group(['middleware' => ['web'], 'prefix' => '{currency}'], function () {
    Route::get('/block',[
        'as'    => 'block_findall',
        'uses'  => 'BlockController@findAll'
    ]);
    Route::get('block/{hash}',[
        'as'    => 'block_findone',
        'uses'  =>'BlockController@findOne'
    ]);

    Route::get('transaction/search', [
        'as'     => 'transaction_search',
        'uses'  => 'TransactionController@search',
    ]);

    Route::get('transaction/visualize',[
        'as'    => "transaction_search_visualize",
        'uses'  => "TransactionController@searchAndVisualize"
    ]);

    Route::post('transaction/visualize',[
        'as'    => "transaction_search_visualize_submit",
        'uses'  => "TransactionController@searchAndVisualizeSubmit"
    ]);

    Route::get('transaction/{txid}',[
        'as'    => 'transaction_findone',
        'uses'  => 'TransactionController@findOne'
    ]);

    Route::get('transaction/{txid}/visualize', [
       'as'     => 'transaction_visualize',
        'uses'  => 'TransactionController@visualize',
    ]);

    Route::get('transaction/{txid}/relations', [
        'as'     => 'transaction_outputs',
        'uses'  => 'TransactionController@relations',
    ]);

    Route::get('transaction/{txid}/input/{inputNo}', [
        'as'    =>  'transaction_input',
        'uses'  =>  'TransactionController@inputDetail'
    ]);

    Route::get('transaction/{txid}/output/{outputNo}', [
        'as'    =>  'transaction_output',
        'uses'  =>  'TransactionController@outputDetail'
    ]);

    Route::get('transaction/{txid}/structure', [
        'as'     => 'transaction_structure',
        'uses'  => 'TransactionController@structure',
    ]);

    Route::get('address/{address}',[
        'as'    => 'address_findone',
        'uses'  => 'AddressController@findOne',
    ]);

    Route::get('address/{address}/cluster', [
        'as'        => 'address_cluster',
        'uses'      => 'AddressController@clusterForAddress'
    ]);

    Route::post('search', [
        'as'    => 'search',
        'uses'  => 'SearchController@search'
    ]);

    Route::get('/tag/{tag}',[
        'as'    => 'tag_findOne',
        'uses'  => 'TagController@findOne'
    ]);

    Route::get('/pubkey/{pubkey}',[
        'as'    => 'pubkey_findOne',
        'uses'  => 'PubkeyController@findOne'
    ]);
});
