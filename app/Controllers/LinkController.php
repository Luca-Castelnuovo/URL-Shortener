<?php

namespace App\Controllers;

use App\Validators\LinkValidator;
use CQ\Controllers\Controller;
use CQ\Helpers\User;
use CQ\Helpers\Str;
use CQ\Helpers\UUID;
use CQ\DB\DB;

class LinkController extends Controller
{
    /**
     * url redirect.
     *
     * @param string $short_url
     *
     * @return Redirect|Html
     */
    public function index($short_url)
    {
        $link = DB::get(
            'links',
            ['long_url'],
            ['short_url' => $short_url]
        );

        if (!$link) {
            return $this->redirect("/error/404", 404);
        }

        DB::update('links', [
            'clicks[+]' => 1,
        ], [
            'short_url' => $short_url,
        ]);

        return $this->redirect($link['long_url']);
    }

    /**
     * Create short url.
     *
     * @param object $request
     *
     * @return Json
     */
    public function create($request)
    {
        try {
            LinkValidator::create($request->data);
        } catch (\Throwable $th) {
            return $this->respondJson(
                'Provided data was malformed',
                json_decode($th->getMessage()),
                422
            );
        }

        if (!$request->data->short_url) {
            $request->data->short_url = Str::random(6);
        }

        if (DB::has('links', [
            'short_url' => $request->data->short_url,
        ])) {
            return $this->respondJson(
                'Short URL is already used',
                [],
                400
            );
        }

        $user_n_links = DB::count('links', ['user_id' => User::getId()]);
        $max_n_links = User::valueRole('max_links');
        if ($user_n_links >= $max_n_links) {
            return $this->respondJson(
                "Links quota reached, max {$max_n_links}",
                [],
                400
            );
        }

        DB::create('links', [
            'id' => UUID::v6(),
            'user_id' => User::getId(),
            'short_url' => $request->data->short_url,
            'long_url' => $request->data->long_url,
        ]);

        return $this->respondJson(
            'Link Created',
            ['reload' => true]
        );
    }

    /**
     * Delete short url.
     *
     * @param object $request
     * @param string $id
     *
     * @return Json
     */
    public function delete($id)
    {
        if (!DB::has('links', [
            'id' => $id,
            'user_id' => User::getId(),
        ]) && !User::hasRole('admin')) {
            return $this->respondJson(
                'Link not found',
                [],
                404
            );
        }

        DB::delete('links', ['id' => $id]);

        return $this->respondJson(
            'Link Deleted',
            ['reload' => true]
        );
    }
}
